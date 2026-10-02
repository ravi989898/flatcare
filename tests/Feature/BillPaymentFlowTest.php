<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Tenant\Payment;
use App\Models\Tenant\RazorpayOrder;
use App\Models\Tenant\User;
use App\Services\RazorpayService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * End-to-end tests of online bill payment (Razorpay) through the real API
 * endpoints and RazorpayService, against a throwaway tenant database. Only
 * the calls to Razorpay's servers are faked (see fakeRazorpay()), so the
 * signature check, amount/order checks, capture and reconciliation all run
 * for real.
 *
 * Needs the local MySQL server; creates/migrates `flatcare_test_payments`
 * once per run and rolls every test back.
 */
class BillPaymentFlowTest extends TestCase
{
    private const DB = 'flatcare_test_payments';
    private const SECRET = 'test_secret_123';

    private static bool $migrated = false;

    private string $originalDefault;
    private int $flatA;
    private int $flatB;
    private User $residentA;
    private User $residentB;

    /** @var object{payments: array, orderPayments: array, captured: array, fetchFails: bool, orderSeq: int, keys: ?array} */
    private object $razorpay;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.society.database' => self::DB]);
        DB::purge('society');

        if (!self::$migrated) {
            DB::connection()->statement('CREATE DATABASE IF NOT EXISTS `'.self::DB.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            Artisan::call('migrate:fresh', ['--database' => 'society', '--path' => 'database/migrations/tenant', '--force' => true]);
            self::$migrated = true;
        }

        $this->originalDefault = DB::getDefaultConnection();
        DB::setDefaultConnection('society');
        DB::connection('society')->beginTransaction();

        $this->withoutMiddleware([AuthenticateApiToken::class, 'throttle:api-auth']);

        config(['services.fcm.credentials' => null]);
        Cache::flush();

        $this->fakeRazorpay();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        DB::connection('society')->rollBack();
        DB::setDefaultConnection($this->originalDefault);
        parent::tearDown();
    }

