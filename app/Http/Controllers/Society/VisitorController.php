<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\StoreVisitorRequest;
use App\Models\Tenant\Flat;
use App\Models\Tenant\Visitor;
use App\Services\NotificationService;
use App\Support\ListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VisitorController extends Controller
{
    /**
     * Gate register: currently-in visitors by default, with status/search
     * filters for the full log.
     */
    public function index(Request $request): View
    {
        $query = Visitor::query()->with(['flat.block', 'checkedInBy']);

        $status = $request->string('status')->trim()->value() ?: 'checked_in';
        if ($status !== 'all') {
            $query->status($status);
        }

        ListQuery::applySearch($query, $request, ['visitor_name', 'visitor_phone', 'vehicle_number', 'flat.flat_number']);

        if (($purpose = $request->string('purpose')->trim()->value()) && in_array($purpose, Visitor::PURPOSES, true)) {
            $query->where('purpose', $purpose);
        }

        ListQuery::applySort($query, $request, [
            'check_in' => 'check_in_at',
            'check_out' => 'check_out_at',
            'name' => 'visitor_name',
        ], 'check_in');

        $visitors = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();
        $currentlyInCount = Visitor::currentlyIn()->count();
        $statusCounts = Visitor::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('society.visitors.index', compact('visitors', 'currentlyInCount', 'status', 'statusCounts'));
    }

    /**
     * Show the check-in form.
     */
    public function create(): View
    {
        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('society.visitors.create', compact('flats'));
    }

    /**
     * Log a new visitor entry (check-in).
     */
    public function store(StoreVisitorRequest $request, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validated();

        Visitor::create([
            ...$validated,
            'status' => 'checked_in',
            'check_in_at' => now(),
            'checked_in_by' => Auth::guard('society')->id(),
        ]);

        $notifications->notifyFlats(
            [$validated['flat_id']],
            'visitor_arrived',
            "{$validated['visitor_name']} has arrived",
            ucfirst($validated['purpose']),
        );

        return redirect()
            ->route('society.visitors.index')
            ->with('success', "{$validated['visitor_name']} checked in successfully.");
    }

    /**
     * Record a visitor's exit.
     */
    public function checkOut(int $id): RedirectResponse
    {
        $visitor = Visitor::findOrFail($id);

        if (! $visitor->isCheckedIn()) {
            return back()->with('error', 'This visitor has already checked out.');
        }

        $visitor->update([
            'status' => 'checked_out',
            'check_out_at' => now(),
            'checked_out_by' => Auth::guard('society')->id(),
        ]);

        return back()->with('success', "{$visitor->visitor_name} checked out successfully.");
    }
}
