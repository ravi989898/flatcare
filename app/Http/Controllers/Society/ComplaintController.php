<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'flat_id' => 'nullable|exists:flats,id',
            'category' => 'required|in:' . implode(',', Complaint::CATEGORIES),
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'against' => 'nullable|string|max:255',
            'priority' => 'required|in:' . implode(',', Complaint::PRIORITIES),
            'raised_by_name' => 'required|string|max:255',
            'raised_by_phone' => 'nullable|string|max:20',
        ]);

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
    public function update(Request $request, int $id): RedirectResponse
    {
        $complaint = Complaint::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Complaint::STATUSES),
            'priority' => 'required|in:' . implode(',', Complaint::PRIORITIES),
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
        ]);

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
