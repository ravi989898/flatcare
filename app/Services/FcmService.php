<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends pushes through the Firebase Cloud Messaging HTTP v1 API. The legacy
 * server-key API was shut down by Google, so this authenticates the v1 way:
 * a service-account JSON (FCM_CREDENTIALS) signs a short JWT that is
 * exchanged for an OAuth2 access token, cached until shortly before expiry.
 * No Google SDK is needed — just openssl and Laravel's HTTP client.
 *
 * send() never throws: it returns a result so a Firebase outage can never
 * break the request that triggered the notification.
 */
class FcmService
{
    public const OK = 'ok';
    /** The token is dead (uninstalled app, rotated token) - deactivate it. */
    public const INVALID_TOKEN = 'invalid_token';
    /** Transient / configuration failure - keep the token, retry later. */
    public const FAILED = 'failed';

    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function isConfigured(): bool
    {
        $credentials = $this->credentials();

        return $credentials !== null && !empty($this->projectId());
    }

    /**
     * @param  array<string, scalar|null>  $data  FCM data values must be strings; they are cast here.
     * @return array{0: string, 1: string|null}  [result, error message]
     */
    public function send(string $deviceToken, string $title, string $body, array $data = [], ?string $platform = null, string $channelId = 'visitor_requests'): array
    {
        // A notification that carries Approve/Reject buttons is sent to
        // Android as a data-only message: Android draws an FCM "notification"
        // message itself (in the background) with no way to add buttons,
        // whereas a data message wakes the app's background handler, which
        // builds the notification with its action buttons. iOS and other
        // platforms get a normal alert. The title/body are also in `data` so
        // the handler has them.
        $androidDataOnly = $platform === 'android' && !empty($data['actions']);
        $data = array_merge($data, ['title' => $title, 'body' => $body]);

        try {
            $response = Http::withToken($this->accessToken())
                ->timeout((int) config('services.fcm.timeout', 5))
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId()}/messages:send", [
                    'message' => [
                        'token' => $deviceToken,
                        'data' => array_map('strval', array_filter($data, fn ($v) => $v !== null)),
                        'android' => array_filter([
                            'priority' => 'HIGH',
                            'notification' => $androidDataOnly ? null : ['title' => $title, 'body' => $body, 'channel_id' => $channelId, 'sound' => 'default'],
                        ]),
                        'apns' => [
                            'headers' => ['apns-priority' => '10'],
                            'payload' => ['aps' => ['alert' => ['title' => $title, 'body' => $body], 'sound' => 'default']],
                        ],
                        'webpush' => ['notification' => ['title' => $title, 'body' => $body]],
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('FCM request failed', ['error' => $e->getMessage()]);

            return [self::FAILED, $e->getMessage()];
        }

        if ($response->successful()) {
            return [self::OK, null];
        }

        $error = $response->json('error') ?? [];
        $status = $error['status'] ?? '';
        $message = $error['message'] ?? "HTTP {$response->status()}";

        // UNREGISTERED = token no longer valid. INVALID_ARGUMENT can also mean
        // a malformed payload, so only treat it as a dead token when the
        // message names the token itself.
        $isDeadToken = $status === 'UNREGISTERED'
            || $response->status() === 404
            || ($status === 'INVALID_ARGUMENT' && stripos($message, 'registration token') !== false);

        Log::warning('FCM rejected message', ['status' => $status, 'message' => $message, 'http' => $response->status()]);

        return [$isDeadToken ? self::INVALID_TOKEN : self::FAILED, "{$status}: {$message}"];
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm.access_token', now()->addMinutes(50), function () {
            $credentials = $this->credentials()
                ?? throw new RuntimeException('FCM credentials are not configured.');

            $now = time();
            $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claims = [
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $unsigned = $this->base64Url(json_encode($header)).'.'.$this->base64Url(json_encode($claims));

            if (!openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Could not sign the FCM auth JWT.');
            }

            $response = Http::asForm()->timeout((int) config('services.fcm.timeout', 5))->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$this->base64Url($signature),
            ]);

            if (!$response->successful() || !$response->json('access_token')) {
                throw new RuntimeException('FCM OAuth token request failed: '.$response->status());
            }

            return $response->json('access_token');
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function credentials(): ?array
    {
        static $cache = [];

        $path = config('services.fcm.credentials');

        if (!$path) {
            return null;
        }

        // Relative paths resolve from the project root, so .env can say
        // FCM_CREDENTIALS=storage/app/firebase/service-account.json.
        if (!preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $path)) {
            $path = base_path($path);
        }

        if (!array_key_exists($path, $cache)) {
            $decoded = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
            $cache[$path] = is_array($decoded) && isset($decoded['client_email'], $decoded['private_key']) ? $decoded : null;
        }

        return $cache[$path];
    }

    private function projectId(): ?string
    {
        return config('services.fcm.project_id') ?: ($this->credentials()['project_id'] ?? null);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
