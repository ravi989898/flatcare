<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMenuItemVisible;
use App\Http\Middleware\SetSocietyContext;
use App\Models\Society;
use App\Models\Tenant\User;
use Closure;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every Society-portal listing page renders - empty and with rows, and with
 * search / filter / sort / rows-per-page in the query string (the shared
 * resources/views/components/fc toolbar, headers and footer, and
 * App\Support\ListQuery behind them). Runs against a throwaway tenant
 * database; set LISTING_DUMP_DIR to also save each page's HTML.
 */
class ListingPagesTest extends TestCase
{
    private const DB = 'flatcare_test_listing_pages';

    private static bool $migrated = false;

    private string $originalDefault;
    private User $admin;
    private ?int $blockA = null;

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

        $this->admin = $this->makeUser('Society Admin', '9100000100', 'admin');

        // What SetSocietyContext does for a signed-in society admin, minus
        // the session lookup and the per-role menu.
        $this->withoutMiddleware([SetSocietyContext::class, EnsureMenuItemVisible::class]);
        $society = new Society(['name' => 'Test Society', 'fixed_maintenance' => 500, 'water_unit_rate' => 10]);
        $society->id = 1;
        $admin = $this->admin;
        $this->app->instance('test.society_context', new class($society, $admin)
        {
            public function __construct(private Society $society, private User $admin) {}

            public function handle(Request $request, Closure $next)
            {
                Auth::guard('society')->setUser($this->admin);
                Auth::shouldUse('society');
                $request->attributes->set('society', $this->society);
                view()->share('currentSociety', $this->society);
                view()->share('visibleMenuItems', collect());
                config(['adminlte.menu' => [], 'adminlte.logout_url' => 'society.logout', 'adminlte.dashboard_url' => 'society.dashboard']);

                return $next($request);
            }
        });
        $this->app->make(Kernel::class)->pushMiddleware('test.society_context');
    }

    protected function tearDown(): void
    {
        DB::connection('society')->rollBack();
        DB::setDefaultConnection($this->originalDefault);
        parent::tearDown();
    }

    public static function pages(): array
    {
        return [
            'water readings' => ['/society/water-readings', 'search=101&sort=amount&dir=asc&per_page=10'],
            'blocks' => ['/society/blocks', 'search=A&status=active&sort=flats&dir=desc'],
            'flats' => ['/society/blocks/{block}/flats', 'search=A-1&ownership_type=vacant&status=active&sort=owner&dir=asc'],
            'admins' => ['/society/admins', 'search=admin&role=admin&status=active&sort=last_login&dir=desc'],
            'directory' => ['/society/directory', 'search=asha&resident_type=owner&sort=flat&dir=asc'],
            'complaints' => ['/society/complaints', 'status=open&priority=high&search=lift&sort=priority&dir=desc'],
            'documents' => ['/society/documents', 'category=notice&search=agm&sort=title&dir=asc'],
            'elections' => ['/society/elections', 'status=draft&search=committee&sort=votes&dir=desc'],
            'emergency contacts' => ['/society/emergency-contacts', ''],
            'fee types' => ['/society/fee-types', ''],
            'service providers' => ['/society/service-providers', ''],
            'polls' => ['/society/polls', 'status=open&search=gym&sort=question&dir=asc'],
            'visitors' => ['/society/visitors', 'status=all&purpose=delivery&search=ravi&sort=name&dir=asc'],
            'security' => ['/society/security', 'shift=day&status=active&search=ram&sort=name&dir=asc'],
            'security history' => ['/society/security/history', 'shift=night&search=ram&sort=ended&dir=asc'],
            'payments' => ['/society/payments', 'status=unpaid&block_id=1&search=A-1&sort=balance&dir=desc'],
            'extra charges' => ['/society/extra-charges', 'status=overdue&search=hall&sort=amount&dir=asc'],
            'water extra charges' => ['/society/water-extra-charges', 'status=active&search=lift&sort=amount&dir=desc'],
            'payment report' => ['/society/reports/payments', 'status=paid&from=2026-01-01&to=2026-12-31&search=A&sort=flat&dir=asc'],
        ];
    }

    #[DataProvider('pages')]
    public function test_listing_page_renders_empty_and_with_rows(string $url, string $query): void
    {
        if (str_contains($url, '{block}')) {
            $emptyBlock = DB::connection('society')->table('blocks')->insertGetId(['name' => 'Empty', 'block_number' => 'E', 'created_at' => now(), 'updated_at' => now()]);
            $this->get(str_replace('{block}', $emptyBlock, $url))->assertOk();
        } else {
            $this->get($url)->assertOk();
        }

        $this->seedFixtures();
        $url = str_replace('{block}', (string) $this->blockA, $url);

        $html = $this->get($url)->assertOk()->assertSee('fc-table', false)->getContent();
        $this->dump($url, $html);

        $this->get($url.'?'.$query)->assertOk();
        $this->get($url.'?sort=not-a-column&dir=sideways&per_page=9999')->assertOk();
    }

    public function test_search_filter_and_sort_narrow_and_order_the_rows(): void
    {
        $this->seedFixtures();

        $this->get('/society/water-readings?search=A-102')
            ->assertOk()->assertSee('A-102')->assertDontSee('>A-101<', false);

        $this->get('/society/water-readings?sort=amount&dir=asc')
            ->assertOk()->assertSeeInOrder(['₹700.00', '₹750.00']);

        $this->get('/society/water-readings?sort=amount&dir=desc')
            ->assertOk()->assertSeeInOrder(['₹750.00', '₹700.00']);

        $this->get('/society/water-readings?search=nothing-matches')
            ->assertOk()->assertSee('No results found');

        $this->get('/society/complaints?status=open&search=lift')->assertOk()->assertSee('Lift not working');
        $this->get('/society/complaints?status=resolved')->assertOk()->assertDontSee('Lift not working');

        $this->get('/society/visitors?status=all&search=ravi')->assertOk()->assertSee('Ravi Courier');
        $this->get('/society/visitors?status=all&purpose=guest')->assertOk()->assertDontSee('Ravi Courier');

        $this->get('/society/payments?status=paid')->assertOk()->assertSee('₹700.00')->assertDontSee('₹750.00');
        $this->get('/society/payments?search=A-102')->assertOk()->assertSee('₹750.00')->assertDontSee('₹700.00');
        $this->get('/society/extra-charges?status=overdue')->assertOk()->assertSee('Hall Booking');

        $this->get('/society/directory?search=asha')->assertOk()->assertSee('Asha Patel');
        $this->get('/society/admins?search=asha')->assertOk()->assertDontSee('Asha Patel');
    }

    // ---------------------------------------------------------------- helpers

    private function dump(string $url, string $html): void
    {
        $dir = getenv('LISTING_DUMP_DIR');
        if ($dir) {
            file_put_contents(rtrim($dir, '/\\').'/'.trim(str_replace('/', '-', $url), '-').'.html', $html);
        }
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
        $stamp = ['created_at' => $now, 'updated_at' => $now];

        $blockA = $this->blockA = $db->table('blocks')->insertGetId(['name' => 'Block A', 'block_number' => 'A', 'status' => 'active'] + $stamp);
        $blockB = $db->table('blocks')->insertGetId(['name' => 'Block B', 'block_number' => 'B', 'status' => 'active'] + $stamp);
        $flat = fn (int $block, string $number) => $db->table('flats')->insertGetId([
            'block_id' => $block, 'flat_number' => $number, 'floor_number' => '1', 'status' => 'active',
        ] + $stamp);
        $a1 = $flat($blockA, 'A-101');
        $a2 = $flat($blockA, 'A-102');
        $flat($blockB, 'B-201');

        $reading = fn (int $flatId, string $month, float $prev, float $cur) => $db->table('water_readings')->insertGetId([
            'flat_id' => $flatId, 'reading_month' => $month, 'previous_reading' => $prev, 'current_reading' => $cur,
            'recorded_by_user_id' => $this->admin->id,
        ] + $stamp);
        $bill = fn (int $flatId, int $readingId, float $amount) => $db->table('maintenance_bills')->insertGetId([
            'flat_id' => $flatId, 'water_reading_id' => $readingId, 'title' => 'October 2026 Maintenance',
            'amount' => $amount, 'due_date' => now()->addDays(10)->toDateString(),
        ] + $stamp);

        $paidBill = $bill($a1, $reading($a1, '2026-10-01', 100, 120), 700);
        $bill($a2, $reading($a2, '2026-10-01', 100, 125), 750);
        $db->table('payments')->insert([
            'bill_id' => $paidBill, 'amount' => 700, 'payment_date' => $now->toDateString(), 'payment_method' => 'cash',
        ] + $stamp);

        // One row for every other listing page.
        $resident = $this->makeUser('Asha Patel', '9100000001', 'resident');
        $db->table('flat_residents')->insert([
            'flat_id' => $a1, 'user_id' => $resident->id, 'resident_type' => 'owner', 'status' => 'active', 'is_primary' => true,
        ] + $stamp);

        $feeType = $db->table('fee_types')->insertGetId(['name' => 'Hall Booking', 'default_amount' => 1500] + $stamp);
        $db->table('maintenance_bills')->insert([
            'flat_id' => $a2, 'fee_type_id' => $feeType, 'title' => 'Hall Booking', 'amount' => 1500,
            'due_date' => now()->subDays(3)->toDateString(),
        ] + $stamp);

        $db->table('complaints')->insert([
            'flat_id' => $a1, 'category' => \App\Models\Tenant\Complaint::CATEGORIES[0], 'subject' => 'Lift not working',
            'description' => 'Stuck on 3rd floor', 'raised_by_name' => 'Asha Patel', 'priority' => 'high', 'status' => 'open',
        ] + $stamp);
        $db->table('visitors')->insert([
            'flat_id' => $a1, 'visitor_name' => 'Ravi Courier', 'visitor_phone' => '9800000000', 'purpose' => 'delivery',
            'status' => 'checked_in', 'check_in_at' => $now,
        ] + $stamp);
        $db->table('documents')->insert([
            'title' => 'AGM Minutes', 'category' => 'notice', 'file_path' => 'documents/agm.pdf', 'file_name' => 'agm.pdf',
        ] + $stamp);
        $db->table('elections')->insert(['title' => 'Committee Election 2026', 'status' => 'draft'] + $stamp);
        $poll = $db->table('polls')->insertGetId(['question' => 'Should we open the gym at 5 AM?', 'status' => 'open'] + $stamp);
        $db->table('poll_options')->insert(['poll_id' => $poll, 'label' => 'Yes'] + $stamp);
        $guard = $db->table('security_guards')->insertGetId(['name' => 'Ram Singh', 'phone' => '9700000000', 'shift' => 'day', 'status' => 'active'] + $stamp);
        $db->table('security_guard_logs')->insert(['security_guard_id' => $guard, 'shift' => 'day', 'started_at' => $now] + $stamp);
        $db->table('emergency_contacts')->insert(['label' => 'Ambulance', 'type' => 'ambulance', 'phone' => '108'] + $stamp);
        $db->table('service_providers')->insert(['name' => 'Mahesh', 'service_type' => 'Plumber', 'phone' => '9600000000'] + $stamp);
        $db->table('water_extra_charges')->insert([
            'amount' => 50, 'start_date' => now()->startOfMonth()->toDateString(), 'remarks' => 'Lift AMC', 'is_active' => true,
        ] + $stamp);
    }
}
