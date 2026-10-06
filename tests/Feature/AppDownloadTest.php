<?php

namespace Tests\Feature;

use App\Http\Controllers\AppDownloadController;
use Tests\TestCase;

/**
 * The Android app has one download link, /app/download, serving the
 * committed APK under a versioned name and never from a cache.
 */
class AppDownloadTest extends TestCase
{
    public function test_download_serves_the_committed_apk_with_a_versioned_name(): void
    {
        $this->assertFileExists(public_path(AppDownloadController::APK_PATH));
        $version = AppDownloadController::version();
        $this->assertNotNull($version);

        $response = $this->get('/app/download');

        $response->assertOk();
        $this->assertSame('application/vnd.android.package-archive', $response->headers->get('Content-Type'));
        $this->assertStringContainsString("FlatCare-{$version}.apk", (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_landing_page_links_to_the_download_route(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="/app/download"', $html);
        $this->assertStringNotContainsString('href="/downloads/flatcare-app.apk"', $html);
    }
}
