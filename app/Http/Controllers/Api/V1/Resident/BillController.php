<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\BillResource;
use App\Models\Tenant\MaintenanceBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-only for now — no "pay" action here yet (Razorpay integration is a
 * later phase). The Flutter app shows a disabled/"coming soon" Pay button
 * instead of calling anything.
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
        $bills = MaintenanceBill::with(['flat.block', 'payments'])
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
        $bill = MaintenanceBill::with(['flat.block', 'payments'])
            ->whereIn('flat_id', $this->myFlatIds())
            ->findOrFail($id);

        return $this->ok(new BillResource($bill));
    }
}
