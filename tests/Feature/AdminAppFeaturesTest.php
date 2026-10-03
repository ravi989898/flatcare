<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Society;
use App\Models\Tenant\MaintenanceBill;
use App\Models\Tenant\ResidentNotification;
use App\Models\Tenant\User;
use App\Models\Tenant\WaterReading;
use Closure;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The society-admin screens of the mobile app (/api/v1/admin/*): water
 * readings that generate bills, and the paid / pending list per flat.
 * Runs against a throwaway tenant database like the other API flow tests.
 */
class AdminAppFeaturesTest extends TestCase
{
    private const DB = 'flatcare_test_admin_app';

    private static bool $migrated = false;

    private string $originalDefault;
    private int $blockA;
    private int $flatA1;
    private int $flatA2;
    private User $admin;
    private User $resident;
    private Society $society;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.society.database' => self::DB, 'services.fcm.credentials' => null]);
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

        // What AuthenticateApiToken puts on the request: the society, with its billing rates.
        $society = $this->society = new Society(['name' => 'Test Society', 'fixed_maintenance' => 500, 'water_unit_rate' => 10]);
        $this->app->instance('test.api_society', new class($society)
        {
            public function __construct(private Society $society) {}

            public function handle(Request $request, Closure $next)
            {
                $request->attributes->set('api_society', $this->society);

                return $next($request);
            }
        });
        $this->app->make(Kernel::class)->pushMiddleware('test.api_society');

        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        DB::connection('society')->rollBack();
        DB::setDefaultConnection($this->originalDefault);
        parent::tearDown();
    }

    public function test_only_the_society_admin_can_use_the_admin_screens(): void
    {
        $this->as($this->resident);

        $this->getJson('/api/v1/admin/water-readings/blocks')->assertForbidden();
        $this->getJson('/api/v1/admin/water-readings')->assertForbidden();
        $this->postJson('/api/v1/admin/water-readings', ['month' => now()->format('Y-m'), 'readings' => []])->assertForbidden();
        $this->getJson('/api/v1/admin/payments')->assertForbidden();
        $this->getJson('/api/v1/admin/payments/periods')->assertForbidden();
    }

    public function test_app_permission_grants_an_admin_screen_to_another_role(): void
    {
        $main = DB::connection('main');
        $societyId = $main->table('societies')->value('id');
        if (!$societyId) {
            $this->markTestSkipped('Needs at least one society in the main database.');
        }

        $main->beginTransaction();
        try {
            $this->society->id = $societyId;
            $secretary = $this->makeUser('Secretary', '9100000200', 'secretary');

            // Not granted yet: hidden in the app menu and refused by the API.
            $this->as($secretary);
            $menu = $this->getJson('/api/v1/app-menu-items')->assertOk();
            $this->assertNotContains('app-water-readings', $menu->json('data.keys'));
            $this->assertContains('app-water-readings', $menu->json('data.managed'));
            $this->getJson('/api/v1/admin/water-readings/blocks')->assertForbidden();

            // Society Admin ticks Water Readings (only) for Secretary.
            $roleId = $main->table('role_definitions')->where('name', 'secretary')->value('id');
            foreach (['app-water-readings' => true, 'app-payment-status' => false] as $key => $visible) {
                $main->table('society_role_app_menu_item')->updateOrInsert(
                    ['society_id' => $societyId, 'role_definition_id' => $roleId, 'app_menu_item_id' => $main->table('app_menu_items')->where('key', $key)->value('id')],
                    ['is_visible' => $visible, 'created_at' => now(), 'updated_at' => now()],
                );
            }

            $this->assertSame(['app-water-readings'], $this->getJson('/api/v1/app-menu-items')->json('data.keys'));
            $this->getJson('/api/v1/admin/water-readings/blocks?month='.now()->format('Y-m'))->assertOk();
            $this->getJson('/api/v1/admin/payments/periods')->assertForbidden();

            // A plain resident of the same society still gets neither.
            $this->as($this->resident);
            $this->assertSame([], $this->getJson('/api/v1/app-menu-items')->json('data.keys'));
            $this->getJson('/api/v1/admin/water-readings/blocks')->assertForbidden();

            // The Society Admin always gets both.
            $this->as($this->admin);
            $this->assertSame(['app-water-readings', 'app-payment-status'], $this->getJson('/api/v1/app-menu-items')->json('data.keys'));
        } finally {
            $main->rollBack();
        }
    }

    public function test_vehicles_screen_lists_every_residents_vehicles_by_block(): void
    {
        $db = DB::connection('society');
        $now = now();
        $vehicle = fn (int $userId, string $type, string $number) => $db->table('vehicles')->insert([
            'user_id' => $userId, 'vehicle_type' => $type, 'registration_number' => $number,
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $tenant = $this->makeUser('Jatish (A-102)', '9100000002', 'resident');
        $db->table('flat_residents')->insert([
            'flat_id' => $this->flatA2, 'user_id' => $tenant->id, 'resident_type' => 'tenant',
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $vehicle($this->resident->id, 'Car', 'GJ 18 BG 4449');
        $vehicle($this->resident->id, 'scooter', 'GJ 27 DV 3309');
        $vehicle($tenant->id, 'Bike', 'GJ 27 FT 2377');

        // A second block whose residents must not show up under Block A.
        $blockB = $db->table('blocks')->insertGetId(['name' => 'Block B', 'block_number' => 'B', 'created_at' => $now, 'updated_at' => $now]);
        $flatB1 = $db->table('flats')->insertGetId(['block_id' => $blockB, 'flat_number' => 'B-101', 'floor_number' => '1', 'created_at' => $now, 'updated_at' => $now]);
        $other = $this->makeUser('Pravin (B-101)', '9100000003', 'resident');
        $db->table('flat_residents')->insert([
            'flat_id' => $flatB1, 'user_id' => $other->id, 'resident_type' => 'owner',
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $vehicle($other->id, 'Car', 'GJ 05 NK 7455');

        $this->as($this->resident);

        $res = $this->getJson("/api/v1/society-vehicles?block_id={$this->blockA}")->assertOk();
        $res->assertJsonPath('data.blocks.0.name', 'Block A')
            ->assertJsonPath('data.blocks.1.name', 'Block B')
            ->assertJsonCount(2, 'data.residents')
            ->assertJsonPath('data.residents.0.flat_number', 'A-101')
            ->assertJsonPath('data.residents.0.phone', '9100000001')
            ->assertJsonPath('data.residents.0.resident_type', 'owner')
            ->assertJsonCount(2, 'data.residents.0.vehicles')
            ->assertJsonPath('data.residents.1.resident_type', 'tenant');
        $counts = collect($res->json('data.counts'))->pluck('count', 'type')->all();
        $this->assertEquals(['Car' => 1, 'Scooter' => 1, 'Bike' => 1], $counts);

        // Search by vehicle number ignores spaces; counts stay for the whole block.
        $this->getJson("/api/v1/society-vehicles?block_id={$this->blockA}&search=gj27ft")
            ->assertJsonCount(1, 'data.residents')
            ->assertJsonPath('data.residents.0.name', 'Jatish (A-102)')
            ->assertJsonCount(3, 'data.counts');

        $this->getJson("/api/v1/society-vehicles?block_id={$blockB}")
            ->assertJsonCount(1, 'data.residents')
            ->assertJsonPath('data.residents.0.vehicles.0.registration_number', 'GJ 05 NK 7455');
    }

    public function test_admin_enters_readings_and_each_flat_is_billed_and_notified(): void
    {
        $month = now()->format('Y-m');
        $this->as($this->admin);

        $this->getJson("/api/v1/admin/water-readings/blocks?month={$month}")
            ->assertOk()
            ->assertJsonPath('data.rates.water_unit_rate', 10)
            ->assertJsonPath('data.blocks.0.flats_count', 2)
            ->assertJsonPath('data.blocks.0.entered_count', 0);

        $this->postJson('/api/v1/admin/water-readings', ['month' => $month, 'readings' => [
            ['flat_id' => $this->flatA1, 'previous_reading' => 100, 'current_reading' => 130],
            ['flat_id' => $this->flatA2, 'previous_reading' => 50, 'current_reading' => null], // not read yet
        ]])->assertOk()->assertJsonPath('data.billed', 1);

        $bill = MaintenanceBill::where('flat_id', $this->flatA1)->sole();
        $this->assertEquals(800.0, (float) $bill->amount); // 30 units x 10 + 500 fixed
        $this->assertSame(0, MaintenanceBill::where('flat_id', $this->flatA2)->count());
        $this->assertSame(1, ResidentNotification::where('user_id', $this->resident->id)->where('type', 'maintenance_due')->count());

        $this->getJson("/api/v1/admin/water-readings?month={$month}&block_id={$this->blockA}")
            ->assertOk()
            ->assertJsonPath('data.rows.0.flat_number', 'A-101')
            ->assertJsonPath('data.rows.0.current_reading', 130)
            ->assertJsonPath('data.rows.0.bill_amount', 800)
            ->assertJsonPath('data.rows.0.locked', false)
            ->assertJsonPath('data.rows.1.current_reading', null);
    }

    public function test_next_month_carries_the_previous_reading_over(): void
    {
        $this->as($this->admin);
        $last = now()->subMonthNoOverflow()->format('Y-m');
        $this->postJson('/api/v1/admin/water-readings', ['month' => $last, 'readings' => [
            ['flat_id' => $this->flatA1, 'previous_reading' => 100, 'current_reading' => 130],
        ]])->assertOk();

        $this->getJson('/api/v1/admin/water-readings?month='.now()->format('Y-m')."&block_id={$this->blockA}")
            ->assertJsonPath('data.rows.0.previous_reading', 130)
            ->assertJsonPath('data.rows.0.has_history', true);

        // The carried-over reading is used even if the app sends something else.
        $this->postJson('/api/v1/admin/water-readings', ['month' => now()->format('Y-m'), 'readings' => [
            ['flat_id' => $this->flatA1, 'previous_reading' => 0, 'current_reading' => 140],
        ]])->assertOk();
        $this->assertEquals(600.0, (float) MaintenanceBill::where('title', now()->format('F Y').' Maintenance')->sole()->amount);
    }

    public function test_bad_readings_are_rejected_and_nothing_is_saved(): void
    {
        $month = now()->format('Y-m');
        $this->as($this->admin);

        $this->postJson('/api/v1/admin/water-readings', ['month' => $month, 'readings' => [
            ['flat_id' => $this->flatA1, 'previous_reading' => 100, 'current_reading' => 130],
            ['flat_id' => $this->flatA2, 'previous_reading' => null, 'current_reading' => 70], // first reading, no previous
        ]])->assertStatus(422)->assertJsonPath('message', 'Flat Block A / A-102: enter the previous reading - it\'s this flat\'s first reading.');

        $this->postJson('/api/v1/admin/water-readings', ['month' => $month, 'readings' => [
            ['flat_id' => $this->flatA1, 'previous_reading' => 100, 'current_reading' => 90],
        ]])->assertStatus(422);

        $this->postJson('/api/v1/admin/water-readings', ['month' => now()->addMonthNoOverflow()->format('Y-m'), 'readings' => [
            ['flat_id' => $this->flatA1, 'previous_reading' => 100, 'current_reading' => 130],
        ]])->assertStatus(422);

        $this->assertSame(0, WaterReading::count());
        $this->assertSame(0, MaintenanceBill::count());
    }

    public function test_a_reading_can_be_corrected_until_its_bill_is_paid(): void
    {
        $month = now()->format('Y-m');
        $this->as($this->admin);
        $save = fn (float $current) => $this->postJson('/api/v1/admin/water-readings', ['month' => $month, 'readings' => [
            ['flat_id' => $this->flatA1, 'previous_reading' => 100, 'current_reading' => $current],
        ]]);

        $save(130)->assertOk();
        $save(120)->assertOk(); // typo fixed
        $bill = MaintenanceBill::sole();
        $this->assertEquals(700.0, (float) $bill->amount);
        $this->assertSame(1, ResidentNotification::where('type', 'maintenance_due')->count()); // not notified twice

        DB::table('payments')->insert([
            'bill_id' => $bill->id, 'amount' => 700, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $save(150)->assertStatus(422)->assertJsonPath('message', "Flat Block A / A-101: this month's bill is already paid, so its reading can't be changed.");
        $this->assertEquals(700.0, (float) $bill->fresh()->amount);

        $this->getJson("/api/v1/admin/water-readings?month={$month}&block_id={$this->blockA}")->assertJsonPath('data.rows.0.locked', true);
    }

    public function test_payment_list_shows_who_paid_and_who_still_owes_by_flat(): void
    {
        $this->as($this->admin);
        $paid = $this->bill($this->flatA1, 'October 2026 Maintenance', 800);
        $this->bill($this->flatA2, 'October 2026 Maintenance', 650);
        $this->bill($this->flatA1, 'September 2026 Maintenance', 700, now()->subMonth());
        DB::table('payments')->insert([
            'bill_id' => $paid, 'amount' => 800, 'payment_date' => now()->toDateString(), 'payment_method' => 'online',
            'reference_number' => 'pay_123', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/admin/payments/periods')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'October 2026 Maintenance')
            ->assertJsonPath('data.0.bills', 2);

        $this->getJson('/api/v1/admin/payments') // newest run by default
            ->assertOk()
            ->assertJsonPath('data.title', 'October 2026 Maintenance')
            ->assertJsonPath('data.summary.paid_count', 1)
            ->assertJsonPath('data.summary.pending_count', 1)
            ->assertJsonPath('data.summary.total_collected', 800)
            ->assertJsonPath('data.summary.total_pending', 650)
            ->assertJsonPath('data.items.0.flat_number', 'A-102') // pending first
            ->assertJsonPath('data.items.0.balance', 650)
            ->assertJsonPath('data.items.1.flat_number', 'A-101')
            ->assertJsonPath('data.items.1.status', 'paid')
            ->assertJsonPath('data.items.1.payment_method', 'online')
            ->assertJsonPath('data.items.1.resident_name', 'Asha (A-101)');

        $this->getJson('/api/v1/admin/payments?status=pending')->assertJsonCount(1, 'data.items');
        $this->getJson('/api/v1/admin/payments?search=a-101')->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.flat_number', 'A-101');
        $this->getJson('/api/v1/admin/payments?title='.urlencode('September 2026 Maintenance'))->assertJsonCount(1, 'data.items');
    }

    // ---------------------------------------------------------------- helpers

    private function as(User $user): static
    {
        Auth::guard('society')->setUser($user);

        return $this;
    }

    private function bill(int $flatId, string $title, float $amount, ?\DateTimeInterface $due = null): int
    {
        return DB::connection('society')->table('maintenance_bills')->insertGetId([
            'flat_id' => $flatId, 'title' => $title, 'amount' => $amount,
            'due_date' => ($due ?? now()->addDays(5))->format('Y-m-d'), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeUser(string $name, string $phone, string $role): User
    {
        $db = DB::connection('society');
        $now = now();
        $u = User::create(['name' => $name, 'email' => "{$phone}@example.test", 'phone' => $phone, 'password' => 'x', 'status' => 'active']);
        $roleId = $db->table('roles')->where('name', $role)->value('id')
            ?? $db->table('roles')->insertGetId(['name' => $role, 'display_name' => ucfirst($role), 'created_at' => $now, 'updated_at' => $now]);
        $db->table('role_user')->insert(['user_id' => $u->id, 'role_id' => $roleId, 'created_at' => $now, 'updated_at' => $now]);

        return $u;
    }

    private function seedFixtures(): void
    {
        $db = DB::connection('society');
        $now = now();

        $this->blockA = $db->table('blocks')->insertGetId(['name' => 'Block A', 'block_number' => 'A', 'created_at' => $now, 'updated_at' => $now]);
        $flat = fn (string $number) => $db->table('flats')->insertGetId([
            'block_id' => $this->blockA, 'flat_number' => $number, 'floor_number' => '1', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->flatA1 = $flat('A-101');
        $this->flatA2 = $flat('A-102');

        $this->admin = $this->makeUser('Society Admin', '9100000100', 'admin');
        $this->resident = $this->makeUser('Asha (A-101)', '9100000001', 'resident');

        $db->table('flat_residents')->insert([
            'flat_id' => $this->flatA1, 'user_id' => $this->resident->id, 'resident_type' => 'owner',
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}
