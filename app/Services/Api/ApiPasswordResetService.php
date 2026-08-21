<?php

namespace App\Services\Api;

use App\Models\ApiPasswordReset;
use App\Models\Tenant\User as TenantUser;
use App\Services\TenantService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Forgot/reset-password for the mobile app. Needed because residents are
 * currently onboarded with a random password nobody ever sees — see
 * Society\DirectoryController::store()'s own comment — so without this flow
 * a resident could never sign in to begin with.
 */
class ApiPasswordResetService
{
    private const EXPIRES_AFTER_MINUTES = 30;

    public function __construct(
        private TenantAccountLocator $locator,
        private TenantService $tenantService,
        private ApiTokenService $tokenService,
    ) {}

    /**
     * Look up the account and create a reset token for it. Returns the
     * plaintext token to hand to the mailer, or null if no active account
     * has this email (callers should still respond as if an email was
     * sent, to avoid leaking which addresses are registered).
     */
    public function requestReset(string $email): ?string
    {
        $found = $this->locator->findByEmail($email);

        if (!$found) {
            return null;
        }

        // One live token per email+society: clear any earlier request
        // before issuing a fresh one.
        ApiPasswordReset::where('email', $email)
            ->where('society_id', $found['society']->id)
            ->delete();

        $plainText = Str::random(64);

        ApiPasswordReset::create([
            'email' => $email,
            'society_id' => $found['society']->id,
            'token_hash' => hash('sha256', $plainText),
            'expires_at' => now()->addMinutes(self::EXPIRES_AFTER_MINUTES),
        ]);

        return $plainText;
    }

    /**
     * Verify a reset token and set the new password on the matching tenant
     * user. Returns false for an invalid/expired/already-used token.
     */
    public function reset(string $email, string $plainTextToken, string $newPassword): bool
    {
        $reset = ApiPasswordReset::where('email', $email)
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (!$reset || $reset->isExpired()) {
            return false;
        }

        $society = $reset->society;
        $this->tenantService->setTenant($society);

        $user = TenantUser::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        $user->update(['password' => Hash::make($newPassword)]);

        // A password reset invalidates every device the resident was
        // signed in on — same reasoning as forcing a re-login everywhere
        // after a password change on the web.
        $this->tokenService->revokeAllForUser($society, $user);

        $reset->delete();

        return true;
    }
}
