<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Firebase Cloud Messaging (HTTP v1) for mobile push notifications.
    | FCM_CREDENTIALS is the path to a Firebase service-account JSON (kept
    | outside version control, e.g. storage/app/firebase/service-account.json);
    | FCM_PROJECT_ID is optional - it defaults to the project_id in that file.
    | With no credentials, pushes are skipped (in-app notifications still work).
    */
    'fcm' => [
        'credentials' => env('FCM_CREDENTIALS'),
        'project_id' => env('FCM_PROJECT_ID'),
        'timeout' => env('FCM_TIMEOUT', 5),
    ],

    /*
    | 2Factor.in SMS OTP for app login (App\Services\Api\OtpService), used
    | only when OTP_CHANNEL=sms.
    | TWOFACTOR_API_KEY is the API key from the 2Factor dashboard;
    | TWOFACTOR_OTP_TEMPLATE is the name of the approved (DLT) OTP template -
    | required: without it 2Factor sends the OTP as a voice call, so no OTP
    | is sent at all. With no API key, OTP stays on the
    | fixed OTP_DEFAULT_CODE (local/dev only).
    */
    'twofactor' => [
        'api_key' => env('TWOFACTOR_API_KEY'),
        'otp_template' => env('TWOFACTOR_OTP_TEMPLATE', 'FlatCare'),
        'timeout' => env('TWOFACTOR_TIMEOUT', 10),
    ],


    /*
    | WhatsApp OTP for app login (App\Services\Api\OtpService, the default
    | OTP_CHANNEL) through Meta's WhatsApp Cloud API. WHATSAPP_TOKEN is a
    | permanent System User access token, WHATSAPP_PHONE_NUMBER_ID the sending
    | number's id (WhatsApp Manager > API Setup), WHATSAPP_OTP_TEMPLATE the
    | name of an approved "Authentication" template with a Copy code button.
    | With no token, OTP stays on the fixed OTP_DEFAULT_CODE (local/dev only).
    */
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'otp_template' => env('WHATSAPP_OTP_TEMPLATE', 'flatcare_otp'),
        'otp_language' => env('WHATSAPP_OTP_LANGUAGE', 'en'),
        'otp_length' => env('WHATSAPP_OTP_LENGTH', 6),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'timeout' => env('WHATSAPP_TIMEOUT', 10),
    ],

];
