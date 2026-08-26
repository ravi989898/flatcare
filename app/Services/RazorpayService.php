<?php

namespace App\Services;

use App\Exceptions\PaymentVerificationFailedException;
use App\Models\Tenant\MaintenanceBill;
use App\Models\Tenant\Payment;
use App\Models\Tenant\RazorpayOrder;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Utility;
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
 */
class RazorpayService
{
    private ?Api $api = null;

    private function api(): Api
    {
        return $this->api ??= new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret'),
        );
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

        $amountPaise = (int) round($bill->balance * 100);

        try {
            $razorpayOrder = $this->api()->order->create([
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
            'razorpay_order_id' => $razorpayOrder->id,
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

            if ($order->status !== 'created') {
                // Replay guard #1: a captured order can never be re-claimed,
                // whether that's an accidental client retry or a deliberate
                // resubmission of an intercepted success response.
                throw new PaymentVerificationFailedException(
                    "Order {$orderId} is already '{$order->status}', refusing to re-verify.",
                );
            }

            $order->update(['status' => 'processing']);

            return $order;
        });

        // Phase 2 — verify against Razorpay's own servers. No DB lock is
        // held during this network I/O.
        try {
            (new Utility)->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);
        } catch (SignatureVerificationError $e) {
            $this->failOrder($order, 'signature_mismatch: ' . $e->getMessage());
            throw new PaymentVerificationFailedException("Signature verification failed for order {$orderId}: {$e->getMessage()}");
        }

        try {
            $payment = $this->api()->payment->fetch($paymentId);
        } catch (Throwable $e) {
            $this->failOrder($order, 'razorpay_fetch_failed: ' . $e->getMessage());
            throw new PaymentVerificationFailedException("Could not fetch payment {$paymentId} from Razorpay: {$e->getMessage()}");
        }

        // The signature alone proves Razorpay issued this pairing, not
        // that the payment is currently in a captured state (it could be
        // merely 'authorized', or later refunded) — this re-fetch is what
        // actually confirms the money moved.
        if (($payment->status ?? null) !== 'captured') {
            $this->failOrder($order, 'payment_status_not_captured: ' . ($payment->status ?? 'unknown'));
            throw new PaymentVerificationFailedException("Payment {$paymentId} status is '{$payment->status}', not captured.");
        }

        if (($payment->order_id ?? null) !== $order->razorpay_order_id) {
            $this->failOrder($order, 'order_id_mismatch');
            throw new PaymentVerificationFailedException("Payment {$paymentId}'s order_id does not match order {$order->razorpay_order_id}.");
        }

        // The concrete enforcement of "never trust a client-supplied
        // amount": this compares what Razorpay says was actually captured
        // against the amount *we* set when the order was created (itself
        // only ever derived from the bill's balance) — nothing here reads
        // an amount from the request at all.
        $expectedPaise = (int) round(((float) $order->amount) * 100);
        if ((int) $payment->amount !== $expectedPaise) {
            $this->failOrder($order, "amount_mismatch: expected {$expectedPaise}, got {$payment->amount}");
            throw new PaymentVerificationFailedException("Captured amount for payment {$paymentId} does not match the billed amount.");
        }

        // Phase 3 — finalize under a fresh lock.
        try {
            return DB::transaction(function () use ($order, $paymentId, $signature, $payment) {
                $order = RazorpayOrder::whereKey($order->id)->lockForUpdate()->first();

                if ($order->status !== 'processing') {
                    throw new PaymentVerificationFailedException(
                        "Order {$order->razorpay_order_id} unexpectedly in status '{$order->status}' during finalize.",
                    );
                }

                $paymentRow = Payment::create([
                    'bill_id' => $order->bill_id,
                    'amount' => $order->amount,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => 'online',
                    'reference_number' => $paymentId,
                    'notes' => 'Paid online via Razorpay.',
                    'recorded_by_user_id' => null,
                ]);

                $order->update([
                    'status' => 'paid',
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_signature' => $signature,
                    'payment_id' => $paymentRow->id,
                    'verified_at' => now(),
                    'meta' => $payment->toArray(),
                ]);

                $bill = $order->bill()->with('payments')->first();

                app(NotificationService::class)->notifyFlats(
                    [$bill->flat_id],
                    'payment_received',
                    'Payment received',
                    'Your payment of ₹' . number_format((float) $order->amount, 2) . " for \"{$bill->title}\" was received.",
                    ['bill_id' => $bill->id, 'payment_id' => $paymentRow->id],
                );

                return $bill;
            });
        } catch (QueryException $e) {
            // Belt-and-braces: the unique constraint on razorpay_payment_id
            // is a DB-level replay guard independent of the app-level
            // status check above — if it ever fires, treat it exactly like
            // any other verification failure rather than a 500.
            $this->failOrder($order, 'db_constraint_violation: ' . $e->getMessage());
            throw new PaymentVerificationFailedException("Finalizing payment {$paymentId} hit a database constraint: {$e->getMessage()}");
        }
    }

    private function failOrder(RazorpayOrder $order, string $reason): void
    {
        Log::warning('razorpay.verify_failed', [
            'order_id' => $order->razorpay_order_id,
            'bill_id' => $order->bill_id,
            'reason' => $reason,
        ]);

        $order->update(['status' => 'failed', 'failure_reason' => Str::limit($reason, 250)]);
    }
}
