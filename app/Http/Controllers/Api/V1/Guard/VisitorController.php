<?php

namespace App\Http\Controllers\Api\V1\Guard;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Guard\StoreGuardVisitorRequest;
use App\Http\Resources\Api\V1\VisitorResource;
use App\Models\Tenant\Visitor;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * The gate register, from the mobile app — the same job
 * Society\VisitorController does for the web portal, but society-wide
 * (a guard isn't scoped to a flat the way ApiController::myFlatIds()
 * scopes a resident) and returning the API envelope instead of a view.
 */
class VisitorController extends ApiController
{
    /**
     * Defaults to currently-in visitors, like the web gate register does,
     * with the same status/search filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Visitor::with(['flat.block', 'checkedInBy']);

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

        $visitors = $query->latest('check_in_at')->paginate(15);

        return $this->paginated(VisitorResource::collection($visitors), $visitors);
    }

    public function show(int $id): JsonResponse
    {
        $visitor = Visitor::with(['flat.block', 'checkedInBy'])->findOrFail($id);

        return $this->ok(new VisitorResource($visitor));
    }

    /**
     * Walk-in check-in: someone at the gate who wasn't pre-invited by a
     * resident. Mirrors Society\VisitorController::store, plus an optional
     * gate photo (camera-only on the app side - see
     * StoreGuardVisitorRequest's docblock).
     */
    public function store(StoreGuardVisitorRequest $request, NotificationService $notifications): JsonResponse
    {
        $validated = Arr::except($request->validated(), ['photo']);
        $photo = $request->file('photo');

        $visitor = Visitor::create([
            ...$validated,
            'status' => 'checked_in',
            'check_in_at' => now(),
            'checked_in_by' => $this->user()->id,
            'photo_path' => $photo?->store('visitors', 'public'),
        ]);

        $notifications->notifyFlats(
            [$validated['flat_id']],
            'visitor_arrived',
            "{$validated['visitor_name']} has arrived",
            ucfirst($validated['purpose']),
        );

        return $this->ok(new VisitorResource($visitor->load('flat.block')), 'Visitor checked in successfully.', 201);
    }

    /**
     * Turns a resident-pre-invited (pending) visitor into checked_in once
     * they actually arrive at the gate.
     */
    public function checkIn(int $id, NotificationService $notifications): JsonResponse
    {
        $visitor = Visitor::findOrFail($id);

        if (!$visitor->isPending()) {
            return $this->fail('This visitor has already been checked in.');
        }

        $visitor->update([
            'status' => 'checked_in',
            'check_in_at' => now(),
            'checked_in_by' => $this->user()->id,
        ]);

        $notifications->notifyFlats(
            [$visitor->flat_id],
            'visitor_arrived',
            "{$visitor->visitor_name} has arrived",
            ucfirst($visitor->purpose),
        );

        return $this->ok(new VisitorResource($visitor->load('flat.block')), 'Visitor checked in successfully.');
    }

    public function checkOut(int $id): JsonResponse
    {
        $visitor = Visitor::findOrFail($id);

        if (!$visitor->isCheckedIn()) {
            return $this->fail('This visitor has already checked out.');
        }

        $visitor->update([
            'status' => 'checked_out',
            'check_out_at' => now(),
            'checked_out_by' => $this->user()->id,
        ]);

        return $this->ok(new VisitorResource($visitor->load('flat.block')), 'Visitor checked out successfully.');
    }
}
