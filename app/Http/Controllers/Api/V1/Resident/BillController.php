<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\BillResource;
use App\Models\Tenant\MaintenanceBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-only bill lookups, plus the receipt/invoice PDF download. Starting
 * and confirming an actual online payment lives in BillPaymentController
 * (see App\Services\RazorpayService) — kept separate since that's where
 * all the payment-security logic sits, not here.
 */
class BillController extends ApiController
{
    /**
     * status is computed from payments (see MaintenanceBill::getStatusAttribute()),
     * not stored, so — same as Society\PaymentController::index() — filtering
     * by it has to happen in-memory after loading, then get paginated by hand.
     */
    public function index(Request $request): JsonResponse
    {
        $bills = MaintenanceBill::with(['flat.block', 'payments', 'waterReading'])
            ->whereIn('flat_id', $this->myFlatIds())
            ->latest('due_date')
            ->get();

        if ($status = $request->string('status')->trim()->value()) {
            $bills = $bills->filter(fn ($bill) => $bill->status === $status)->values();
        }

        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $paginated = new LengthAwarePaginator(
            $bills->forPage($page, $perPage),
            $bills->count(),
            $perPage,
            $page,
        );

        return $this->paginated(BillResource::collection($paginated), $paginated);
    }

    public function show(int $id): JsonResponse
    {
        $bill = MaintenanceBill::with(['flat.block', 'payments', 'waterReading'])
            ->whereIn('flat_id', $this->myFlatIds())
            ->findOrFail($id);

        return $this->ok(new BillResource($bill));
    }

    /**
     * A printable PDF for the bill — an "invoice" while unpaid, or a
     * "receipt" once payments cover it, since the same layout shows
     * whatever payment history exists either way. Scoped through the same
     * whereIn(myFlatIds()) ownership check as show() — nothing extra is
     * needed for access control here.
     */
    public function receipt(Request $request, int $id): Response
    {
        $bill = MaintenanceBill::with(['flat.block', 'payments', 'waterReading'])
            ->whereIn('flat_id', $this->myFlatIds())
            ->findOrFail($id);

        $society = $request->attributes->get('api_society');

        $pdf = Pdf::loadView('pdf.bill_receipt', [
            'bill' => $bill,
            'society' => $society,
            'breakdown' => $bill->breakdownLines($society),
            'mobileNumber' => $this->user()->phone,
        ]);

        return $pdf->download("bill-{$bill->id}-receipt.pdf");
    }
}
