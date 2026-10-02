<?php

namespace App\Services;

use App\Exceptions\PaymentVerificationFailedException;
use App\Models\Tenant\MaintenanceBill;
use App\Models\Tenant\Payment;
use App\Models\Tenant\RazorpayOrder;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use RuntimeException;
use Throwable;

/**
 * All the "can this be trusted" logic for resident online payments lives
 * here, not in the controller — see routes/api.php's bills.pay.* routes and
 * BillPaymentController, which only handle HTTP shape.
 *
 * The structural guarantee this class enforces: the amount charged is
 * always derived from the bill's own balance, server-side, and a
 * "payment succeeded" claim from the app is never trusted on its own —
 * every success is independently re-derived from Razorpay's own servers
 * (signature + a live payment re-fetch) before the ledger is touched.
 *
 * Money taken must always end up on the bill, even when the app never
 * reports back (killed mid-checkout, network drop, a verify that failed on
 * a transient error): reconcileOrder() asks Razorpay directly which
 * payments an order has and records a captured one. It runs from
 * `payments:reconcile` (scheduled) and when a resident re-opens the bill.
 *
 * Each society has its own Razorpay account: the keys come from the
 * current society (set by the Super Admin, see
 * Admin\SocietyPaymentGatewayController), so a resident's money goes
 * straight to their society. Test keys (`rzp_test_`) take test payments.
 *
 * Every Razorpay API call goes through the small protected methods at the
 * bottom, which return plain arrays (tests override them).
 */
class RazorpayService
{
    /** @var array<string, Api> one client per key id (reconcile walks every society) */
    private array $apis = [];

    /** Whether the current society can take online payments. */
    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    /** The current society's public key id, which the app's checkout needs. */
    public function keyId(): ?string
    {
        return $this->credentials()[0] ?? null;
    }

    /**
     * Creates a Razorpay order for whatever the bill's balance actually is
     * right now — the request that triggers this never carries an amount,
     * so there's nothing for a client to tamper with here.
     *
     * @throws \DomainException if the bill has nothing left to pay
     */
    public function createOrderForBill(MaintenanceBill $bill, TenantUser $user): RazorpayOrder
    {
        $bill->load('payments');

        if ($bill->balance <= 0) {
            throw new \DomainException('This bill is already fully paid.');
        }

        if (!$this->isConfigured()) {
            throw new \DomainException('Online payment is not available for your society yet. Please pay at the society office.');
        }

        $amountPaise = (int) round($bill->balance * 100);

        try {
            $razorpayOrderId = $this->createRemoteOrder([
                'amount' => $amountPaise,
                'currency' => 'INR',
                // Unique per attempt (not per bill) so a resident can retry a
                // failed/abandoned checkout without colliding with the old one.
                'receipt' => 'bill-' . $bill->id . '-' . Str::random(8),
                'payment_capture' => 1,
                'notes' => [
                    'bill_id' => (string) $bill->id,
                    'user_id' => (string) $user->id,
                ],
            ]);
        } catch (Throwable $e) {
            // Razorpay unreachable, misconfigured keys, etc. — never leak
            // that detail (it could confirm to a caller whether keys are
            // even configured); log it and surface a generic message.
            Log::error('razorpay.create_order_failed', ['bill_id' => $bill->id, 'error' => $e->getMessage()]);
            throw new PaymentVerificationFailedException(
                "Razorpay order creation failed for bill {$bill->id}: {$e->getMessage()}",
                'Could not start the payment right now. Please try again in a moment.',
            );
        }

        return RazorpayOrder::create([
            'bill_id' => $bill->id,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $bill->balance,
            'currency' => 'INR',
            'status' => 'created',
            'initiated_by_user_id' => $user->id,
        ]);
    }

    /**
     * Verifies a checkout-completion claim against Razorpay's own truth and,
     * only once every check below passes, records the payment. Returns the
     * refreshed bill.
     *
     * @param  array<int>  $flatIds  the caller's own flat ids (never trust a bill id alone)
     *
     * @throws PaymentVerificationFailedException on any failure — always
     *   carries a client-safe generic message; the real reason is logged.
     */
    public function verifyAndRecordPayment(
        int $billId,
        array $flatIds,
        string $orderId,
        string $paymentId,
        string $signature,
        TenantUser $user,
    ): MaintenanceBill {
        // Phase 1 — claim the order under a row lock, released quickly
        // (never held across the network calls in phase 2). Ownership is
        // enforced here via whereIn('flat_id', $flatIds): a real, valid
        // {order_id, payment_id, signature} triple for someone else's bill
        // must still be rejected, or the cryptographic checks below would
        // happily "verify" it against the wrong resident's ledger.
        $order = DB::transaction(function () use ($billId, $flatIds, $orderId) {
            $order = RazorpayOrder::where('bill_id', $billId)
                ->where('razorpay_order_id', $orderId)
                ->whereHas('bill', fn ($q) => $q->whereIn('flat_id', $flatIds))
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new PaymentVerificationFailedException(
                    "No local order found for bill={$billId}, order_id={$orderId} within the caller's flats.",
                );
            }

            // Already recorded (e.g. reconciliation got there first, or a
            // client retry): nothing to do, the caller just gets the bill.
            if ($order->status === 'paid') {
                return $order;
            }

            if ($order->status !== 'created') {
                // Replay guard: an order being verified right now, or one
                // that already failed, is never re-claimed from a client
                // claim. reconcileOrder() still settles it from Razorpay's
                // own records if money was actually taken.
                throw new PaymentVerificationFailedException(
                    "Order {$orderId} is already '{$order->status}', refusing to re-verify.",
                );
            }

            $order->update(['status' => 'processing']);

            return $order;
        });

