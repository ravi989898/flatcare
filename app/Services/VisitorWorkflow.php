<?php

namespace App\Services;

use App\Exceptions\VisitorTransitionException;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\User;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorStatusHistory;
use Illuminate\Support\Facades\DB;

/**
 * The visitor approval state machine:
 *
 *   PENDING --resident approves--> APPROVED --guard--> ENTERED --guard--> EXITED
 *   PENDING --resident rejects---> REJECTED
 *
 * (stored as pending / approved / checked_in / checked_out / denied — see
 * Visitor::STATUS_LABELS). Every transition runs in one transaction that
 * locks the visitor row, re-checks the current status and the actor's
 * authority against the locked row, updates it and appends a
 * visitor_status_histories row. Two devices answering the same request at
 * once therefore serialise on the lock: the first wins, the second sees the
 * new status and gets a VisitorTransitionException. Nothing here trusts a
 * client-supplied user, flat or status — callers pass the authenticated
 * user and a visitor id, and everything else is derived server-side.
 *
 * Notifications go out only AFTER the transaction commits, and a push
 * failure is swallowed by NotificationService — the stored request and its
 * status are never affected by delivery problems.
 */
class VisitorWorkflow
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * A guard raises an entry request for a walk-in the resident hasn't
     * pre-approved (or, with $requiresApproval false, logs them straight in).
     *
     * @param  array<string, mixed>  $attributes  validated visitor fields (name, phone, purpose, ...)
     */
    public function createRequest(User $guard, Flat $flat, array $attributes, bool $requiresApproval, ?string $photoPath, ?string $ip = null): Visitor
    {
        $visitor = DB::connection('society')->transaction(function () use ($guard, $flat, $attributes, $requiresApproval, $photoPath, $ip) {
            $visitor = Visitor::create([
                ...$attributes,
                'flat_id' => $flat->id,
                // Always taken from the flat itself, never from the request.
                'block_id' => $flat->block_id,
                'status' => $requiresApproval ? 'pending' : 'checked_in',
                'check_in_at' => $requiresApproval ? null : now(),
                'gate_keeper_id' => $guard->id,
                'checked_in_by' => $guard->id,
                'photo_path' => $photoPath,
            ]);

            $this->record($visitor, null, $visitor->status, $requiresApproval ? 'requested' : 'entered', $guard, $ip);

            return $visitor;
        });

        if ($requiresApproval) {
            $this->notifications->notifyFlatResidents(
                $flat->id,
                'visitor_request',
                'Visitor Approval Request',
                "{$visitor->visitor_name} is waiting at the gate. Please approve or reject the visitor request.",
                ['visitor_id' => $visitor->id, 'flat_id' => $flat->id, 'actions' => 'approve,reject'],
                $visitor->id,
            );
        } else {
            $this->notifications->notifyFlats(
                [$flat->id],
                'visitor_arrived',
                "{$visitor->visitor_name} has arrived",
                ucfirst($visitor->purpose),
            );
        }

        return $visitor;
    }

    /**
     * A resident answers a guard-raised request. Only an active resident of
     * the request's own flat may do so, and only while it is still PENDING.
     */
    public function respond(int $visitorId, User $resident, bool $approve, ?string $ip = null): Visitor
    {
        $visitor = DB::connection('society')->transaction(function () use ($visitorId, $resident, $approve, $ip) {
            $visitor = Visitor::lockForUpdate()->find($visitorId);

            // A request that isn't for one of the caller's own flats is
            // indistinguishable from one that doesn't exist.
            if (!$visitor || !$this->isResidentOf($resident, $visitor->flat_id)) {
                throw VisitorTransitionException::notFound();
            }

            if (!$visitor->isGuardRequest()) {
                throw VisitorTransitionException::because('This is a pass you created yourself - there is nothing to approve.');
            }

            if (!$visitor->isPending()) {
                throw VisitorTransitionException::alreadyDecided($visitor);
            }

            $now = now();
            $visitor->update($approve
                ? ['status' => 'approved', 'approved_by' => $resident->id, 'approved_at' => $now]
                : ['status' => 'denied', 'rejected_by' => $resident->id, 'rejected_at' => $now]);

            $this->record($visitor, 'pending', $visitor->status, $approve ? 'approved' : 'rejected', $resident, $ip);

            return $visitor;
        });

        // Tell the guard who raised it (not whichever guard is on shift now).
        if ($visitor->gate_keeper_id) {
            $this->notifications->notify(
                $visitor->gate_keeper_id,
                $approve ? 'visitor_request_approved' : 'visitor_request_rejected',
                $approve ? 'Visitor Approved' : 'Visitor Rejected',
                $approve
                    ? "{$visitor->visitor_name} has been approved by the resident. You can allow the visitor to enter."
                    : "{$visitor->visitor_name} has been rejected by the resident. Do not allow the visitor to enter.",
                ['visitor_id' => $visitor->id, 'flat_id' => $visitor->flat_id],
                $visitor->id,
                push: true,
            );
        }

        return $visitor;
    }

    /**
     * The guard lets an APPROVED visitor in (or a visitor holding a
     * resident's own pre-approved pass). A request still awaiting the
     * resident's decision, or one they rejected, can never be entered.
     */
    public function enter(int $visitorId, User $guard, ?string $ip = null): Visitor
    {
        $visitor = DB::connection('society')->transaction(function () use ($visitorId, $guard, $ip) {
            $visitor = Visitor::lockForUpdate()->findOrFail($visitorId);

            $hasPass = $visitor->isPending() && !$visitor->isGuardRequest();

            if ($visitor->status !== 'approved' && !$hasPass) {
                throw match ($visitor->status) {
                    'pending' => VisitorTransitionException::because('The resident has not approved this visitor yet.'),
                    'denied' => VisitorTransitionException::because('The resident rejected this visitor - do not allow entry.'),
                    default => VisitorTransitionException::because("This visitor is already {$visitor->status_label}."),
                };
            }

            $from = $visitor->status;
            $visitor->update(['status' => 'checked_in', 'check_in_at' => now(), 'checked_in_by' => $guard->id]);
            $this->record($visitor, $from, 'checked_in', 'entered', $guard, $ip);

            return $visitor;
        });

        $this->notifications->notifyFlats(
            [$visitor->flat_id],
            'visitor_arrived',
            "{$visitor->visitor_name} has arrived",
            ucfirst($visitor->purpose),
        );

        return $visitor;
    }

    public function exit(int $visitorId, User $guard, ?string $ip = null): Visitor
    {
        return DB::connection('society')->transaction(function () use ($visitorId, $guard, $ip) {
            $visitor = Visitor::lockForUpdate()->findOrFail($visitorId);

            if ($visitor->status !== 'checked_in') {
                throw VisitorTransitionException::because(
                    $visitor->status === 'checked_out'
                        ? 'This visitor has already exited.'
                        : 'Only a visitor who has entered can be marked as exited.'
                );
            }

            $visitor->update(['status' => 'checked_out', 'check_out_at' => now(), 'checked_out_by' => $guard->id]);
            $this->record($visitor, 'checked_in', 'checked_out', 'exited', $guard, $ip);

            return $visitor;
        });
    }

    private function isResidentOf(User $user, int $flatId): bool
    {
        return FlatResident::query()->active()->where('user_id', $user->id)->where('flat_id', $flatId)->exists();
    }

    private function record(Visitor $visitor, ?string $from, string $to, string $action, User $by, ?string $ip): void
    {
        VisitorStatusHistory::create([
            'visitor_id' => $visitor->id,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'changed_by' => $by->id,
            'ip_address' => $ip,
        ]);
    }
}
