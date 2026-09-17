<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\StoreExtraChargeRequest;
use App\Models\Tenant\Block;
use App\Models\Tenant\FeeType;
use App\Models\Tenant\Flat;
use App\Models\Tenant\MaintenanceBill;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * One-off charges against a flat — function/event usage, common hall
 * booking, renovation fund, flat transfer fee, etc. — kept as a distinct
 * screen from regular maintenance billing (Society\PaymentController)
 * since Society Admin raises/tracks them separately, but stored as
 * ordinary maintenance_bills rows tagged with a fee_type_id so viewing,
 * recording a payment, printing an invoice, and resident online payment
 * all reuse Society\PaymentController's existing show/pay/invoice routes
 * unchanged (see MaintenanceBill::scopeExtraCharges()).
 */
class ExtraChargeController extends Controller
{
    public function index(Request $request): View
    {
        $charges = MaintenanceBill::extraCharges()->with(['flat.block', 'payments', 'feeType'])->latest('due_date')->get();

        if ($status = $request->string('status')->trim()->value()) {
            $charges = $charges->filter(fn ($bill) => $bill->status === $status)->values();
        }

        $summary = [
            'total_due' => $charges->sum('amount'),
            'total_collected' => $charges->sum('paid_amount'),
            'overdue_count' => $charges->where('status', 'overdue')->count(),
        ];

        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $paginated = new LengthAwarePaginator(
            $charges->forPage($page, $perPage),
            $charges->count(),
            $perPage,
            $page,
        );
        $paginated->withPath($request->url())->appends($request->query());

        return view('society.extra-charges.index', [
            'charges' => $paginated,
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        $blocks = Block::active()->orderBy('name')->get();

        $flats = Flat::active()
            ->with(['block', 'residents' => fn ($query) => $query->where('status', 'active')->with('user')])
            ->orderBy('flat_number')
            ->get();

        $feeTypes = FeeType::orderBy('sort_order')->get();

        return view('society.extra-charges.create', compact('blocks', 'flats', 'feeTypes'));
    }

    public function store(StoreExtraChargeRequest $request, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validated();

        $feeType = FeeType::findOrFail($validated['fee_type_id']);

        $bill = MaintenanceBill::create([
            'flat_id' => $validated['flat_id'],
            'fee_type_id' => $feeType->id,
            'title' => $feeType->name,
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
            'created_by_user_id' => Auth::guard('society')->id(),
        ]);

        $notifications->notifyFlats(
            [$validated['flat_id']],
            'maintenance_due',
            "{$feeType->name} due on ".Carbon::parse($validated['due_date'])->format('d M Y'),
            '₹'.number_format($validated['amount'], 2),
            ['bill_id' => $bill->id],
        );

        return redirect()
            ->route('society.extra-charges.index')
            ->with('success', 'Charge raised successfully.');
    }
}
