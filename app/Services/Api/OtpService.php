<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * OTP delivery/verification for app login. config('flatcare.otp_channel')
 * picks how the code goes out:
 *
 *  - 'whatsapp' (default, cheaper than SMS): we generate the code, keep only
 *    its hash in the cache for 10 minutes and send it with the approved
 *    WhatsApp authentication template through Meta's WhatsApp Cloud API
 *    (config/services.php 'whatsapp').
 *  - 'sms': 2Factor.in (config/services.php 'twofactor') generates and checks
 *    the code itself: send() asks it to text a fresh OTP and keeps the
 *    session id it returns; verify() checks the typed code against that
 *    session. Switched off for now (cost) - set OTP_CHANNEL=sms to bring it
 *    back, nothing else needs to change.
 *
 * A matched code is used up. When the active channel has no credentials
 * (a dev machine), nothing is sent and every number's code is the fixed
 * config('flatcare.otp_default_code').
 */
class OtpService
{
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_SMS = 'sms';

    private const TWOFACTOR_BASE_URL = 'https://2factor.in/API/V1';

    private const SESSION_TTL_MINUTES = 10;

    /** Wrong guesses a WhatsApp code survives before it is thrown away. */
    private const MAX_ATTEMPTS = 5;

    private const SEND_FAILED = "Couldn't send the OTP right now. Please try again in a minute.";

    /**
     * Until WhatsApp's credentials are filled in, a server that still has a
     * 2Factor key keeps sending SMS - never drop to the fixed dev code.
     */
    public function channel(): string
    {
        if (config('flatcare.otp_channel') === self::CHANNEL_SMS) {
            return self::CHANNEL_SMS;
        }

        $whatsAppReady = config('services.whatsapp.token') && config('services.whatsapp.phone_number_id');

        return !$whatsAppReady && config('services.twofactor.api_key') ? self::CHANNEL_SMS : self::CHANNEL_WHATSAPP;
    }

    /** Whether the active channel has the credentials it needs to really send. */
    public function isLive(): bool
    {
        return $this->channel() === self::CHANNEL_SMS
            ? (bool) config('services.twofactor.api_key')
            : config('services.whatsapp.token') && config('services.whatsapp.phone_number_id');
    }

    public function send(string $mobileNumber): void
    {
        if (!$this->isLive()) {
            // The code itself is only written to the log on a local dev
            // machine; elsewhere a log must never hold a working credential.
            Log::info('OTP requested (stub - nothing sent)', [
                'channel' => $this->channel(),
                'mobile_number' => app()->environment('local') ? $mobileNumber : '******'.substr($mobileNumber, -4),
            ] + (app()->environment('local') ? ['code' => config('flatcare.otp_default_code')] : []));

            return;
        }

        $phone = $this->normalize($mobileNumber);

        $this->channel() === self::CHANNEL_SMS ? $this->sendSms($phone) : $this->sendWhatsApp($phone);
    }

    public function verify(string $mobileNumber, string $code): bool
    {
        if (!$this->isLive()) {
            return hash_equals((string) config('flatcare.otp_default_code'), $code);
        }

        $code = trim($code);
        if ($code === '' || !ctype_digit($code)) {
            return false;
        }

        $phone = $this->normalize($mobileNumber);

        return $this->channel() === self::CHANNEL_SMS
            ? $this->verifySms($phone, $code)
            : $this->verifyWhatsApp($phone, $code);
    }

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (Meta WhatsApp Cloud API, authentication template)
    |--------------------------------------------------------------------------
    */

    private function sendWhatsApp(string $phone): void
    {
        $config = config('services.whatsapp');
        $length = max(4, min(8, (int) ($config['otp_length'] ?? 6)));
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        $url = 'https://graph.facebook.com/'.($config['api_version'] ?? 'v21.0').'/'.rawurlencode($config['phone_number_id']).'/messages';

        // An authentication template has the code as its only body
        // parameter, plus the "Copy code" button that carries it again.
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => '91'.$phone,
            'type' => 'template',
            'template' => [
                'name' => $config['otp_template'],
                'language' => ['code' => $config['otp_language'] ?? 'en'],
                'components' => [
                    ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
                    ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $code]]],
                ],
            ],
        ];

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 10))
                ->withToken($config['token'])
                ->post($url, $payload);
            $ok = $response->successful() && !empty($response->json('messages.0.id'));
            $error = $ok ? null : ($response->json('error.message') ?? 'HTTP '.$response->status());
        } catch (Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        if (!$ok) {
            Log::warning('WhatsApp OTP send failed', [
                'mobile_number' => '******'.substr($phone, -4),
                'details' => $error,
            ]);

            throw ValidationException::withMessages(['mobile_number' => self::SEND_FAILED]);
        }

        Cache::put($this->cacheKey($phone), [
            'hash' => hash_hmac('sha256', $code, (string) config('app.key')),
            'attempts' => 0,
        ], now()->addMinutes(self::SESSION_TTL_MINUTES));
    }

    private function verifyWhatsApp(string $phone, string $code): bool
    {
        $key = $this->cacheKey($phone);
        $entry = Cache::get($key);
        if (!is_array($entry) || empty($entry['hash'])) {
            return false;
        }

        if (hash_equals($entry['hash'], hash_hmac('sha256', $code, (string) config('app.key')))) {
            Cache::forget($key);

            return true;
        }

        $entry['attempts'] = ($entry['attempts'] ?? 0) + 1;
        $entry['attempts'] >= self::MAX_ATTEMPTS
            ? Cache::forget($key)
            : Cache::put($key, $entry, now()->addMinutes(self::SESSION_TTL_MINUTES));

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | SMS (2Factor.in) - kept for OTP_CHANNEL=sms
    |--------------------------------------------------------------------------
    */

    private function sendSms(string $phone): void
    {
        $apiKey = config('services.twofactor.api_key');

        // Without an approved (DLT) template name 2Factor reads the OTP out
        // over a voice call instead of texting it - never send without one.
        $template = config('services.twofactor.otp_template');
        if (!$template) {
            Log::error('2Factor OTP not sent: TWOFACTOR_OTP_TEMPLATE is empty (OTP would go as a voice call).');

            throw ValidationException::withMessages(['mobile_number' => self::SEND_FAILED]);
        }

        $url = self::TWOFACTOR_BASE_URL.'/'.rawurlencode($apiKey).'/SMS/'.$phone.'/AUTOGEN/'.rawurlencode($template);

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

            throw ValidationException::withMessages(['mobile_number' => self::SEND_FAILED]);
        }

        Cache::put($this->cacheKey($phone), $body['Details'], now()->addMinutes(self::SESSION_TTL_MINUTES));
    }

    private function verifySms(string $phone, string $code): bool
    {
        $apiKey = config('services.twofactor.api_key');
        $sessionId = Cache::get($this->cacheKey($phone));
        if (!is_string($sessionId) || $sessionId === '') {
            return false;
        }

        try {
            $body = Http::timeout((int) config('services.twofactor.timeout', 10))
                ->get(self::TWOFACTOR_BASE_URL.'/'.rawurlencode($apiKey).'/SMS/VERIFY/'.rawurlencode($sessionId).'/'.$code)
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
        return 'otp:'.$this->channel().':'.$phone;
    }
}
