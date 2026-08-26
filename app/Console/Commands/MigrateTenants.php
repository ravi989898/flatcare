<?php

namespace App\Console\Commands;

use App\Models\SocietyDatabase;
use App\Services\TenantService;
use Illuminate\Console\Command;

/**
 * TenantService::runTenantMigrations() only ever runs automatically at
 * provisioning time for a brand-new society (see Admin\SocietyController).
 * Whenever a new migration is added to database/migrations/tenant/ after
 * that, every already-provisioned society's own physical database needs it
 * applied by hand — this command is the reusable way to do that, instead
 * of a one-off script per migration.
 */
class MigrateTenants extends Command
{
    protected $signature = 'tenants:migrate';

    protected $description = "Run pending database/migrations/tenant migrations against every provisioned society's own database";

    public function handle(TenantService $tenantService): int
    {
        $societyDatabases = SocietyDatabase::active()->get();

        if ($societyDatabases->isEmpty()) {
            $this->warn('No active society databases found.');

            return self::SUCCESS;
        }

        $failures = 0;

        foreach ($societyDatabases as $societyDatabase) {
            $this->info("Migrating society #{$societyDatabase->society_id} ({$societyDatabase->db_name})...");

            if (!$tenantService->runTenantMigrations($societyDatabase->society_id)) {
                $this->error("  Failed — see storage/logs for details.");
                $failures++;
            }
        }

        if ($failures > 0) {
            $this->error("{$failures} of {$societyDatabases->count()} society database(s) failed to migrate.");

            return self::FAILURE;
        }

        $this->info("All {$societyDatabases->count()} society database(s) migrated successfully.");

        return self::SUCCESS;
    }
}
