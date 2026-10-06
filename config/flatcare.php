<?php

return [

    // The Android app has no setting here on purpose: it is always
    // downloaded from /app/download, which serves the APK committed at
    // public/downloads/flatcare-app.apk (see AppDownloadController and
    // public/downloads/README.md).

    // iPhone app's App Store (or TestFlight public) link. Until it is set the
    // landing page shows the iPhone button as "Coming soon".
    'ios_url' => env('MOBILE_IOS_URL') ?: null,

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
