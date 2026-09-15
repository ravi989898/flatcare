<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\StoreComplaintRequest;
use App\Http\Requests\Society\UpdateComplaintRequest;
use App\Models\Tenant\Complaint;
use App\Models\Tenant\Flat;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    /**
     * List complaints with status/priority/category filters.
     */
    public function index(Request $request): View
    {
        $query = Complaint::query()->with(['flat.block', 'assignedTo']);

        $query->status($request->string('status')->trim()->value() ?: null);
        $query->priority($request->string('priority')->trim()->value() ?: null);

        if ($category = $request->string('category')->trim()->value()) {
            $query->where('category', $category);
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('raised_by_name', 'like', "%{$search}%")
                    ->orWhere('against', 'like', "%{$search}%");
            });
        }

        $complaints = $query->latest()->paginate(15)->withQueryString();

        $statusCounts = Complaint::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('society.complaints.index', compact('complaints', 'statusCounts'));
    }

    /**
     * Show the log-a-complaint form.
     */
    public function create(): View
    {
        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('society.complaints.create', compact('flats'));
    }

    /**
     * Log a new complaint.
     */
    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $complaint = Complaint::create([
            ...$validated,
            'status' => 'open',
        ]);

        $complaint->logHistory('created', null, Auth::guard('society')->id());

        return redirect()
            ->route('society.complaints.show', $complaint->id)
            ->with('success', 'Complaint logged successfully.');
    }

    /**
     * Show a single complaint with its history and a status-update form.
     */
    public function show(int $id): View
    {
        $complaint = Complaint::with(['flat.block', 'assignedTo', 'raisedBy'])->findOrFail($id);
        $staff = User::active()->admins()->orderBy('name')->get();

        return view('society.complaints.show', compact('complaint', 'staff'));
    }

    /**
     * Update a complaint's status, priority, and/or assignment.
     */
    public function update(UpdateComplaintRequest $request, int $id): RedirectResponse
    {
        $complaint = Complaint::findOrFail($id);

        $validated = $request->validated();

        $statusChanged = $validated['status'] !== $complaint->status;

        $complaint->update([
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'resolution_notes' => $validated['note'] ?: $complaint->resolution_notes,
            'resolved_at' => $validated['status'] === 'resolved' ? ($complaint->resolved_at ?? now()) : $complaint->resolved_at,
            'closed_at' => in_array($validated['status'], ['closed', 'rejected'], true) ? ($complaint->closed_at ?? now()) : $complaint->closed_at,
        ]);

        if ($statusChanged || $validated['note']) {
            $complaint->logHistory(
                $statusChanged ? "status_changed_to_{$validated['status']}" : 'note_added',
                $validated['note'] ?? null,
                Auth::guard('society')->id(),
            );
        }

        return redirect()
            ->route('society.complaints.show', $complaint->id)
            ->with('success', 'Complaint updated successfully.');
    }
}
