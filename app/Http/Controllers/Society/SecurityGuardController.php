<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Concerns\ManagesSecurityGuardDuty;
use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityGuardRequest;
use App\Models\Tenant\SecurityGuard;
use App\Models\Tenant\SecurityGuardLog;
use App\Support\ListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Lets a Society Admin manage their own society's security guard roster
 * (App\Http\Controllers\Admin\SocietySecurityController does the same thing
 * cross-tenant, for Super Admin's use). No delete - a guard who has left is
 * marked Inactive so history is kept.
 */
class SecurityGuardController extends Controller
{
    use ManagesSecurityGuardDuty;

    public function index(Request $request): View
    {
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

        return view('society.security.index', compact('guards', 'onDuty'));
    }

    public function create(): View
    {
        return view('society.security.create');
    }

    public function store(SecurityGuardRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
            ->route('society.security.index')
            ->with('success', 'Security guard added successfully.');
    }

    public function edit(int $guardId): View
    {
        $guard = SecurityGuard::findOrFail($guardId);

        return view('society.security.edit', compact('guard'));
    }

    public function update(SecurityGuardRequest $request, int $guardId): RedirectResponse
    {
        $validated = $request->validated();

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
            ->route('society.security.index')
            ->with('success', 'Security guard updated successfully.');
    }

    public function activate(int $guardId): RedirectResponse
    {
        $guard = SecurityGuard::findOrFail($guardId);
        $guard->update(['status' => 'active']);
        $this->assignShift($guard, $guard->shift ?? 'day');

        return redirect()
            ->route('society.security.index')
            ->with('success', 'Security guard activated successfully.');
    }

    public function deactivate(int $guardId): RedirectResponse
    {
        $guard = SecurityGuard::findOrFail($guardId);
        $guard->update(['status' => 'inactive']);
        $this->endDuty($guard);

        return redirect()
            ->route('society.security.index')
            ->with('success', 'Security guard deactivated successfully.');
    }

    /**
     * Full duty history - every (guard, shift) period, newest first, so
     * "who was on night duty on 5 Sep?" can actually be answered.
     */
    public function history(Request $request): View
    {
        $query = SecurityGuardLog::with('securityGuard');

        ListQuery::applySearch($query, $request, ['securityGuard.name', 'securityGuard.phone']);

        if (in_array($shift = $request->string('shift')->value(), ['day', 'night'], true)) {
            $query->where('shift', $shift);
        }

        ListQuery::applySort($query, $request, ['started' => 'started_at', 'ended' => 'ended_at'], 'started');

        $logs = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();

        return view('society.security.history', compact('logs'));
    }
}
