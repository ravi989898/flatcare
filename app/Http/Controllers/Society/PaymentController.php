<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Flat;
use App\Models\Tenant\MaintenanceBill;
use App\Models\Tenant\Payment;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Bill list with status/flat filters. Status is computed per-bill from
     * its payments (see MaintenanceBill::getStatusAttribute()) rather than
     * stored, so filtering happens in-memory after loading — fine at the
     * scale of one society's bills, and it can't drift out of sync with
     * the payment ledger the way a cached status column could.
     */
    public function index(Request $request): View
    {
        $bills = MaintenanceBill::with(['flat.block', 'payments'])->latest('due_date')->get();

        if ($status = $request->string('status')->trim()->value()) {
            $bills = $bills->filter(fn ($bill) => $bill->status === $status)->values();
        }

        if ($flatId = $request->string('flat_id')->trim()->value()) {
            $bills = $bills->where('flat_id', $flatId)->values();
        }

        $summary = [
            'total_due' => $bills->sum('amount'),
            'total_collected' => $bills->sum('paid_amount'),
            'overdue_count' => $bills->where('status', 'overdue')->count(),
        ];

        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $paginated = new LengthAwarePaginator(
            $bills->forPage($page, $perPage),
            $bills->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('society.payments.index', [
            'bills' => $paginated,
            'summary' => $summary,
            'flats' => $flats,
        ]);
    }

    public function create(): View
    {
        $flats = Flat::active()->with('block')->orderBy('flat_number')->get();

        return view('society.payments.create', compact('flats'));
    }

    /**
     * Raise a bill for one flat, or for every active flat at once when
     * "all" is chosen — sparing the admin from creating the same monthly
     * bill flat-by-flat.
     */
    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'flat_id' => ['required', Rule::in(array_merge(['all'], Flat::pluck('id')->all()))],
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $flatIds = $validated['flat_id'] === 'all'
            ? Flat::active()->pluck('id')
            : collect([$validated['flat_id']]);

        foreach ($flatIds as $flatId) {
            $bill = MaintenanceBill::create([
                'flat_id' => $flatId,
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by_user_id' => Auth::guard('society')->id(),
            ]);

            $notifications->notifyFlats(
                [$flatId],
                'maintenance_due',
                "{$validated['title']} due on " . \Illuminate\Support\Carbon::parse($validated['due_date'])->format('d M Y'),
                "₹" . number_format($validated['amount'], 2),
                ['bill_id' => $bill->id],
            );
        }

        $count = $flatIds->count();

        return redirect()
            ->route('society.payments.index')
            ->with('success', $count > 1 ? "Bill raised for {$count} flats." : 'Bill raised successfully.');
    }

    public function show(int $id): View
    {
        $bill = MaintenanceBill::with(['flat.block', 'payments.recordedBy'])->findOrFail($id);

        return view('society.payments.show', compact('bill'));
    }

    /**
     * Record a payment against a bill. Rejected if it would push the bill
     * into credit — a real overpayment/refund workflow is out of scope
     * here, so the ledger only ever tracks payments up to what's owed.
     */
    public function recordPayment(Request $request, int $id): RedirectResponse
    {
        $bill = MaintenanceBill::with('payments')->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', Payment::METHODS),
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['amount'] > $bill->balance) {
            return back()->with('error', "That's more than the remaining balance of ₹{$bill->balance}.");
        }

        Payment::create([
            ...$validated,
            'bill_id' => $bill->id,
            'recorded_by_user_id' => Auth::guard('society')->id(),
        ]);

        return redirect()
            ->route('society.payments.show', $bill->id)
            ->with('success', 'Payment recorded successfully.');
    }
}
