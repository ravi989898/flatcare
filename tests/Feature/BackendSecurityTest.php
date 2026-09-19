<?php

namespace Tests\Feature;

use App\Http\Middleware\SanitizeHtmlInput;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Backend hardening regression tests (see SECURITY_AUDIT.md). Safe,
 * non-destructive payloads only; no database needed.
 */
class BackendSecurityTest extends TestCase
{
    // ------------------------------------------------------- stored-XSS input

    /**
     * (data provider below)
     */
    #[DataProvider('maliciousPayloads')]
    public function test_html_and_script_payloads_are_stripped_from_input(string $payload, string $forbidden): void
    {
        $clean = SanitizeHtmlInput::sanitize($payload);

        $this->assertStringNotContainsStringIgnoringCase($forbidden, $clean, "payload survived: {$payload}");
    }

    public static function maliciousPayloads(): array
    {
        return [
            'script block' => ['Rahul<script>alert(1)</script>', '<script'],
            'script + body' => ['<script>alert(1)</script>', 'alert'],
            'img onerror' => ['<img src=x onerror=alert(1)>', 'onerror'],
            'svg onload' => ['<svg onload=alert(1)>', 'onload'],
            'svg block' => ['<svg><script>alert(1)</script></svg>', 'script'],
            'iframe' => ['<iframe src="javascript:alert(1)"></iframe>', 'iframe'],
            'object' => ['<object data="x"></object>', '<object'],
            'embed' => ['<embed src="x">', '<embed'],
            'mixed case' => ['<ScRiPt>alert(1)</sCrIpT>', 'script'],
            'spaced tag' => ['< script >alert(1)</ script >', 'script'],
            'comment' => ['a<!-- <script> -->b', '<!--'],
            'js uri only' => ['javascript:alert(1)', 'javascript:'],
            'data uri only' => ['data:text/html,<b>x</b>', 'data:'],
            'nul split' => ["<scr\0ipt>alert(1)</scr\0ipt>", '<script'],
            'anchor onclick' => ['<a href="#" onclick="alert(1)">x</a>', 'onclick'],
        ];
    }

    public function test_legitimate_text_is_left_untouched(): void
    {
        foreach (['Rahul Mehta', 'Flat A-101 (2nd floor)', '5 < 6 and 7 > 3', 'I <3 this society', 'x<y and z', "O'Brien & Sons", 'अहमदाबाद', 'Plumber @ 10:30 AM'] as $text) {
            $this->assertSame($text, SanitizeHtmlInput::sanitize($text));
        }
    }

    public function test_middleware_cleans_json_and_form_input_but_never_touches_passwords_or_tokens(): void
    {
        Route::post('/_sec/echo', fn (\Illuminate\Http\Request $r) => response()->json($r->all()));

        $response = $this->postJson('/_sec/echo', [
            'notes' => 'hello<script>alert(1)</script>',
            'visitor_name' => '<b>Rahul</b>',
            'password' => 'P<a>ss w0rd<b>',
            'otp' => '<0000>',
            'nested' => ['purpose' => '<img src=x onerror=alert(1)>Delivery'],
        ])->assertOk();

        $response->assertJsonPath('notes', 'hello');
        $response->assertJsonPath('visitor_name', 'Rahul');
        $response->assertJsonPath('nested.purpose', 'Delivery');
        // credentials are passed through byte-for-byte
        $response->assertJsonPath('password', 'P<a>ss w0rd<b>');
        $response->assertJsonPath('otp', '<0000>');
    }

    public function test_query_string_is_sanitised_too(): void
    {
        Route::get('/_sec/q', fn (\Illuminate\Http\Request $r) => response()->json(['q' => $r->query('q')]));

        $this->getJson('/_sec/q?q='.urlencode('<script>alert(1)</script>abc'))->assertJsonPath('q', 'abc');
    }

    // ------------------------------------------------------------------- CORS

    public function test_cors_does_not_allow_arbitrary_origins(): void
    {
        $response = $this->call('OPTIONS', '/api/v1/visitors', [], [], [], [
            'HTTP_ORIGIN' => 'https://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
        ]);

        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Credentials'));
    }

    public function test_cors_allows_only_explicitly_configured_origins(): void
    {
        config(['cors.allowed_origins' => ['https://app.example']]);

        $good = $this->call('OPTIONS', '/api/v1/visitors', [], [], [], [
            'HTTP_ORIGIN' => 'https://app.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        $bad = $this->call('OPTIONS', '/api/v1/visitors', [], [], [], [
            'HTTP_ORIGIN' => 'https://app.example.evil.test',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $this->assertSame('https://app.example', $good->headers->get('Access-Control-Allow-Origin'));
        // For a single configured origin the CORS layer answers with *that* origin (never an
        // echo of the caller's), so a foreign page's browser refuses the response.
        $this->assertNotSame('https://app.example.evil.test', $bad->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $bad->headers->get('Access-Control-Allow-Origin'));
    }

    // ------------------------------------------------------ API auth required

    public function test_every_api_route_except_login_flows_requires_a_token(): void
    {
        $public = ['api/v1/auth/login', 'api/v1/auth/forgot-password', 'api/v1/auth/reset-password', 'api/v1/auth/otp/request', 'api/v1/auth/otp/verify'];
        $failures = [];

        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1') || in_array($uri, $public, true)) {
                continue;
            }

            if (! in_array('api.auth', $route->gatherMiddleware(), true)) {
                $failures[] = implode('|', $route->methods()).' '.$uri;
            }
        }

        $this->assertSame([], $failures, 'API routes reachable without a token: '.implode(', ', $failures));
    }

    public function test_guard_routes_are_restricted_to_the_security_role(): void
    {
        $unprotected = [];

        foreach (app('router')->getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'api/v1/guard') && ! in_array('role:security', $route->gatherMiddleware(), true)) {
                $unprotected[] = $route->uri();
            }
        }

        $this->assertSame([], $unprotected);
    }

    public function test_abuse_prone_routes_have_dedicated_rate_limits(): void
    {
        $expected = [
            'POST api/v1/auth/login' => 'throttle:10,1',
            'POST api/v1/auth/otp/request' => 'throttle:otp-request',
            'POST api/v1/auth/forgot-password' => 'throttle:5,1',
            'POST api/v1/auth/reset-password' => 'throttle:5,1',
            'POST api/v1/visitors' => 'throttle:visitor-create',
            'POST api/v1/daily-helpers' => 'throttle:uploads',
            'POST api/v1/guard/visitors' => 'throttle:guard-visitor-create',
            'PUT api/v1/profile/password' => 'throttle:password-change',
        ];

        foreach (app('router')->getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                $key = $method.' '.$route->uri();

                if (isset($expected[$key])) {
                    $this->assertContains($expected[$key], $route->gatherMiddleware(), "$key is missing {$expected[$key]}");
                    unset($expected[$key]);
                }
            }
        }

        $this->assertSame([], $expected, 'routes not found: '.implode(', ', array_keys($expected)));
    }
}
