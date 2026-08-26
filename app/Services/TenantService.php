<?php

namespace App\Services;

use App\Models\Society;
use App\Models\SocietyDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TenantService - Handles multi-tenant database connection management
 *
 * This service is responsible for:
 * - Identifying the current tenant (society)
 * - Fetching tenant database credentials
 * - Switching database connections
 * - Maintaining tenant context throughout request
 */
class TenantService
{
    /**
     * Current tenant context
     */
    private ?int $currentTenantId = null;
    private ?array $tenantConnection = null;

    /**
     * Set the current tenant
     */
    public function setTenant(int|Society $tenant): void
    {
        $societyId = $tenant instanceof Society ? $tenant->id : $tenant;
        $this->currentTenantId = $societyId;
        $this->tenantConnection = null; // Reset connection cache

        $this->switchConnection($societyId);
    }

    /**
     * Get current tenant ID
     */
    public function getCurrentTenantId(): ?int
    {
        return $this->currentTenantId;
    }

    /**
     * Get current tenant Society model
     */
    public function getCurrentSociety(): ?Society
    {
        if (!$this->currentTenantId) {
            return null;
        }

        return Cache::remember(
            "society.{$this->currentTenantId}",
            now()->addHours(24),
            fn() => Society::find($this->currentTenantId)
        );
    }