    public function test_a_resident_pays_a_bill_and_it_is_marked_paid(): void
    {
        $billId = $this->bill($this->flatA, 1500.50);

        $order = $this->as($this->residentA)->postJson("/api/v1/bills/{$billId}/pay/order")
            ->assertOk()
            ->assertJsonPath('data.amount', 150050)
            ->assertJsonPath('data.key', 'rzp_test_key')
            ->json('data');

        $paymentId = $this->razorpayTakesPayment($order['razorpay_order_id'], 150050);

        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($order['razorpay_order_id'], $paymentId))
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $payment = Payment::where('bill_id', $billId)->sole();
        $this->assertSame('online', $payment->payment_method);
        $this->assertSame($paymentId, $payment->reference_number);
        $this->assertEquals(1500.50, (float) $payment->amount);
        $this->assertSame('paid', RazorpayOrder::where('razorpay_order_id', $order['razorpay_order_id'])->value('status'));
    }

    public function test_a_society_without_razorpay_keys_cannot_take_online_payments(): void
    {
        $this->razorpay->keys = null;
        $billId = $this->bill($this->flatA, 500);

        $this->as($this->residentA)->postJson("/api/v1/bills/{$billId}/pay/order")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Online payment is not available for your society yet. Please pay at the society office.');

        $this->assertSame(0, RazorpayOrder::count());
    }

    public function test_a_payment_signed_with_another_societys_secret_is_rejected(): void
    {
        $billId = $this->bill($this->flatA, 500);
        $orderId = $this->createOrder($billId);
        $paymentId = $this->razorpayTakesPayment($orderId, 50000);

        $this->razorpay->keys = ['rzp_test_other', 'other_society_secret'];
        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $paymentId))->assertStatus(422);

        $this->assertSame(0, Payment::where('bill_id', $billId)->count());
    }

    public function test_an_authorized_payment_is_captured_before_it_is_recorded(): void
    {
        $billId = $this->bill($this->flatA, 999);
        $orderId = $this->createOrder($billId);
        $paymentId = $this->razorpayTakesPayment($orderId, 99900, 'authorized');

        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $paymentId))
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertSame([[$paymentId, 99900]], $this->razorpay->captured);
    }

    public function test_a_forged_signature_is_rejected_and_nothing_is_recorded(): void
    {
        $billId = $this->bill($this->flatA, 500);
        $orderId = $this->createOrder($billId);
        $paymentId = $this->razorpayTakesPayment($orderId, 50000);

        $this->postJson("/api/v1/bills/{$billId}/pay/verify", [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => hash_hmac('sha256', "{$orderId}|{$paymentId}", 'wrong-secret'),
        ])->assertStatus(422);

        $this->assertSame(0, Payment::where('bill_id', $billId)->count());
    }

    public function test_a_payment_for_a_different_amount_is_rejected(): void
    {
        $billId = $this->bill($this->flatA, 500);
        $orderId = $this->createOrder($billId);
        $paymentId = $this->razorpayTakesPayment($orderId, 100);

        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $paymentId))
            ->assertStatus(422);

        $this->assertSame(0, Payment::where('bill_id', $billId)->count());
    }

    public function test_another_flats_resident_cannot_pay_or_verify_the_bill(): void
    {
        $billId = $this->bill($this->flatA, 500);
        $orderId = $this->createOrder($billId);
        $paymentId = $this->razorpayTakesPayment($orderId, 50000);

        $this->as($this->residentB)->postJson("/api/v1/bills/{$billId}/pay/order")->assertNotFound();
        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $paymentId))->assertStatus(422);

        $this->assertSame(0, Payment::where('bill_id', $billId)->count());
    }

    public function test_verifying_the_same_payment_twice_records_it_once(): void
    {
        $billId = $this->bill($this->flatA, 500);
        $orderId = $this->createOrder($billId);
        $paymentId = $this->razorpayTakesPayment($orderId, 50000);

        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $paymentId))->assertOk();
        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $paymentId))
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertSame(1, Payment::where('bill_id', $billId)->count());
    }

    public function test_a_paid_bill_cannot_start_another_payment(): void
    {
        $billId = $this->bill($this->flatA, 500);
        $orderId = $this->createOrder($billId);
        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $this->razorpayTakesPayment($orderId, 50000)))->assertOk();

        $this->postJson("/api/v1/bills/{$billId}/pay/order")->assertStatus(422);
    }

    public function test_money_taken_but_never_confirmed_by_the_app_shows_up_when_the_bill_is_opened(): void
    {
        $billId = $this->bill($this->flatA, 750);
        $orderId = $this->createOrder($billId);
        $this->razorpayTakesPayment($orderId, 75000);
        // ...and the app closes before calling pay/verify.

        $this->getJson("/api/v1/bills/{$billId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertSame(1, Payment::where('bill_id', $billId)->count());
    }

    public function test_a_verify_that_hit_a_network_error_is_settled_by_the_scheduled_reconcile(): void
    {
        $billId = $this->bill($this->flatA, 750);
        $orderId = $this->createOrder($billId);
        $paymentId = $this->razorpayTakesPayment($orderId, 75000);

        $this->razorpay->fetchFails = true;
        $this->postJson("/api/v1/bills/{$billId}/pay/verify", $this->checkoutResult($orderId, $paymentId))->assertStatus(422);
        $this->assertSame('failed', RazorpayOrder::where('razorpay_order_id', $orderId)->value('status'));

        $this->razorpay->fetchFails = false;
        app(RazorpayService::class)->reconcileOrder(RazorpayOrder::where('razorpay_order_id', $orderId)->sole());

        $this->assertSame('paid', RazorpayOrder::where('razorpay_order_id', $orderId)->value('status'));
        $this->assertSame(1, Payment::where('bill_id', $billId)->count());
    }

    public function test_reconcile_ignores_abandoned_failed_and_wrong_amount_payments(): void
    {
        $billId = $this->bill($this->flatA, 750);
        $this->createOrder($billId); // checkout opened and closed without paying
        $failed = $this->createOrder($billId);
        $this->razorpayTakesPayment($failed, 75000, 'failed');
        $short = $this->createOrder($billId);
        $this->razorpayTakesPayment($short, 100);

        $this->getJson("/api/v1/bills/{$billId}")->assertOk()->assertJsonPath('data.status', 'unpaid');

        $this->assertSame(0, Payment::where('bill_id', $billId)->count());
        $this->assertSame([], $this->razorpay->captured);
    }

    // ---------------------------------------------------------------- helpers

    private function createOrder(int $billId): string
    {
        return $this->as($this->residentA)->postJson("/api/v1/bills/{$billId}/pay/order")->assertOk()->json('data.razorpay_order_id');
    }

    /** Razorpay Checkout completing on the phone: a payment now exists at Razorpay for the order. */
    private function razorpayTakesPayment(string $orderId, int $amountPaise, string $status = 'captured'): string
    {
        $paymentId = 'pay_'.bin2hex(random_bytes(7));
        $payment = ['id' => $paymentId, 'entity' => 'payment', 'order_id' => $orderId, 'amount' => $amountPaise, 'currency' => 'INR', 'status' => $status];

        $this->razorpay->payments[$paymentId] = $payment;
        $this->razorpay->orderPayments[$orderId][] = $paymentId;

        return $paymentId;
    }

    /** @return array<string, string> what the Razorpay SDK hands the app on success */
    private function checkoutResult(string $orderId, string $paymentId): array
    {
        return [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => hash_hmac('sha256', "{$orderId}|{$paymentId}", self::SECRET),
        ];
    }

    private function fakeRazorpay(): void
    {
        // The society's own Razorpay keys (what the Super Admin saved).
        $state = $this->razorpay = (object) ['payments' => [], 'orderPayments' => [], 'captured' => [], 'fetchFails' => false, 'orderSeq' => 0, 'keys' => ['rzp_test_key', self::SECRET]];

        $this->app->instance(RazorpayService::class, new class($state) extends RazorpayService
        {
            public function __construct(private object $state) {}

            protected function credentials(): ?array
            {
                return $this->state->keys;
            }

            protected function createRemoteOrder(array $attributes): string
            {
                return 'order_test'.(++$this->state->orderSeq).bin2hex(random_bytes(3));
            }

            protected function fetchPayment(string $paymentId): array
            {
                if ($this->state->fetchFails) {
                    throw new RuntimeException('cURL error 28: timed out');
                }

                return $this->state->payments[$paymentId] ?? throw new RuntimeException('The id provided does not exist');
            }

            protected function capturePayment(string $paymentId, int $amountPaise, string $currency): array
            {
                $this->state->captured[] = [$paymentId, $amountPaise];

                return $this->state->payments[$paymentId] = ['status' => 'captured'] + $this->state->payments[$paymentId];
            }

            protected function fetchOrderPayments(string $orderId): array
            {
                return array_map(fn ($id) => $this->state->payments[$id], $this->state->orderPayments[$orderId] ?? []);
            }
        });
    }

    private function as(User $user): static
    {
        Auth::guard('society')->setUser($user);

        return $this;
    }

    private function bill(int $flatId, float $amount): int
    {
        return DB::connection('society')->table('maintenance_bills')->insertGetId([
            'flat_id' => $flatId,
            'title' => 'October 2026 Maintenance',
            'amount' => $amount,
            'due_date' => now()->addDays(10)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedFixtures(): void
    {
        $db = DB::connection('society');
        $now = now();

        $blockId = $db->table('blocks')->insertGetId(['name' => 'Block A', 'block_number' => 'A', 'created_at' => $now, 'updated_at' => $now]);
        $flat = fn (string $number) => $db->table('flats')->insertGetId([
            'block_id' => $blockId, 'flat_number' => $number, 'floor_number' => '1', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->flatA = $flat('A-101');
        $this->flatB = $flat('A-102');

        $user = fn (string $name, string $phone): User => User::create(['name' => $name, 'email' => "{$phone}@example.test", 'phone' => $phone, 'password' => 'x', 'status' => 'active']);
        $this->residentA = $user('Asha (A-101)', '9100000001');
        $this->residentB = $user('Bina (A-102)', '9100000003');

        foreach ([[$this->flatA, $this->residentA], [$this->flatB, $this->residentB]] as [$flatId, $resident]) {
            $db->table('flat_residents')->insert([
                'flat_id' => $flatId, 'user_id' => $resident->id, 'resident_type' => 'owner',
                'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
