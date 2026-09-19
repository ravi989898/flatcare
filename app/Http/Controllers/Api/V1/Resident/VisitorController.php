<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\StoreVisitorInviteRequest;
use App\Http\Resources\Api\V1\VisitorResource;
use App\Models\Tenant\Visitor;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Check-in/check-out is still a gate-security action done from the web
 * portal (Society\VisitorController). Residents can see who came/is at
 * their flat, and — via store() — pre-approve an expected visitor
 * ("Invite Visitor" / "Gate Pass" in the mobile app), which lands as a
 * `pending` row the gate desk later checks in when the visitor arrives.
 *
 * approve()/reject() handle the other kind of `pending` row: one the
 * *guard* raised (Api\V1\Guard\VisitorController::store with
 * `requires_approval`) because an unexpected visitor is waiting at the
 * gate right now. Those are distinguished from a resident's own
 * self-invite by `invited_by_user_id` being null (see
 * VisitorResource::awaiting_approval).
 */
class VisitorController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        // Query-string filters are client input: validate type/length/allowed
        // values up front so junk gets a clean 422 instead of a 500.
        $request->validate([
            'status' => ['nullable', 'in:'.implode(',', Visitor::STATUSES)],
            'kind' => ['nullable', 'in:'.implode(',', Visitor::ENTRY_KINDS)],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = Visitor::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->status($request->string('status')->trim()->value() ?: null);

        if ($kind = $request->string('kind')->trim()->value()) {
            $query->where('entry_kind', $kind)->whereNotNull('invited_by_user_id');
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('flat', fn ($f) => $f->where('flat_number', 'like', "%{$search}%"));
            });
        }

        // The My Visitors date filter. A visitor's date is when they came,
        // or - for a pass that hasn't been used yet - when they're expected.
        $from = $request->date('from');
        $to = $request->date('to');
        if ($from || $to) {
            $query->where(function ($q) use ($from, $to) {
                $day = 'COALESCE(check_in_at, expected_at, created_at)';
                if ($from) {
                    $q->whereRaw("$day >= ?", [$from->startOfDay()]);
                }
                if ($to) {
                    $q->whereRaw("$day <= ?", [$to->copy()->endOfDay()]);
                }
            });
        }

        $visitors = $query->orderByRaw('COALESCE(check_in_at, expected_at, created_at) DESC')->paginate(50);

        return $this->paginated(VisitorResource::collection($visitors), $visitors);
    }

    public function show(int $id): JsonResponse
    {
        $visitor = Visitor::with('flat.block')
            ->whereIn('flat_id', $this->myFlatIds())
            ->findOrFail($id);

        return $this->ok(new VisitorResource($visitor));
    }

    public function store(StoreVisitorInviteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!in_array((int) $validated['flat_id'], $this->myFlatIds(), true)) {
            return $this->fail('You can only invite a visitor to your own flat.', 403);
        }

        $visitor = Visitor::create([
            ...Arr::except($validated, ['photo']),
            'photo_path' => $request->file('photo')?->store('visitors', 'public'),
            'status' => 'pending',
            'invited_by_user_id' => $this->user()->id,
            'pass_code' => strtoupper(Str::random(6)),
        ]);

        return $this->ok(new VisitorResource($visitor->load('flat.block')), 'Visitor invited successfully.', 201);
    }

    /**
     * A resident withdrawing their own still-unused pass / pre-approval.
     * Guard-raised requests aren't theirs to delete - those are answered
     * with approve()/reject() instead.
     */
    public function destroy(int $id): JsonResponse
    {
        $visitor = Visitor::whereIn('flat_id', $this->myFlatIds())->findOrFail($id);

        if (!$visitor->isPending() || $visitor->invited_by_user_id === null) {
            return $this->fail('Only a pass you created that has not been used yet can be cancelled.');
        }

        $visitor->delete();

        return $this->ok(null, 'Pass cancelled.');
    }

    public function approve(int $id, NotificationService $notifications): JsonResponse
    {
        $visitor = Visitor::whereIn('flat_id', $this->myFlatIds())->findOrFail($id);

        if (!$visitor->isPending() || $visitor->invited_by_user_id !== null) {
            return $this->fail('This visitor request can no longer be approved.');
        }

        $visitor->update(['status' => 'checked_in', 'check_in_at' => now()]);

        if ($visitor->checked_in_by) {
            $notifications->notify(
                $visitor->checked_in_by,
                'visitor_request_approved',
                "{$visitor->visitor_name} approved — let them in",
            );
        }

        return $this->ok(new VisitorResource($visitor->load('flat.block')), 'Visitor approved.');
    }

    public function reject(int $id, NotificationService $notifications): JsonResponse
    {
        $visitor = Visitor::whereIn('flat_id', $this->myFlatIds())->findOrFail($id);

        if (!$visitor->isPending() || $visitor->invited_by_user_id !== null) {
            return $this->fail('This visitor request can no longer be rejected.');
        }

        $visitor->update(['status' => 'denied']);

        if ($visitor->checked_in_by) {
            $notifications->notify(
                $visitor->checked_in_by,
                'visitor_request_rejected',
                "{$visitor->visitor_name} was denied entry",
            );
        }

        return $this->ok(new VisitorResource($visitor->load('flat.block')), 'Visitor request rejected.');
    }
}
