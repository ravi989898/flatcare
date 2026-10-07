<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * OTP delivery/verification for app login through 2Factor.in
 * (config/services.php 'twofactor'). 2Factor generates and checks the code
 * itself: send() asks it to text a fresh OTP and keeps the session id it
 * returns in the cache for 10 minutes; verify() checks the typed code
 * against that session, and a matched session is used up.
 *
 * With no TWOFACTOR_API_KEY (a dev machine), no SMS is sent and every
 * number's code is the fixed config('flatcare.otp_default_code').
 */
class OtpService
{
    private const BASE_URL = 'https://2factor.in/API/V1';

    private const SESSION_TTL_MINUTES = 10;

    public function send(string $mobileNumber): void
    {
        $apiKey = config('services.twofactor.api_key');

        if (!$apiKey) {
            // The code itself is only written to the log on a local dev
            // machine; elsewhere a log must never hold a working credential.
            Log::info('OTP requested (stub - no SMS sent)', [
                'mobile_number' => app()->environment('local') ? $mobileNumber : '******'.substr($mobileNumber, -4),
            ] + (app()->environment('local') ? ['code' => config('flatcare.otp_default_code')] : []));

            return;
        }

        $phone = $this->normalize($mobileNumber);
        $url = self::BASE_URL.'/'.rawurlencode($apiKey).'/SMS/'.$phone.'/AUTOGEN';
        if ($template = config('services.twofactor.otp_template')) {
            $url .= '/'.rawurlencode($template);
        }

        try {
            $response = Http::timeout((int) config('services.twofactor.timeout', 10))->get($url);
            $body = $response->json() ?? [];
        } catch (Throwable $e) {
            $body = ['Status' => 'Error', 'Details' => $e->getMessage()];
        }

        if (($body['Status'] ?? null) !== 'Success' || empty($body['Details'])) {
            Log::warning('2Factor OTP send failed', [
                'mobile_number' => '******'.substr($phone, -4),
                'details' => $body['Details'] ?? null,
            ]);

            throw ValidationException::withMessages([
                'mobile_number' => "Couldn't send the OTP right now. Please try again in a minute.",
            ]);
        }

        Cache::put($this->cacheKey($phone), $body['Details'], now()->addMinutes(self::SESSION_TTL_MINUTES));
    }

    public function verify(string $mobileNumber, string $code): bool
    {
        $apiKey = config('services.twofactor.api_key');

        if (!$apiKey) {
            return hash_equals((string) config('flatcare.otp_default_code'), $code);
        }

        $code = trim($code);
        if ($code === '' || !ctype_digit($code)) {
            return false;
        }

        $phone = $this->normalize($mobileNumber);
        $sessionId = Cache::get($this->cacheKey($phone));
        if (!$sessionId) {
            return false;
        }

        try {
            $body = Http::timeout((int) config('services.twofactor.timeout', 10))
                ->get(self::BASE_URL.'/'.rawurlencode($apiKey).'/SMS/VERIFY/'.rawurlencode($sessionId).'/'.$code)
                ->json() ?? [];
        } catch (Throwable $e) {
            Log::warning('2Factor OTP verify failed', ['details' => $e->getMessage()]);

            return false;
        }

        $matched = ($body['Status'] ?? null) === 'Success';
        if ($matched) {
            Cache::forget($this->cacheKey($phone));
        }

        return $matched;
    }

    /**
     * Last 10 digits - the app and the flat/guard records store Indian
     * mobile numbers in different shapes (+91, 0-prefixed, spaces).
     */
    private function normalize(string $mobileNumber): string
    {
        return substr(preg_replace('/\D/', '', $mobileNumber), -10);
    }

    private function cacheKey(string $phone): string
    {
        return 'otp:2factor:'.$phone;
    }
}
