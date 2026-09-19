<?php
// Multi-society security test fixtures. Creates a throw-away "Society B" (own
// database) plus test accounts in A and B. Everything is prefixed "sectest" and
// removed again by multi_society_cleanup.php. Nothing existing is modified except adding
// rows to Society A's tenant DB (also removed afterwards).

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Society;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\SecurityGuard;
use App\Models\Tenant\User;
use App\Services\Api\ApiTokenService;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$ts = app(TenantService::class);
$pw = 'SecTest#Passw0rd!';

// ---- Society B -------------------------------------------------------------
$b = Society::where('slug', 'sectest-b')->first();
if (! $b) {
    $b = Society::create([
        'name' => 'SecTest Society B', 'slug' => 'sectest-b', 'db_name' => 'pending-sectest-b',
        'email' => 'sectest-b@example.test', 'phone' => '9000000000', 'address' => 'Test Road', 'city' => 'Ahmedabad',
        'state' => 'Gujarat', 'country' => 'India', 'postal_code' => '380001', 'status' => 'active',
        'is_trial' => true, 'payment_verified' => true, 'start_date' => '2026-01-01', 'end_date' => '2027-12-31',
        'settings' => [],
    ]);
    $ts->createSocietyDatabase($b) or exit("createSocietyDatabase failed\n");
    $ts->runTenantMigrations($b->id) or exit("migrations failed\n");
    $ts->seedTenantDatabase($b->id) or exit("seed failed\n");
}
$a = Society::where('slug', 'demo-society')->firstOrFail();

$out = ['society_a' => $a->id, 'society_b' => $b->id];

foreach (['a' => $a, 'b' => $b] as $key => $society) {
    $ts->setTenant($society);
    $roles = DB::connection('society')->table('roles')->pluck('id', 'name');

    $block = Block::firstOrCreate(['name' => "SecTest Block {$key}"], ['block_number' => "ST{$key}", 'status' => 'active']);
    $flat = Flat::firstOrCreate(['flat_number' => "ST-{$key}-1"], [
        'block_id' => $block->id, 'floor_number' => 1, 'flat_type' => '2bhk', 'status' => 'active',
        'mobile_number' => $key === 'a' ? '9000000011' : '9000000021',
    ]);

    $admin = User::firstOrCreate(['email' => "sectest-admin-{$key}@example.test"], [
        'name' => "SecTest Admin {$key}", 'phone' => $key === 'a' ? '9000000012' : '9000000022',
        'password' => Hash::make($pw), 'status' => 'active',
    ]);
    DB::connection('society')->table('role_user')->insertOrIgnore(['user_id' => $admin->id, 'role_id' => $roles['admin'], 'created_at' => now(), 'updated_at' => now()]);

    $guard = SecurityGuard::firstOrCreate(['phone' => $key === 'a' ? '9000000013' : '9000000023'], ['name' => "SecTest Guard {$key}", 'shift' => 'day', 'status' => 'active']);

    $out[$key] = ['flat_id' => $flat->id, 'admin_id' => $admin->id, 'guard_id' => $guard->id, 'resident_mobile' => $flat->mobile_number, 'guard_phone' => $guard->phone];
}

echo json_encode($out, JSON_PRETTY_PRINT), "\n";
