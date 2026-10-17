<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesSecurityGuardDuty;
use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityGuardRequest;
use App\Models\Society;
use App\Models\Tenant\SecurityGuard;
use App\Models\Tenant\SecurityGuardLog;
use App\Services\TenantService;
use App\Support\ListQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SocietySecurityController extends Controller
{
    use ManagesSecurityGuardDuty;

    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * List security guards for a society, newest first, plus who currently
     * holds the Day and Night shift — that's what the resident mobile app
     * shows as on duty.
     */
    public function index(Request $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $query = SecurityGuard::query();

        ListQuery::applySearch($query, $request, ['name', 'phone']);

        if (in_array($shift = $request->string('shift')->value(), ['day', 'night'], true)) {
            $query->where('shift', $shift);
        }

        if (in_array($status = $request->string('status')->value(), ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        ListQuery::applySort($query, $request, ['added' => 'created_at', 'name' => 'name', 'shift' => 'shift'], 'added');

        $guards = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();
        $onDuty = $this->currentDutyByShift();

        return view('admin.societies.security.index', compact('society', 'guards', 'onDuty'));
    }

    public function create(Request $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        return view('admin.societies.security.create', compact('society'));
    }

    public function store(SecurityGuardRequest $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        $validated = $request->validated();

        $this->tenantService->switchConnection($societyId);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('security-guards', 'public');
        }

        $guard = SecurityGuard::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'shift' => $validated['shift'],
            'aadhar_last4' => $validated['aadhar_last4'] ?? null,
            'photo_path' => $photoPath,
            'status' => 'active',
        ]);

        $this->assignShift($guard, $validated['shift']);

        return redirect()
            ->route('admin.societies.security.index', $societyId)
            ->with('success', 'Security guard added successfully');
    }

    public function edit(Request $request, int $societyId, int $guardId)
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $guard = SecurityGuard::findOrFail($guardId);

        return view('admin.societies.security.edit', compact('society', 'guard'));
    }

    public function update(SecurityGuardRequest $request, int $societyId, int $guardId)
    {
        $society = Society::findOrFail($societyId);

        $validated = $request->validated();

        $this->tenantService->switchConnection($societyId);

        $guard = SecurityGuard::findOrFail($guardId);

        $updates = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'shift' => $validated['shift'],
            'aadhar_last4' => $validated['aadhar_last4'] ?? null,
        ];

        if ($request->hasFile('photo')) {
            if ($guard->photo_path) {
                Storage::disk('public')->delete($guard->photo_path);
            }
            $updates['photo_path'] = $request->file('photo')->store('security-guards', 'public');
        }

        $guard->update($updates);

        if ($guard->status === 'active') {
            $this->assignShift($guard, $validated['shift']);
        }

        return redirect()
            ->route('admin.societies.security.index', $societyId)
            ->with('success', 'Security guard updated successfully');
    }

    /**
     * Activate a guard and put them back on their assigned shift — this
     * replaces whoever currently holds that shift.
     */
    public function activate(Request $request, int $societyId, int $guardId)
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $guard = SecurityGuard::findOrFail($guardId);
        $guard->update(['status' => 'active']);
        $this->assignShift($guard, $guard->shift ?? 'day');

        return redirect()
            ->route('admin.societies.security.index', $societyId)
            ->with('success', 'Security guard activated successfully');
    }

    /**
     * Deactivate a guard and close out their duty log — their shift is
     * vacant until someone else is assigned to it.
     */
    public function deactivate(Request $request, int $societyId, int $guardId)
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $guard = SecurityGuard::findOrFail($guardId);
        $guard->update(['status' => 'inactive']);
        $this->endDuty($guard);

        return redirect()
            ->route('admin.societies.security.index', $societyId)
            ->with('success', 'Security guard deactivated successfully');
    }

    /**
     * Full duty history — every (guard, shift) period, newest first, so
     * "who was on night duty on 5 Sep?" can actually be answered.
     */
    public function history(Request $request, int $societyId)
    {
        $society = Society::findOrFail($societyId);

        $this->tenantService->switchConnection($societyId);

        $query = SecurityGuardLog::with('securityGuard');

        ListQuery::applySearch($query, $request, ['securityGuard.name', 'securityGuard.phone']);

        if (in_array($shift = $request->string('shift')->value(), ['day', 'night'], true)) {
            $query->where('shift', $shift);
        }

        ListQuery::applySort($query, $request, ['started' => 'started_at', 'ended' => 'ended_at'], 'started');

        $logs = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();

        return view('admin.societies.security.history', compact('society', 'logs'));
    }
}