    /**
     * Switch database connection to tenant database
     *
     * @throws \Exception
     */
    public function switchConnection(int $societyId): void
    {
        try {
            // Get cached connection if available
            $cacheKey = "tenant.connection.{$societyId}";
            $credentials = Cache::remember(
                $cacheKey,
                now()->addHours(1),
                fn() => $this->getConnectionCredentials($societyId)
            );

            if (!$credentials) {
                throw new \Exception("Tenant database credentials not found for society: {$societyId}");
            }

            // Configure the shared 'society' connection to point at this tenant's
            // database. Every tenant-aware query goes through DB::connection('society'),
            // so we reconfigure that single connection rather than minting one per
            // society id (which nothing else looks up).
            Config::set('database.connections.society', [
                'driver' => 'mysql',
                'host' => $credentials['db_host'],
                'port' => $credentials['db_port'],
                'database' => $credentials['db_name'],
                'username' => $credentials['db_user'],
                'password' => decrypt($credentials['db_password']),
                'charset' => $credentials['db_charset'],
                'collation' => $credentials['db_collation'],
                'prefix' => '',
                'strict' => true,
                'engine' => 'InnoDB',
            ]);

            // Drop any previously opened PDO handle for this connection name so the
            // new config actually takes effect instead of reusing the old society's
            // connection.
            DB::purge('society');

            // Set default connection for models
            DB::setDefaultConnection('society');

            $this->tenantConnection = $credentials;

            Log::info("Switched to tenant database", [
                'society_id' => $societyId,
                'database' => $credentials['db_name'],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to switch tenant connection", [
                'society_id' => $societyId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get connection credentials for a society
     */
    private function getConnectionCredentials(int $societyId): ?array
    {
        try {
            // Query main database. 'created' is included alongside 'active'
            // because the very first migration run has to connect to a
            // database that createSocietyDatabase() just created but hasn't
            // been promoted to 'active' yet — that promotion only happens
            // once runTenantMigrations() itself succeeds via this method.
            $connection = DB::connection('main')
                ->table('society_databases')
                ->where('society_id', $societyId)
                ->whereIn('status', ['active', 'created'])
                ->first();

            if (!$connection) {
                return null;
            }

            return (array) $connection;
        } catch (\Exception $e) {
            Log::error("Error fetching tenant credentials", [
                'society_id' => $societyId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a new society database
     */
    public function createSocietyDatabase(Society $society): bool
    {
        try {
            // Generate database name
            $dbName = "society_{$society->id}_{$society->slug}";

            // Create database
            $this->createDatabase($dbName);

            // Store credentials in main database
            SocietyDatabase::create([
                'society_id' => $society->id,
                'db_host' => config('database.connections.mysql.host'),
                'db_port' => config('database.connections.mysql.port', 3306),
                'db_name' => $dbName,
                'db_user' => config('database.connections.mysql.username'),
                'db_password' => encrypt(config('database.connections.mysql.password')),
                'db_charset' => 'utf8mb4',
                'db_collation' => 'utf8mb4_unicode_ci',
                'status' => 'created',
            ]);

            $society->update(['db_name' => $dbName]);

            Log::info("Society database created", [
                'society_id' => $society->id,
                'database' => $dbName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create society database", [
                'society_id' => $society->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Register a tenant database whose credentials point at a database the
     * super admin created by hand (e.g. via cPanel, on hosts that don't
     * grant the app's DB user CREATE DATABASE privilege) instead of one
     * this app created itself via createSocietyDatabase(). Skips the
     * `CREATE DATABASE` statement entirely — the database is assumed to
     * already exist — and just stores the credentials so switchConnection()
     * can use them.
     *
     * A blank $dbPassword keeps whatever password is already on file for
     * this society (lets the edit form fix the db name/user without forcing
     * the password to be retyped every time).
     */
    public function registerManualDatabase(Society $society, string $dbName, string $dbUser, ?string $dbPassword): SocietyDatabase
    {
        $existing = SocietyDatabase::where('society_id', $society->id)->first();

        $record = SocietyDatabase::updateOrCreate(
            ['society_id' => $society->id],
            [
                'db_host' => config('database.connections.mysql.host', '127.0.0.1'),
                'db_port' => config('database.connections.mysql.port', 3306),
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => filled($dbPassword) ? encrypt($dbPassword) : $existing?->db_password,
                'db_charset' => 'utf8mb4',
                'db_collation' => 'utf8mb4_unicode_ci',
                'status' => 'created',
                'error_message' => null,
            ]
        );

        $society->update(['db_name' => $dbName]);

        Log::info('Society database credentials registered manually', [
            'society_id' => $society->id,
            'database' => $dbName,
        ]);

        return $record;
    }

    /**
     * Run migrations for tenant database
     */
    public function runTenantMigrations(int $societyId): bool
    {
        try {
            $this->switchConnection($societyId);

            // Run migrations
            \Artisan::call('migrate', [
                '--database' => 'society',
                '--force' => true,
                '--path' => 'database/migrations/tenant',
            ]);

            // Update database status
            DB::connection('main')
                ->table('society_databases')
                ->where('society_id', $societyId)
                ->update([
                    'status' => 'active',
                    'last_migrated_at' => now(),
                ]);

            Log::info("Tenant migrations completed", [
                'society_id' => $societyId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Tenant migrations failed", [
                'society_id' => $societyId,
                'error' => $e->getMessage(),
            ]);

            DB::connection('main')
                ->table('society_databases')
                ->where('society_id', $societyId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

            return false;
        }
    }

    /**
     * Seed initial data for tenant database
     */
    public function seedTenantDatabase(int $societyId): bool
    {
        try {
            $this->switchConnection($societyId);

            \Artisan::call('db:seed', [
                '--database' => 'society',
                '--force' => true,
                '--class' => 'Database\Seeders\TenantSeeder',
            ]);

            Log::info("Tenant seeding completed", [
                'society_id' => $societyId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Tenant seeding failed", [
                'society_id' => $societyId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create database
     */
    private function createDatabase(string $databaseName): void
    {
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci");

            Log::info("Database created", ['database' => $databaseName]);
        } catch (\Exception $e) {
            Log::error("Failed to create database", [
                'database' => $databaseName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate society access period
     */
    public function validateSocietyAccessPeriod(Society $society): bool
    {
        $today = today();

        // Check if society access period is valid
        if ($today < $society->start_date || $today > $society->end_date) {
            return false;
        }

        // Check if society is active
        if ($society->status !== 'active') {
            return false;
        }

        return true;
    }

    /**
     * Check if module is enabled for society
     */
    public function isModuleEnabled(Society $society, string $moduleName): bool
    {
        return Cache::remember(
            "society.{$society->id}.module.{$moduleName}",
            now()->addHours(6),
            function () use ($society, $moduleName) {
                return DB::connection('main')
                    ->table('society_modules')
                    ->join('modules', 'society_modules.module_id', '=', 'modules.id')
                    ->where('society_modules.society_id', $society->id)
                    ->where('modules.name', $moduleName)
                    ->where('society_modules.is_enabled', true)
                    ->exists();
            }
        );
    }

    /**
     * Clear cache for tenant
     */
    public function clearTenantCache(int $societyId): void
    {
        Cache::forget("tenant.connection.{$societyId}");
        Cache::forget("society.{$societyId}");

        // Per-module cache (see isModuleEnabled()) isn't tagged - it isn't
        // stored via ->tags(), and the "database"/"file" cache drivers this
        // app uses don't support tagging anyway. Those entries just expire
        // on their own after 6 hours instead of being busted here.
    }
}
