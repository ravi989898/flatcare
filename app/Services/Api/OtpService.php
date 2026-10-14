<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * SMS OTP for app login through 2Factor.in (config/services.php
 * 'twofactor'). 2Factor generates and checks the code itself: send() asks it
 * to text a fresh OTP with the approved DLT template and keeps the session
 * id it returns for 10 minutes; verify() checks the typed code against that
 * session. A matched code is used up, and a session is thrown away after
 * MAX_ATTEMPTS wrong guesses so the code can't be brute-forced.
 *
 * Without TWOFACTOR_API_KEY nothing is sent: off production every number's
 * code is the fixed config('flatcare.otp_default_code') (local dev); in
 * production OTP login refuses to work rather than accept a known code.
 */
class OtpService
{
    private const BASE_URL = 'https://2factor.in/API/V1';

    private const SESSION_TTL_MINUTES = 10;

    /** Wrong guesses a session survives before it is thrown away. */
    private const MAX_ATTEMPTS = 5;

    /** Minimum gap between two OTPs to the same number. */
    private const RESEND_COOLDOWN_SECONDS = 30;

    private const SEND_FAILED = "Couldn't send the OTP right now. Please try again in a minute.";

    public function isLive(): bool
    {
        return filled(config('services.twofactor.api_key'));
    }

    /**
     * The 10-digit Indian mobile number in $mobileNumber (+91 / 91 / 0
     * prefixes, spaces and dashes allowed), or null when it isn't one.
     */
    public static function normalize(string $mobileNumber): ?string
    {
        $compact = preg_replace('/[\s\-()]/', '', trim($mobileNumber));

        return preg_match('/^(?:\+?91|0)?([6-9]\d{9})$/', $compact, $m) ? $m[1] : null;
    }

    /**
     * @throws ValidationException when the number is invalid, asked again
     *                             too soon, or 2Factor didn't send the SMS
     */
    public function send(string $mobileNumber): void
    {
        $phone = self::normalize($mobileNumber)
            ?? throw ValidationException::withMessages(['mobile_number' => 'Enter a valid 10-digit mobile number.']);

        if (!$this->isLive()) {
            if (app()->isProduction()) {
                Log::error('OTP not sent: TWOFACTOR_API_KEY is not set.');

                throw ValidationException::withMessages(['mobile_number' => self::SEND_FAILED]);
            }

            Log::info('OTP requested (stub - nothing sent)', [
                'mobile_number' => $this->mask($phone),
            ] + (app()->environment('local') ? ['code' => config('flatcare.otp_default_code')] : []));

            return;
        }

        // Without an approved (DLT) template name 2Factor reads the OTP out
        // over a voice call instead of texting it - never send without one.
        $template = (string) config('services.twofactor.otp_template');
        if ($template === '') {
            Log::error('2Factor OTP not sent: TWOFACTOR_OTP_TEMPLATE is empty (OTP would go as a voice call).');

            throw ValidationException::withMessages(['mobile_number' => self::SEND_FAILED]);
        }

        if (!Cache::add($this->cooldownKey($phone), true, self::RESEND_COOLDOWN_SECONDS)) {
            throw ValidationException::withMessages([
                'mobile_number' => 'Please wait '.self::RESEND_COOLDOWN_SECONDS.' seconds before asking for a new OTP.',
            ]);
        }

        $sessionId = null;
        $error = null;

        try {
            $response = $this->client()->get($this->url('SMS', '91'.$phone, 'AUTOGEN', $template));
            $details = $response->json('Details');

            if ($response->successful() && $response->json('Status') === 'Success'
                && is_string($details) && preg_match('/^[A-Za-z0-9\-]{8,100}$/', $details)) {
                $sessionId = $details;
            } else {
                $error = is_string($details) ? $details : 'HTTP '.$response->status();
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        if ($sessionId === null) {
            Cache::forget($this->cooldownKey($phone));
            Log::warning('2Factor OTP send failed', [
                'mobile_number' => $this->mask($phone),
                'details' => $this->redact($error),
            ]);

            throw ValidationException::withMessages(['mobile_number' => self::SEND_FAILED]);
        }

        Cache::put($this->sessionKey($phone), [
            'session_id' => $sessionId,
            'attempts' => 0,
        ], now()->addMinutes(self::SESSION_TTL_MINUTES));
    }

    public function verify(string $mobileNumber, string $code): bool
    {
        $code = trim($code);
        $phone = self::normalize($mobileNumber);

        if ($phone === null || !preg_match('/^\d{4,8}$/', $code)) {
            return false;
        }

        if (!$this->isLive()) {
            return !app()->isProduction()
                && hash_equals((string) config('flatcare.otp_default_code'), $code);
        }

        $key = $this->sessionKey($phone);
        $entry = Cache::get($key);
        if (!is_array($entry) || !is_string($entry['session_id'] ?? null)) {
            return false;
        }

        try {
            $response = $this->client()->get($this->url('SMS', 'VERIFY', $entry['session_id'], $code));
        } catch (Throwable $e) {
            // 2Factor unreachable - not the user's guess, so no attempt used.
            Log::warning('2Factor OTP verify failed', ['details' => $this->redact($e->getMessage())]);

            return false;
        }

        $matched = $response->successful()
            && $response->json('Status') === 'Success'
            && $response->json('Details') === 'OTP Matched';

        if ($matched) {
            Cache::forget($key);

            return true;
        }

        $entry['attempts'] = (int) ($entry['attempts'] ?? 0) + 1;
        $entry['attempts'] >= self::MAX_ATTEMPTS
            ? Cache::forget($key)
            : Cache::put($key, $entry, now()->addMinutes(self::SESSION_TTL_MINUTES));

        return false;
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::acceptJson()->timeout((int) config('services.twofactor.timeout', 10));
    }

    private function url(string ...$segments): string
    {
        return self::BASE_URL.'/'.implode('/', array_map('rawurlencode', [
            (string) config('services.twofactor.api_key'),
            ...$segments,
        ]));
    }

    /** The API key is part of every 2Factor URL - keep it out of the logs. */
    private function redact(?string $message): ?string
    {
        $apiKey = (string) config('services.twofactor.api_key');

        return $message === null || $apiKey === '' ? $message : str_replace($apiKey, '***', $message);
    }

    private function mask(string $phone): string
    {
        return '******'.substr($phone, -4);
    }

    private function sessionKey(string $phone): string
    {
        return 'otp:sms:'.$phone;
    }

    private function cooldownKey(string $phone): string
    {
        return 'otp:cooldown:'.$phone;
    }
}
