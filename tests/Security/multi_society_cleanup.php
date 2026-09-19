<?php
// Removes everything multi_society_setup.php / multi_society_test.py created:
// the throw-away "SecTest Society B" (its database, registry rows, tokens) and the
// sectest-* rows added to Society A. Touches nothing that does not carry the
// sectest / SECTEST marker.

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Society;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

$ts = app(TenantService::class);

// ---- Society A: sectest rows only ------------------------------------------
if ($a = Society::where('slug', 'demo-society')->first()) {
    $ts->setTenant($a);
    $c = DB::connection('society');

    $flatIds = $c->table('flats')->where('flat_number', 'like', 'ST-a-%')->pluck('id');
    $userIds = $c->table('users')->where('email', 'like', 'sectest-%')->orWhere('name', 'like', 'SecTest%')
        ->orWhereIn('id', $c->table('flat_residents')->whereIn('flat_id', $flatIds)->pluck('user_id'))->pluck('id');

    DB::connection('main')->table('api_tokens')->where('society_id', $a->id)->whereIn('tenant_user_id', $userIds)->delete();
    foreach (['visitors' => 'visitor_name', 'daily_helpers' => 'name', 'complaints' => 'subject'] as $t => $col) {
        if (\Illuminate\Support\Facades\Schema::connection('society')->hasTable($t)) {
            $c->table($t)->where($col, 'like', 'SECTEST%')->delete();
        }
    }
    foreach (['resident_notifications', 'family_members', 'vehicles'] as $t) {
        if (\Illuminate\Support\Facades\Schema::connection('society')->hasTable($t)) {
            $c->table($t)->whereIn('user_id', $userIds)->delete();
        }
    }
    $c->table('role_user')->whereIn('user_id', $userIds)->delete();
    $c->table('flat_residents')->whereIn('user_id', $userIds)->delete();
    $c->table('users')->whereIn('id', $userIds)->delete();
    $c->table('security_guards')->where('name', 'like', 'SecTest Guard%')->delete();
    $c->table('flats')->whereIn('id', $flatIds)->delete();
    $c->table('blocks')->where('name', 'like', 'SecTest Block%')->delete();
    echo "Society A cleaned (users: {$userIds->count()}, flats: {$flatIds->count()})\n";
}

// ---- Society B: drop it entirely --------------------------------------------
if ($b = Society::where('slug', 'sectest-b')->first()) {
    $db = (string) $b->db_name;
    DB::connection('main')->table('api_tokens')->where('society_id', $b->id)->delete();
    DB::connection('main')->table('society_databases')->where('society_id', $b->id)->delete();
    DB::connection('main')->table('society_modules')->where('society_id', $b->id)->delete();
    $b->forceDelete();
    if (preg_match('/^society_\d+_sectest-b$/', $db)) {
        DB::connection('main')->statement("DROP DATABASE IF EXISTS `{$db}`");
        echo "Dropped {$db}\n";
    }
}
echo "done\n";
