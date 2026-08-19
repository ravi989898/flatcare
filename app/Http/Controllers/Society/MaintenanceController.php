<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Block;
use App\Models\Tenant\Flat;
use App\Models\Tenant\MaintenanceRequest;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    /**
     * List maintenance requests with status/priority/category filters.
     */
    public function index(Request $request): View
    {
        $query = MaintenanceRequest::query()->with(['flat.block', 'block', 'assignedTo']);

        $query->status($request->string('status')->trim()->value() ?: null);
        $query->priority($request->string('priority')->trim()->value() ?: null);

        if ($category = $request->string('category')->trim()->value()) {
            $query->where('category', $category);
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('raised_by_name', 'like', "%{$search}%");
            });
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        // Simple status counts for the filter tabs, independent of the
        // current filter selection.
        $statusCounts = MaintenanceRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('society.maintenance.index', compact('requests', 'statusCounts'));
    }

    /**
     * Show the log-a-request form.
     */
    public function create(): View
    {
        $blocks = Block::active()->orderBy('name')->get();
        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('society.maintenance.create', compact('blocks', 'flats'));
    }

    /**
     * Log a new maintenance request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'block_id' => 'nullable|exists:blocks,id',
            'flat_id' => 'nullable|exists:flats,id',
            'category' => 'required|in:' . implode(',', MaintenanceRequest::CATEGORIES),
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:' . implode(',', MaintenanceRequest::PRIORITIES),
            'raised_by_name' => 'required|string|max:255',
            'raised_by_phone' => 'nullable|string|max:20',
        ]);

        $maintenanceRequest = MaintenanceRequest::create([
            ...$validated,
            'status' => 'open',
        ]);

        $maintenanceRequest->logHistory('created', null, Auth::guard('society')->id());

        return redirect()
            ->route('society.maintenance.show', $maintenanceRequest->id)
            ->with('success', 'Maintenance request logged successfully.');
    }

    /**
     * Show a single request with its full history and a status-update form.
     */
    public function show(int $id): View
    {
        $maintenanceRequest = MaintenanceRequest::with(['flat.block', 'block', 'assignedTo', 'raisedBy'])->findOrFail($id);
        $staff = User::active()->admins()->orderBy('name')->get();

        return view('society.maintenance.show', compact('maintenanceRequest', 'staff'));
    }

    /**
     * Update a request's status, priority, and/or assignment. Every change
     * is appended to the request's history trail.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $maintenanceRequest = MaintenanceRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', MaintenanceRequest::STATUSES),
            'priority' => 'required|in:' . implode(',', MaintenanceRequest::PRIORITIES),
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $statusChanged = $validated['status'] !== $maintenanceRequest->status;

        $maintenanceRequest->update([
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'resolution_notes' => $validated['note'] ?: $maintenanceRequest->resolution_notes,
            'resolved_at' => $validated['status'] === 'resolved' ? ($maintenanceRequest->resolved_at ?? now()) : $maintenanceRequest->resolved_at,
            'closed_at' => $validated['status'] === 'closed' ? ($maintenanceRequest->closed_at ?? now()) : $maintenanceRequest->closed_at,
        ]);

        if ($statusChanged || $validated['note']) {
            $maintenanceRequest->logHistory(
                $statusChanged ? "status_changed_to_{$validated['status']}" : 'note_added',
                $validated['note'] ?? null,
                Auth::guard('society')->id(),
            );
        }

        return redirect()
            ->route('society.maintenance.show', $maintenanceRequest->id)
            ->with('success', 'Request updated successfully.');
    }
}
