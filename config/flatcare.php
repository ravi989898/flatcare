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
    | App\Services\Api\OtpService texts the code through 2Factor.in (see
    | services.twofactor). Without TWOFACTOR_API_KEY nothing is sent and,
    | outside production only, every phone number's OTP is otp_default_code.
    |
    */

    'otp_default_code' => env('OTP_DEFAULT_CODE', '0000'),

];
