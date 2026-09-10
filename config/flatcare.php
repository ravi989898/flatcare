<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resident mobile app (Android APK) download link
    |--------------------------------------------------------------------------
    |
    | By default the landing page serves the APK straight from the server at
    | public/downloads/flatcare-app.apk — upload the file there (it is
    | git-ignored, so it never goes through the repo). A release build is
    | ~19 MB, small enough to keep on the server without trouble.
    |
    | Set MOBILE_APK_URL in .env to point somewhere else instead — e.g. a
    | GitHub Release asset on a PUBLIC repo, or a CDN. When it is empty the
    | blade falls back to asset('downloads/flatcare-app.apk').
    |
    */

    'apk_url' => env('MOBILE_APK_URL') ?: null,

];