        if ($order->status === 'paid') {
            return $order->bill()->with('payments')->first();
        }

        // Phase 2 — verify against Razorpay's own servers. No DB lock is
        // held during this network I/O.
        if (!$this->signatureIsValid($orderId, $paymentId, $signature)) {
            $this->failOrder($order, 'signature_mismatch');
            throw new PaymentVerificationFailedException("Signature verification failed for order {$orderId}.");
        }

        try {
            $payment = $this->ensureCaptured($this->fetchPayment($paymentId), $order);
        } catch (PaymentVerificationFailedException $e) {
            $this->failOrder($order, $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            $this->failOrder($order, 'razorpay_fetch_failed: ' . $e->getMessage());
            throw new PaymentVerificationFailedException("Could not fetch payment {$paymentId} from Razorpay: {$e->getMessage()}");
        }

        // Phase 3 — finalize under a fresh lock.
        try {
            return $this->recordCapturedPayment($order, $payment, $signature);
        } catch (QueryException $e) {
            // Belt-and-braces: the unique constraint on razorpay_payment_id
            // is a DB-level replay guard independent of the app-level
            // status check above — if it ever fires, treat it exactly like
            // any other verification failure rather than a 500.
            $this->failOrder($order, 'db_constraint_violation: ' . $e->getMessage());
            throw new PaymentVerificationFailedException("Finalizing payment {$paymentId} hit a database constraint: {$e->getMessage()}");
        }
    }

