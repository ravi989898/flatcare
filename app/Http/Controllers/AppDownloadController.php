<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The one place the Android app is downloaded from: /app/download.
 *
 * The landing page, the SEO structured data and old shared links to
 * /downloads/flatcare-app.apk (redirected here by public/.htaccess) all
 * lead here, and it always serves the APK committed at
 * public/downloads/flatcare-app.apk. A `git pull` on the server is the
 * whole release; see public/downloads/README.md for how that file is built.
 *
 * The file is named after the app version (FlatCare-1.0.11.apk) so people
 * can tell which build they have, and is never cached, so a phone or proxy
 * can never hand out an older build after an update.
 */
class AppDownloadController extends Controller
{
    public const APK_PATH = 'downloads/flatcare-app.apk';

    public function android(): BinaryFileResponse
    {
        $path = public_path(self::APK_PATH);

        abort_unless(is_file($path), 404, 'The app is not available for download yet.');

        $version = self::version();

        return response()->download($path, $version ? "FlatCare-{$version}.apk" : 'FlatCare.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * The app version the committed APK was built from: `version:` in
     * mobile/pubspec.yaml, without the build number (1.0.11+12 -> 1.0.11).
     */
    public static function version(): ?string
    {
        $pubspec = base_path('mobile/pubspec.yaml');

        if (!is_readable($pubspec) || !preg_match('/^version:\s*([0-9][0-9.]*)/m', (string) file_get_contents($pubspec), $match)) {
            return null;
        }

        return $match[1];
    }
}
