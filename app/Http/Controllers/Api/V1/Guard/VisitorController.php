<?php

namespace App\Http\Controllers\Api\V1\Guard;

use App\Exceptions\VisitorTransitionException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Guard\StoreGuardVisitorRequest;
use App\Http\Resources\Api\V1\VisitorResource;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Visitor;
use App\Services\VisitorWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * The gate register, from the mobile app - the same job
 * Society\VisitorController does for the web portal, but society-wide
 * (a guard isn't scoped to a flat the way ApiController::myFlatIds()
 * scopes a resident) and returning the API envelope instead of a view.
 *
 * "Society-wide" is also the security boundary: every society has its own
 * database, and AuthenticateApiToken points the connection at the caller's
 * society, so a guard can only ever see or touch that society's visitors.
 * Status changes go through App\Services\VisitorWorkflow, which validates
 * the current status under a row lock and writes the audit trail.
 */
class VisitorController extends ApiController
{
    public function __construct(private VisitorWorkflow $workflow) {}

    /**
     * Defaults to the requests the gate still has to act on (pending,
     * approved, currently inside). `status` may also be a single status or
     * `all`; `search` matches name, phone, vehicle, pass code and flat.
     *
     * `kind` narrows to one gate-duty list:
     *  - `requests`: guard-raised entry requests still awaiting the resident
     *  - `passes`:   residents' gate passes / pre-approvals still usable
     *                (see Visitor::scopeUsablePasses - a dated pass stays
     *                listed until it expires, even after the visitor used it)
     * `date` (Y-m-d) keeps visitors created or entered on that day - the
     * Visitor Log's date filter.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:active,all,'.implode(',', Visitor::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
            'kind' => ['nullable', 'in:requests,passes'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = Visitor::with(['flat.block', 'gateKeeper', 'checkedInBy', 'invitedBy']);
        $kind = $request->string('kind')->value();

        if ($kind === 'requests') {
            $query->where('status', 'pending')->whereNull('invited_by_user_id');
        } elseif ($kind === 'passes') {
            $query->usablePasses();
        } else {
            $status = $request->string('status')->trim()->value() ?: 'active';
            if ($status === 'active') {
                $query->activeRequests();
            } elseif ($status !== 'all') {
                $query->status($status);
            }
        }

        if ($date = $request->string('date')->value()) {
            $query->where(fn ($q) => $q->whereDate('created_at', $date)->orWhereDate('check_in_at', $date));
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_phone', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('pass_code', 'like', "%{$search}%")
                    ->orWhereHas('flat', fn ($f) => $f->where('flat_number', 'like', "%{$search}%"));
            });
        }

        $visitors = $kind === 'passes'
            ? $query->orderByRaw('expected_at IS NULL')->orderBy('expected_at')->latest('id')->paginate(30)
            : $query->latest('created_at')->latest('id')->paginate(30);

        return $this->paginated(VisitorResource::collection($visitors), $visitors);
    }

    /**
     * Counts for the gate-duty tiles on the guard home screen.
     */
    public function summary(): JsonResponse
    {
        $today = now()->toDateString();

        return $this->ok([
            'pending_requests' => Visitor::where('status', 'pending')->whereNull('invited_by_user_id')
                ->whereDate('created_at', $today)->count(),
            'visitors_today' => Visitor::where(fn ($q) => $q->whereDate('created_at', $today)->orWhereDate('check_in_at', $today))
                ->count(),
            'inside_now' => Visitor::currentlyIn()->count(),
            'active_passes' => Visitor::usablePasses()->count(),
            'closed_houses' => Flat::active()->where('house_closed', true)->count(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $visitor = Visitor::with(['flat.block', 'gateKeeper', 'checkedInBy'])->findOrFail($id);

        return $this->ok(new VisitorResource($visitor));
    }

    /**
     * The gate's QR scanner (or a pass code typed in by hand): looks the
     * pass up and says whether it is valid right now. Always 200 - `result`
     * carries the verdict (valid / upcoming / inside / expired / used /
     * cancelled / invalid) so the app shows one screen for every outcome.
     * Cancelled passes are looked up too, so the guard is told why.
     */
    public function verifyPass(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:100']]);

        $code = trim($request->string('code')->value());
        if (stripos($code, Visitor::PASS_QR_PREFIX) === 0) {
            $code = substr($code, strlen(Visitor::PASS_QR_PREFIX));
        }
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        $visitor = $code === '' ? null : Visitor::withTrashed()
            ->with(['flat.block', 'gateKeeper', 'checkedInBy', 'invitedBy'])
            ->whereNotNull('invited_by_user_id')
            ->where('pass_code', $code)
            ->latest('id')
            ->first();

        if (!$visitor) {
            return $this->ok([
                'result' => 'invalid',
                'message' => 'No gate pass matches this code - do not allow entry.',
                'visitor' => null,
            ]);
        }

        return $this->ok([
            'result' => $visitor->passStatus(),
            'message' => VisitorWorkflow::passMessage($visitor),
            'visitor' => new VisitorResource($visitor),
        ]);
    }

    /**
     * Raise an entry request for someone at the gate the resident didn't
     * pre-invite. Unless the guard explicitly sends `requires_approval:
     * false` (a known delivery person etc.), the visitor lands `pending`
     * and every authorised resident of the flat gets an actionable push;
     * the resident's decision is pushed back to this guard. `visitor_name`,
     * `purpose` (the visitor type) and `notes` (what they've come for) plus
     * an optional camera photo are stored; the block is taken from the flat,
     * never trusted from the client.
     */
    public function store(StoreGuardVisitorRequest $request): JsonResponse
    {
        $validated = Arr::except($request->validated(), ['photo', 'requires_approval', 'flat_id', 'block_id']);
        $requiresApproval = $request->boolean('requires_approval', true);

        $flat = Flat::with('block')->active()->find($request->integer('flat_id'));

        if (!$flat) {
            return $this->fail('That flat was not found in this society.', 404);
        }

        // A block, if sent, must be this flat's block - guards a mismatched
        // picker selection rather than silently routing to another flat.
        if ($request->filled('block_id') && (int) $request->input('block_id') !== (int) $flat->block_id) {
            return $this->fail('The selected flat does not belong to the selected block.', 422);
        }

        // The resident's Visitor > Settings: a closed house takes no
        // walk-ins, and "guests only if I approve" turns every guest entry
        // into an approval request whether or not the guard asked for one.
        if ($flat->house_closed) {
            return $this->fail("{$flat->display_label} is marked closed by the resident - entry is not allowed.");
        }
        if ($flat->guest_approval_required && $validated['purpose'] === 'guest') {
            $requiresApproval = true;
        }

        if ($requiresApproval && !FlatResident::active()->where('flat_id', $flat->id)->exists()) {
            return $this->fail("No resident is registered on {$flat->display_label}, so nobody can approve this visitor.");
        }

        $visitor = $this->workflow->createRequest(
            $this->user(),
            $flat,
            $validated,
            $requiresApproval,
            $request->file('photo')?->store('visitors', 'public'),
            $request->ip(),
        );

        return $this->ok(
            new VisitorResource($visitor->load(['flat.block', 'gateKeeper'])),
            $requiresApproval ? 'Entry request sent to the resident.' : 'Visitor checked in successfully.',
            201,
        );
    }

    /**
     * Mark an APPROVED visitor (or one holding a resident's pre-approved
     * pass) as ENTERED. Routed as both /entry and the older /check-in.
     */
    public function checkIn(Request $request, int $id): JsonResponse
    {
        try {
            $visitor = $this->workflow->enter($id, $this->user(), $request->ip());
        } catch (VisitorTransitionException $e) {
            return $this->fail($e->getMessage(), $e->httpStatus);
        }

        return $this->ok(new VisitorResource($visitor->load(['flat.block', 'gateKeeper'])), 'Visitor entered.');
    }

    /**
     * Mark an ENTERED visitor as EXITED. Routed as both /exit and the older
     * /check-out.
     */
    public function checkOut(Request $request, int $id): JsonResponse
    {
        try {
            $visitor = $this->workflow->exit($id, $this->user(), $request->ip());
        } catch (VisitorTransitionException $e) {
            return $this->fail($e->getMessage(), $e->httpStatus);
        }

        return $this->ok(new VisitorResource($visitor->load(['flat.block', 'gateKeeper'])), 'Visitor exited.');
    }
}
