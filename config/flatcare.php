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
    | blade falls back to the root-relative "/downloads/flatcare-app.apk",
    | which inherits the page's own scheme — so it stays https on an https
    | page (a hardcoded http:// link there is a mixed-content download that
    | browsers block) without depending on APP_URL / APP_ENV being right.
    |
    */

    'apk_url' => env('MOBILE_APK_URL') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Resident app OTP login
    |--------------------------------------------------------------------------
    |
    | No SMS gateway is wired up yet (see App\Services\Api\OtpService) — every
    | phone number's OTP is this fixed code until a paid provider (MSG91,
    | Twilio Verify, etc.) is added. Override OTP_DEFAULT_CODE per
    | environment if you ever need it to be something other than "0000"
    | before that happens; nothing about the request/verify endpoints needs
    | to change when a real provider is added — only OtpService::send() and
    | ::verify() do.
    |
    */

    'otp_default_code' => env('OTP_DEFAULT_CODE', '0000'),

];
