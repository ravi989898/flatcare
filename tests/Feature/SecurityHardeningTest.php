<?php

namespace Tests\Feature;

use App\Rules\SafeUploadedFile;
use App\Support\SvgSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Regression tests for the security hardening (see SECURITY_REPORT.md).
 * They use only safe, non-destructive payloads and need no database.
 */
class SecurityHardeningTest extends TestCase
{
    // ---------------------------------------------------------------- headers

    public function test_web_responses_carry_a_strict_csp_and_hardening_headers(): void
    {
        Route::get('/_sec/page', fn () => 'ok');

        $response = $this->get('/_sec/page');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertDoesNotMatchRegularExpression("/script-src[^;]*'unsafe-inline'/", $csp);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
    }

    public function test_api_responses_are_locked_down_and_never_cached(): void
    {
        Route::get('/api/_sec/ping', fn () => response()->json(['ok' => true]));

        $response = $this->getJson('/api/_sec/ping');

        $response->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_hsts_and_upgrade_insecure_requests_only_on_https(): void
    {
        Route::get('/_sec/https', fn () => 'ok');

        $plain = $this->get('/_sec/https');
        $this->assertNull($plain->headers->get('Strict-Transport-Security'));

        $secure = $this->get('https://localhost/_sec/https');
        $this->assertNotNull($secure->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('upgrade-insecure-requests', $secure->headers->get('Content-Security-Policy'));
    }

    // ---------------------------------------------------------------- uploads

    private function validate(UploadedFile $file, array $extensions): bool
    {
        return ! Validator::make(['f' => $file], ['f' => [new SafeUploadedFile($extensions)]])->fails();
    }

    /** A real 1x1 PNG. */
    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }

    public function test_a_genuine_image_is_accepted(): void
    {
        $file = UploadedFile::fake()->createWithContent('photo.png', $this->png());

        $this->assertTrue($this->validate($file, ['png', 'jpg']));
    }

    public function test_php_renamed_to_an_image_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('shell.jpg', '<?php echo "test"; ?>');

        $this->assertFalse($this->validate($file, ['jpg', 'png']));
    }

    public function test_php_appended_after_valid_image_bytes_is_rejected(): void
    {
        // A "polyglot": a valid PNG followed by a script tag far past the
        // first few KB, which a head-only check would have missed.
        $payload = $this->png().str_repeat('A', 9000).'<?php echo "test"; ?>';
        $file = UploadedFile::fake()->createWithContent('polyglot.png', $payload);

        $this->assertFalse($this->validate($file, ['png']));
    }

    public function test_html_or_script_content_is_rejected_even_under_an_allowed_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('note.pdf', "%PDF-1.4\n<script>alert('test')</script>");

        $this->assertFalse($this->validate($file, ['pdf']));
    }

    public function test_disallowed_extensions_are_rejected(): void
    {
        foreach (['x.php', 'x.phtml', 'x.html', 'x.js', 'x.exe', 'x.svg', 'x.php.jpg.php'] as $name) {
            $file = UploadedFile::fake()->createWithContent($name, $this->png());

            $this->assertFalse($this->validate($file, ['jpg', 'png']), "$name should be rejected");
        }
    }

    public function test_content_that_does_not_match_the_extension_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('photo.png', 'just some text, not an image');

        $this->assertFalse($this->validate($file, ['png']));
    }

    public function test_oversized_pixel_dimensions_are_rejected(): void
    {
        // Header of a PNG that *claims* 20000x20000 pixels (decompression bomb).
        $ihdr = pack('N', 20000).pack('N', 20000).chr(8).chr(2).chr(0).chr(0).chr(0);
        $chunk = pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
        $file = UploadedFile::fake()->createWithContent('bomb.png', "\x89PNG\r\n\x1a\n".$chunk);

        $this->assertFalse($this->validate($file, ['png']));
    }

    // -------------------------------------------------------------------- SVG

    public function test_svg_sanitizer_strips_script_handlers_and_js_uris(): void
    {
        $dirty = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" onload="alert(1)">'
            .'<script>alert(1)</script>'
            .'<a xlink:href="javascript:alert(1)"><rect width="1" height="1" onclick="alert(1)"/></a>'
            .'<foreignObject><div>x</div></foreignObject>'
            .'<animate attributeName="href" to="javascript:alert(1)"/>'
            .'</svg>';

        $clean = SvgSanitizer::sanitize($dirty);

        $this->assertNotSame('', $clean);
        $this->assertStringNotContainsStringIgnoringCase('<script', $clean);
        $this->assertStringNotContainsStringIgnoringCase('onload', $clean);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $clean);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $clean);
        $this->assertStringNotContainsStringIgnoringCase('foreignObject', $clean);
        $this->assertStringNotContainsStringIgnoringCase('<animate', $clean);
    }

    public function test_svg_sanitizer_refuses_xxe_and_entity_declarations(): void
    {
        $xxe = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]>'
            .'<svg xmlns="http://www.w3.org/2000/svg"><text>&x;</text></svg>';

        $this->assertSame('', SvgSanitizer::sanitize($xxe));
    }

    // ------------------------------------------------------------------- CSRF

    public function test_csrf_protection_is_active_for_the_web_group_with_no_exempt_paths(): void
    {
        // Laravel skips CSRF verification while unit-testing, so assert the
        // wiring instead: the middleware is in the web group and nothing is
        // exempted. (The 419 itself is checked with a real HTTP request.)
        $groups = $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();

        $this->assertContains(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $groups['web']);
        $this->assertSame([], $this->app->make(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->getExcludedPaths());
    }
}
