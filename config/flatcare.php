<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resident mobile app (Android APK)
    |--------------------------------------------------------------------------
    |
    | The APK is ~150 MB — too large to keep in the git repo (GitHub caps
    | files at 100 MB) or to ship inside public/. It is published as a
    | GitHub Release asset instead, and the landing page links straight to
    | it. "releases/latest/download/<asset>" always resolves to the newest
    | release, so publishing a new build never needs a code change — just
    | upload the new APK to a fresh release using the same asset name.
    |
    | Override with MOBILE_APK_URL in .env if the app is hosted elsewhere.
    |
    */

    'apk_url' => env(
        'MOBILE_APK_URL',
        'https://github.com/ravi989898/flatcare/releases/latest/download/flatcare-app.apk'
    ),

];
