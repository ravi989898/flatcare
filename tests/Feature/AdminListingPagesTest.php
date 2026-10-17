<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TenantService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every Super Admin listing page renders with the shared fc table look,
 * with and without search / filter / sort / rows-per-page. The society's
 * own screens (blocks, admins, security, residents) read a throwaway tenant
 * database; writes to the main database are rolled back.
 */
class AdminListingPagesTest extends TestCase
{
    private const DB = 'flatcare_test_admin_listing';

    private static bool $migrated = false;

    private int $societyId;
    private int $blockId;

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

        DB::connection('main')->beginTransaction();
        DB::connection('society')->beginTransaction();

        // The tenant connection already points at the test database.
        $this->app->instance(TenantService::class, new class extends TenantService
        {
            public function switchConnection(int $societyId): void {}
        });

        $now = now();
        $this->societyId = DB::connection('main')->table('societies')->insertGetId([
            'name' => 'Listing Test Society', 'slug' => 'listing-test-'.uniqid(), 'email' => uniqid().'@listing.test',
            'phone' => '9000000000', 'address' => 'Main Road', 'city' => 'Surat', 'state' => 'Gujarat', 'postal_code' => '395007',
            'start_date' => $now->copy()->subMonth()->toDateString(), 'end_date' => $now->copy()->addYear()->toDateString(),
            'db_name' => self::DB.'_'.uniqid(), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $tenant = DB::connection('society');
        $this->blockId = $tenant->table('blocks')->insertGetId(['name' => 'Block A', 'block_number' => 'A', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $tenant->table('flats')->insert(['block_id' => $this->blockId, 'flat_number' => 'A-101', 'floor_number' => '1', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $tenant->table('security_guards')->insert(['name' => 'Ram Singh', 'phone' => '9700000000', 'shift' => 'day', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);

        $admin = new User(['name' => 'Platform Admin', 'email' => 'platform@listing.test']);
        $admin->forceFill(['id' => 999999, 'role' => 'super_admin', 'email_verified_at' => $now]);
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        DB::connection('society')->rollBack();
        DB::connection('main')->rollBack();
        parent::tearDown();
    }

    public static function pages(): array
    {
        return [
            'societies' => ['/admin/societies', 'status=active&search=listing&sort=name&dir=asc&per_page=10'],
            'inquiries' => ['/admin/inquiries', 'status=new&search=x&sort=society&dir=asc'],
            'audit logs' => ['/admin/audit-logs', 'search=x&from=2026-01-01&to=2026-12-31&sort=when&dir=asc'],
            'permission logs' => ['/admin/permission-logs', 'search=x&sort=when&dir=asc'],
            'roles' => ['/admin/settings/roles', ''],
            'society admins' => ['/admin/societies/{society}/admins', 'search=x&status=active&sort=last_login&dir=desc'],
            'society blocks' => ['/admin/societies/{society}/blocks', 'search=A&status=active&sort=flats&dir=desc'],
            'society flats' => ['/admin/societies/{society}/blocks/{block}/flats', 'search=A&ownership_type=vacant&sort=floor&dir=asc'],
            'society security' => ['/admin/societies/{society}/security', 'search=ram&shift=day&status=active&sort=name&dir=asc'],
            'society security history' => ['/admin/societies/{society}/security-history', 'shift=night&sort=ended&dir=asc'],
            'society residents' => ['/admin/societies/{society}/users', 'search=nobody'],
        ];
    }

    #[DataProvider('pages')]
    public function test_admin_listing_page_renders(string $url, string $query): void
    {
        $url = str_replace(['{society}', '{block}'], [(string) $this->societyId, (string) $this->blockId], $url);

        $html = $this->get($url)->assertOk()->assertSee('fc-card', false)->getContent();
        if ($dir = getenv('LISTING_DUMP_DIR')) {
            file_put_contents(rtrim($dir, '/\\').'/'.trim(preg_replace('/[^a-z]+/', '-', $url), '-').'.html', $html);
        }

        if ($query !== '') {
            $this->get($url.'?'.$query)->assertOk();
        }
        $this->get($url.'?sort=nope&dir=up&per_page=7')->assertOk();
    }

    public function test_society_search_and_status_tab_filter_the_list(): void
    {
        $this->get('/admin/societies?search=Listing+Test')->assertOk()->assertSee('Listing Test Society');
        $this->get('/admin/societies?search=Listing+Test&status=expired')->assertOk()->assertDontSee('Listing Test Society');
        $this->get('/admin/societies/'.$this->societyId.'/security?search=ram')->assertOk()->assertSee('Ram Singh');
        $this->get('/admin/societies/'.$this->societyId.'/security?shift=night')->assertOk()->assertDontSee('Ram Singh');
    }
}
