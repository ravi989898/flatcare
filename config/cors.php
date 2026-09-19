<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing
|--------------------------------------------------------------------------
|
| The API is consumed by the native mobile app, which does not use CORS at
| all, and by same-origin pages of this site. Without this file Laravel's
| default is `Access-Control-Allow-Origin: *` for /api/*, letting any website
| script call the API from a visitor's browser. Instead nothing is allowed
| unless it is listed:
|
|   CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
|
| On a local dev machine (APP_ENV=local) http(s)://localhost and
| http(s)://127.0.0.1 on any port are allowed so `flutter run -d chrome`
| keeps working. Credentials (cookies) are never allowed: the API
| authenticates with a bearer token in the Authorization header.
|
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))))),

    'allowed_origins_patterns' => env('APP_ENV', 'production') === 'local'
        ? ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#']
        : [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With', 'Origin'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => false,

];