    /**
     * Settles one order from Razorpay's own records, without any claim from
     * the app: if Razorpay holds a payment for it that is (or can be)
     * captured for the right amount, it is recorded on the bill. Safe to
     * run any number of times and alongside a verify — recording is
     * idempotent under a row lock. Never throws.
     *
     * @return bool  whether a payment was recorded
     */
    public function reconcileOrder(RazorpayOrder $order): bool
    {
        if ($order->status === 'paid') {
            return false;
        }

        try {
            foreach ($this->fetchOrderPayments($order->razorpay_order_id) as $payment) {
                if (!in_array($payment['status'] ?? null, ['authorized', 'captured'], true)) {
                    continue;
                }

                $this->recordCapturedPayment($order, $this->ensureCaptured($payment, $order), null);

                return true;
            }
        } catch (Throwable $e) {
            Log::warning('razorpay.reconcile_failed', [
                'order_id' => $order->razorpay_order_id,
                'bill_id' => $order->bill_id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Reconciles a bill's recent unsettled orders — called when a resident
     * opens the bill, so "Check Bill Status" after an unconfirmed payment
     * shows it paid straight away. Each order is asked about at most once a
     * minute so opening the screen stays fast.
     */
    public function reconcileBill(MaintenanceBill $bill): bool
    {
        $recorded = false;

        $orders = RazorpayOrder::where('bill_id', $bill->id)
            ->where('status', '!=', 'paid')
            ->where('created_at', '>=', now()->subDay())
            ->get();

        foreach ($orders as $order) {
            if (Cache::add("razorpay.reconciled.{$order->razorpay_order_id}", true, 60)) {
                $recorded = $this->reconcileOrder($order) || $recorded;
            }
        }

        return $recorded;
    }

    /**
     * Checks a Razorpay payment really belongs to this order and is for its
     * full amount, and captures it if it is only authorized (when automatic
     * capture is off in the Razorpay dashboard an authorized payment would
     * otherwise be refunded by Razorpay after a few days).
     *
     * @param  array<string, mixed>  $payment
     * @return array<string, mixed>  the captured payment
     *
     * @throws PaymentVerificationFailedException
     */
    private function ensureCaptured(array $payment, RazorpayOrder $order): array
    {
        $paymentId = $payment['id'] ?? 'unknown';

        if (($payment['order_id'] ?? null) !== $order->razorpay_order_id) {
            throw new PaymentVerificationFailedException("order_id_mismatch: payment {$paymentId} is not for order {$order->razorpay_order_id}.");
        }

        // The concrete enforcement of "never trust a client-supplied
        // amount": what Razorpay holds is compared against the amount *we*
        // set when the order was created (itself only ever derived from the
        // bill's balance) — nothing here reads an amount from the request.
        $expectedPaise = (int) round(((float) $order->amount) * 100);

        if ((int) ($payment['amount'] ?? 0) !== $expectedPaise) {
            throw new PaymentVerificationFailedException("amount_mismatch: expected {$expectedPaise}, got " . ($payment['amount'] ?? 'none') . " for payment {$paymentId}.");
        }

        if (($payment['status'] ?? null) === 'authorized') {
            $payment = $this->capturePayment($paymentId, $expectedPaise, $order->currency ?: 'INR');
        }

        if (($payment['status'] ?? null) !== 'captured') {
            throw new PaymentVerificationFailedException('payment_status_not_captured: ' . ($payment['status'] ?? 'unknown') . " for payment {$paymentId}.");
        }

        return $payment;
    }

    /**
     * Writes the payment to the bill's ledger exactly once: under a lock on
     * the order, an order that is already paid is left as it is.
     *
     * @param  array<string, mixed>  $payment  a captured Razorpay payment
     */
    private function recordCapturedPayment(RazorpayOrder $order, array $payment, ?string $signature): MaintenanceBill
    {
        [$bill, $paymentRow] = DB::transaction(function () use ($order, $payment, $signature) {
            $order = RazorpayOrder::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status === 'paid') {
                return [$order->bill()->with('payments')->first(), null];
            }

            $paymentRow = Payment::create([
                'bill_id' => $order->bill_id,
                'amount' => $order->amount,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'online',
                'reference_number' => $payment['id'],
                'notes' => 'Paid online via Razorpay.',
                'recorded_by_user_id' => null,
            ]);

            $order->update([
                'status' => 'paid',
                'razorpay_payment_id' => $payment['id'],
                'razorpay_signature' => $signature,
                'payment_id' => $paymentRow->id,
                'verified_at' => now(),
                'failure_reason' => null,
                'meta' => $payment,
            ]);

            return [$order->bill()->with('payments')->first(), $paymentRow];
        });

        // After the commit, and never able to undo it: the money is
        // recorded whether or not the "payment received" push goes out.
        if ($paymentRow) {
            try {
                app(NotificationService::class)->notifyFlats(
                    [$bill->flat_id],
                    'payment_received',
                    'Payment received',
                    'Your payment of ₹' . number_format((float) $paymentRow->amount, 2) . " for \"{$bill->title}\" was received.",
                    ['bill_id' => $bill->id, 'payment_id' => $paymentRow->id],
                );
            } catch (Throwable $e) {
                Log::warning('razorpay.payment_notification_failed', ['bill_id' => $bill->id, 'error' => $e->getMessage()]);
            }
        }

        return $bill;
    }

    /**
     * Razorpay signs "{order_id}|{payment_id}" with the key secret. Checked
     * here directly: the SDK's Utility::verifyPaymentSignature() reads the
     * secret from a static that is only set once an Api object has been
     * built, so on a fresh request it compared against an empty secret and
     * rejected every genuine payment.
     */
    private function signatureIsValid(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = (string) ($this->credentials()[1] ?? '');

        return $secret !== ''
            && hash_equals(hash_hmac('sha256', $orderId . '|' . $paymentId, $secret), $signature);
    }

    private function failOrder(RazorpayOrder $order, string $reason): void
    {
        Log::warning('razorpay.verify_failed', [
            'order_id' => $order->razorpay_order_id,
            'bill_id' => $order->bill_id,
            'reason' => $reason,
        ]);

        // A concurrent reconcile may already have recorded it - never
        // downgrade a paid order.
        RazorpayOrder::whereKey($order->id)
            ->where('status', '!=', 'paid')
            ->update(['status' => 'failed', 'failure_reason' => Str::limit($reason, 250)]);
    }

    /**
     * The current society's [key id, key secret], or null when the Super
     * Admin hasn't set them up.
     *
     * @return array{0: string, 1: string}|null
     */
    protected function credentials(): ?array
    {
        $society = app(TenantService::class)->getCurrentSociety();

        return $society?->hasRazorpay() ? [$society->razorpay_key_id, $society->razorpay_key_secret] : null;
    }

    private function api(): Api
    {
        [$key, $secret] = $this->credentials()
            ?? throw new RuntimeException('Razorpay keys are not set up for this society.');

        return $this->apis[$key] ??= new Api($key, $secret);
    }

    /** @param array<string, mixed> $attributes */
    protected function createRemoteOrder(array $attributes): string
    {
        return $this->api()->order->create($attributes)->id;
    }

    /** @return array<string, mixed> */
    protected function fetchPayment(string $paymentId): array
    {
        return $this->api()->payment->fetch($paymentId)->toArray();
    }

    /** @return array<string, mixed> */
    protected function capturePayment(string $paymentId, int $amountPaise, string $currency): array
    {
        return $this->api()->payment->fetch($paymentId)
            ->capture(['amount' => $amountPaise, 'currency' => $currency])
            ->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    protected function fetchOrderPayments(string $orderId): array
    {
        return $this->api()->order->fetch($orderId)->payments()->toArray()['items'] ?? [];
    }
}
