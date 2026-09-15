<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocietyRequest;
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
    public function store(SocietyRequest $request): RedirectResponse
    {
        $validated = $request->societyFields();
        $manualDb = $request->manualDbFields();

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

        if (! $provisioned) {
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
    public function update(SocietyRequest $request, int $id): RedirectResponse
    {
        $society = Society::findOrFail($id);
        $validated = $request->societyFields();
        $manualDb = $request->manualDbFields();

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

        $enabling = ! $societyModule->exists || ! $societyModule->is_enabled;

        if ($module->is_core && ! $enabling) {
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

        return back()->with('success', "\"{$module->display_name}\" ".($enabling ? 'enabled' : 'disabled')." for {$society->name}.");
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

        if (! $provisioned) {
            return back()->with('warning', 'Provisioning failed again. Check application logs for details.');
        }

        return back()->with('success', 'Society database provisioned successfully.');
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
            $slug = "{$base}-".++$suffix;
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
