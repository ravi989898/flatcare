<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

/**
 * Platform-wide values the mobile app shows but shouldn't hard-code —
 * currently the FlatCare support email and phone numbers, which a super
 * admin edits under Settings › Branding. Public (no token) so the login
 * screen and Help Line work before sign-in too.
 */
class AppConfigController extends ApiController
{
    public function show(): JsonResponse
    {
        $contact = PlatformSetting::contact();

        $phones = array_map(function (string $phone, int $i) use ($contact) {
            $digits = preg_replace('/\D+/', '', $phone);
            $whatsapp = $contact['whatsapp'][$i] ?? (strlen($digits) === 10 ? '91'.$digits : $digits);

            return [
                'display' => $phone,
                // E.164-style number for tel: links, e.g. +919664653896.
                'dial' => '+'.$whatsapp,
                // Digits for wa.me links, e.g. 919664653896.
                'whatsapp' => $whatsapp,
            ];
        }, $contact['phones'], array_keys($contact['phones']));

        return $this->ok([
            'support' => [
                'email' => $contact['email'],
                'phones' => $phones,
            ],
        ]);
    }
}
