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
    | otp_channel picks how App\Services\Api\OtpService sends the code:
    | "whatsapp" (default - Meta WhatsApp Cloud API, see services.whatsapp;
    | until its keys are set, a server with a 2Factor key keeps using SMS)
    | or "sms" (2Factor.in, see services.twofactor; switched off for now
    | because SMS costs more). The app reads the channel from the
    | /auth/otp/request response and tells the user where to look.
    |
    | When the active channel has no credentials (a dev machine), nothing is
    | sent and every phone number's OTP is otp_default_code.
    |
    */

    'otp_channel' => env('OTP_CHANNEL', 'whatsapp'),

    'otp_default_code' => env('OTP_DEFAULT_CODE', '0000'),

];
