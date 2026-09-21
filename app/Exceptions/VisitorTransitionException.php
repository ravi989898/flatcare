<?php

namespace App\Exceptions;

use App\Models\Tenant\Visitor;
use RuntimeException;

/**
 * A visitor status change that the workflow refuses (wrong current status,
 * already decided by someone else, not your flat). Carries the HTTP status
 * the API should answer with: 409 when the request has moved on / is in the
 * wrong state, 404 when it isn't visible to the caller.
 */
class VisitorTransitionException extends RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 409)
    {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('Visitor request not found.', 404);
    }

    public static function because(string $message): self
    {
        return new self($message, 409);
    }

    public static function alreadyDecided(Visitor $visitor): self
    {
        $verb = match ($visitor->status) {
            'approved' => 'approved',
            'denied' => 'rejected',
            default => 'processed',
        };

        return new self("This visitor request has already been {$verb}.", 409);
    }
}
