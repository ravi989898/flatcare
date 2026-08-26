<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Society;
use App\Models\SocietyDatabase;
use App\Models\SocietyModule;
use App\Models\SuperAdmin;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SocietyController extends Controller
{
    public function __construct(
        protected TenantService $tenantService,
    ) {}

    /**
     * List societies with search/status filters.
     */
    public function index(Request $request): View
    {
        $query = Society::query()->with('database');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $societies = $query->latest()->paginate(15)->withQueryString();

        return view('admin.societies.index', compact('societies'));
    }

    /**
     * Show the create-society form.
     */
    public function create(): View
    {
        $modules = Module::active()->orderBy('display_order')->get();

        return view('admin.societies.create', compact('modules'));
    }

    /**
     * Store a new society: create its registry row, provision its database,
     * run tenant migrations, seed default roles/permissions, and enable the
     * selected modules.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSociety($request);
        $manualDb = $this->validateManualDbFields($request, hasExistingDatabase: false);

        if ($manualDb === false) {
            return back()->withErrors(['db_name' => 'Database Name, User, and Password must all be provided together.'])->withInput();
        }

        $society = DB::connection('main')->transaction(function () use ($validated, $request) {
            $slug = $this->uniqueSlug($validated['name']);

            $society = Society::create([
                ...$validated,
                'slug' => $slug,
                'db_name' => "pending-{$slug}", // replaced once the tenant database is provisioned; kept unique meanwhile
                'settings' => [],
                'created_by_super_admin' => $this->currentSuperAdminId(),
            ]);

            SocietyModule::insert(
                collect($request->input('modules', []))
                    ->unique()
                    ->map(fn ($moduleId) => [
                        'society_id' => $society->id,
                        'module_id' => $moduleId,
                        'is_enabled' => true,
                        'enabled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->all()
            );

            return $society;
        });

        if ($manualDb) {
            $this->tenantService->registerManualDatabase($society, $manualDb['db_name'], $manualDb['db_user'], $manualDb['db_password']);
            $provisioned = $this->tenantService->runTenantMigrations($society->id)
                && $this->tenantService->seedTenantDatabase($society->id);
        } else {
            $provisioned = $this->tenantService->createSocietyDatabase($society)
                && $this->tenantService->runTenantMigrations($society->id)
                && $this->tenantService->seedTenantDatabase($society->id);
        }

        AuditLog::log(
            $this->currentSuperAdmin(),
            $society,
            'society.created',
            'society_management',
            Society::class,
            $society->id,
            null,
            $society->fresh()->toArray(),
        );

        if (!$provisioned) {
            return redirect()
                ->route('admin.societies.index')
                ->with('warning', "Society \"{$society->name}\" was created, but its database could not be fully provisioned. Check the logs and retry provisioning.");
        }

        return redirect()
            ->route('admin.societies.index')
            ->with('success', "Society \"{$society->name}\" created and provisioned successfully.");
    }

    /**
     * Show a single society: profile, database status, and module toggles.
     */
    public function show(int $id): View
    {
        $society = Society::with(['database', 'modules.module'])->findOrFail($id);
        $modules = Module::active()->orderBy('display_order')->get();
        $enabledModuleIds = $society->modules->where('is_enabled', true)->pluck('module_id');

        return view('admin.societies.show', compact('society', 'modules', 'enabledModuleIds'));
    }

    /**
     * Show the edit-society form.
     */
    public function edit(int $id): View
    {
        $society = Society::findOrFail($id);

        return view('admin.societies.edit', compact('society'));
    }

    /**
     * Update a society's profile fields (not its database or modules —
     * those go through update()'s dedicated actions).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $society = Society::findOrFail($id);
        $validated = $this->validateSociety($request, $society->id);
        $manualDb = $this->validateManualDbFields($request, hasExistingDatabase: $society->database !== null);

        if ($manualDb === false) {
            return back()->withErrors(['db_name' => 'Database Name and User are required (Password too, unless one is already on file).'])->withInput();
        }

        $before = $society->toArray();
        $society->update($validated);

        if ($manualDb) {
            $this->tenantService->registerManualDatabase($society, $manualDb['db_name'], $manualDb['db_user'], $manualDb['db_password']);
        }

        AuditLog::log(
            $this->currentSuperAdmin(),
            $society,
            'society.updated',
            'society_management',
            Society::class,
            $society->id,
            $before,
            $society->fresh()->toArray(),
        );

        $this->tenantService->clearTenantCache($society->id);

        return redirect()
            ->route('admin.societies.show', $society->id)
            ->with('success', $manualDb
                ? 'Society details updated. Database credentials saved — use "Retry Provisioning" on the society page to migrate it.'
                : 'Society details updated successfully.');
    }

    /**
     * Archive a society (soft delete). The tenant database is left intact
     * so it can be restored or exported later.
     */
    public function destroy(int $id): RedirectResponse
    {
        $society = Society::findOrFail($id);
        $society->update(['status' => 'archived']);
        $society->delete();

        AuditLog::log(
            $this->currentSuperAdmin(),
            $society,
            'society.archived',
            'society_management',
            Society::class,
            $society->id,
        );

        $this->tenantService->clearTenantCache($society->id);

        return redirect()
            ->route('admin.societies.index')
            ->with('success', "Society \"{$society->name}\" archived successfully.");
    }

    /**
     * Toggle a module on/off for a society.
     */
    public function toggleModule(Request $request, int $id, int $moduleId): RedirectResponse
    {
        $society = Society::findOrFail($id);
        $module = Module::findOrFail($moduleId);

        $societyModule = SocietyModule::firstOrNew([
            'society_id' => $society->id,
            'module_id' => $module->id,
        ]);

        $enabling = !$societyModule->exists || !$societyModule->is_enabled;

        if ($module->is_core && !$enabling) {
            return back()->with('warning', "\"{$module->display_name}\" is a core module and cannot be disabled.");
        }

        $societyModule->fill([
            'is_enabled' => $enabling,
            'enabled_at' => $enabling ? now() : $societyModule->enabled_at,
            'disabled_at' => $enabling ? null : now(),
        ])->save();

        $this->tenantService->clearTenantCache($society->id);

        AuditLog::log(
            $this->currentSuperAdmin(),
            $society,
            $enabling ? 'society.module_enabled' : 'society.module_disabled',
            'society_management',
            Module::class,
            $module->id,
        );

        return back()->with('success', "\"{$module->display_name}\" " . ($enabling ? 'enabled' : 'disabled') . " for {$society->name}.");
    }

    /**
     * Retry database provisioning for a society whose creation failed
     * partway through (or that has not been provisioned yet).
     */
    public function retryProvisioning(int $id): RedirectResponse
    {
        $society = Society::findOrFail($id);

        // A database row already means the database itself exists — either
        // this app created it before (even if migrations then failed), or
        // its credentials were entered by hand via the edit form. Either
        // way, re-running createSocietyDatabase() here would try to CREATE
        // DATABASE a second, differently-named database and orphan the
        // existing row, so just re-attempt migrations against what's there.
        $hasDatabase = SocietyDatabase::where('society_id', $society->id)->exists();

        $provisioned = ($hasDatabase || $this->tenantService->createSocietyDatabase($society))
            && $this->tenantService->runTenantMigrations($society->id)
            && $this->tenantService->seedTenantDatabase($society->id);

        if (!$provisioned) {
            return back()->with('warning', 'Provisioning failed again. Check application logs for details.');
        }

        return back()->with('success', 'Society database provisioned successfully.');
    }

    /**
     * Shared validation for store/update.
     */
    private function validateSociety(Request $request, ?int $ignoreId = null): array
    {
        // Blank optional text inputs arrive as "" rather than absent; normalize
        // them to null so nullable numeric/date columns don't receive "".
        $request->merge(collect($request->only(['registration_number', 'total_flats', 'total_blocks', 'fixed_maintenance', 'water_unit_rate', 'admin_name', 'admin_email', 'admin_phone', 'alternate_phone', 'description']))
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all());

        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'required|email|unique:main.societies,email' . ($ignoreId ? ",{$ignoreId}" : ''),
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:20',
            'registration_number' => 'nullable|string|max:255',
            'total_flats' => 'nullable|integer|min:0',
            'total_blocks' => 'nullable|integer|min:0',
            'fixed_maintenance' => 'nullable|numeric|min:0',
            'water_unit_rate' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,inactive,expired,archived',
            'is_trial' => 'boolean',
            'payment_verified' => 'boolean',
            'admin_name' => 'nullable|string|max:255',
            'admin_email' => 'nullable|email|max:255',
            'admin_phone' => 'nullable|string|max:20',
        ]);
    }

    /**
     * Reads the optional manual db_name/db_user/db_password fields (used
     * when the hosting environment doesn't let the app CREATE DATABASE
     * itself, so the super admin creates the tenant database by hand and
     * pastes its credentials in here instead).
     *
     * Returns null if none of the three were provided (the normal
     * auto-provisioning path), an array of the three values if a usable set
     * was provided, or false if the set was incomplete and the caller
     * should reject the request. $hasExistingDatabase allows the password
     * to be left blank (kept as-is) when editing a society that already has
     * database credentials on file.
     */
    private function validateManualDbFields(Request $request, bool $hasExistingDatabase): array|false|null
    {
        $dbName = trim((string) $request->input('db_name'));
        $dbUser = trim((string) $request->input('db_user'));
        $dbPassword = trim((string) $request->input('db_password'));

        if ($dbName === '' && $dbUser === '' && $dbPassword === '') {
            return null;
        }

        if ($dbName === '' || $dbUser === '' || ($dbPassword === '' && !$hasExistingDatabase)) {
            return false;
        }

        return [
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => $dbPassword === '' ? null : $dbPassword,
        ];
    }

    /**
     * Build a unique, URL-safe slug for the society (also used as part of
     * its tenant database name).
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Society::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-" . ++$suffix;
        }

        return $slug;
    }

    private function currentSuperAdmin(): ?SuperAdmin
    {
        $userId = auth()->id();

        return $userId ? SuperAdmin::where('user_id', $userId)->first() : null;
    }

    private function currentSuperAdminId(): ?int
    {
        return $this->currentSuperAdmin()?->id;
    }
}
