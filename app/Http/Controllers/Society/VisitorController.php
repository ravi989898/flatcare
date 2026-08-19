<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Flat;
use App\Models\Tenant\Visitor;
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

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        $visitors = $query->latest('check_in_at')->paginate(15)->withQueryString();
        $currentlyInCount = Visitor::currentlyIn()->count();

        return view('society.visitors.index', compact('visitors', 'currentlyInCount', 'status'));
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'visitor_name' => 'required|string|max:255',
            'visitor_phone' => 'nullable|string|max:20',
            'purpose' => 'required|in:' . implode(',', Visitor::PURPOSES),
            'vehicle_number' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        Visitor::create([
            ...$validated,
            'status' => 'checked_in',
            'check_in_at' => now(),
            'checked_in_by' => Auth::guard('society')->id(),
        ]);

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

        if (!$visitor->isCheckedIn()) {
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
