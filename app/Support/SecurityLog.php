<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Single entry point for security-relevant events (failed logins, lockouts,
 * authorization failures, blocked uploads, rate-limit hits). They go to the
 * dedicated `security` log channel (storage/logs/security-*.log) so they can
 * be monitored or shipped separately from ordinary application logs.
 *
 * Never pass passwords, OTP codes, bearer tokens or reset codes in $context —
 * anything whose key looks like a secret is dropped defensively below.
 */
class SecurityLog
{
    private const SECRET_KEY_PATTERN = '/pass|token|secret|otp|code|authorization|cookie|key/i';

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        self::write('warning', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $event, array $context): void
    {
        $request = request();

        $context = array_filter(
            $context,
            fn ($value, $key) => ! preg_match(self::SECRET_KEY_PATTERN, (string) $key),
            ARRAY_FILTER_USE_BOTH
        );

        try {
            Log::channel('security')->{$level}($event, $context + [
                'ip' => $request?->ip(),
                'method' => $request?->method(),
                // Path only — never the query string, which can carry tokens.
                'path' => $request?->path(),
                'user_agent' => mb_substr((string) $request?->userAgent(), 0, 200),
            ]);
        } catch (\Throwable) {
            // A logging failure must never break the request being logged.
        }
    }
}
