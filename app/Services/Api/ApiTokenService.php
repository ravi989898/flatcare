<?php

namespace App\Services\Api;

use App\Models\ApiToken;
use App\Models\Society;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Str;

/**
 * Issues and resolves the mobile app's bearer tokens. See api_tokens
 * migration for why these live in the main database instead of using
 * Sanctum's per-connection tokenable lookup.
 */
class ApiTokenService
{
    private const EXPIRES_AFTER_DAYS = 90;

    /**
     * Mint a new token for a tenant user and persist its hash. Returns the
     * plaintext token — the only time it's ever available, exactly like a
     * Sanctum personal access token.
     */
    public function issue(Society $society, TenantUser $user, ?string $deviceId = null, ?string $devicePlatform = null): string
    {
        $plainText = Str::random(64);

        ApiToken::create([
            'society_id' => $society->id,
            'tenant_user_id' => $user->id,
            'role_name' => $user->roles()->orderByDesc('priority')->value('name'),
            'token_hash' => hash('sha256', $plainText),
            'device_id' => $deviceId,
            'device_platform' => $devicePlatform,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
        ]);

        return $plainText;
    }

    /**
     * Resolve a plaintext bearer token to its (non-expired) database row.
     */
    public function resolve(string $plainText): ?ApiToken
    {
        $token = ApiToken::where('token_hash', hash('sha256', $plainText))->first();

        if (!$token || $token->isExpired()) {
            return null;
        }

        return $token;
    }

    public function touch(ApiToken $token): void
    {
        $token->update(['last_used_at' => now()]);
    }

    public function revoke(string $plainText): void
    {
        ApiToken::where('token_hash', hash('sha256', $plainText))->delete();
    }

    /**
     * Revoke every token issued to a tenant user (e.g. on password reset),
     * scoped to their society since tenant_user_id alone isn't unique
     * across societies.
     */
    public function revokeAllForUser(Society $society, TenantUser $user): void
    {
        ApiToken::where('society_id', $society->id)
            ->where('tenant_user_id', $user->id)
            ->delete();
    }
}
