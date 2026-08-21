<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\SocietyResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Api\ApiPasswordResetService;
use App\Services\Api\ApiTokenService;
use App\Services\Api\TenantAccountLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function login(LoginRequest $request, TenantAccountLocator $locator, ApiTokenService $tokenService): JsonResponse
    {
        $found = $request->authenticate($locator);
        $society = $found['society'];
        $user = $found['user'];

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $token = $tokenService->issue(
            $society,
            $user,
            $request->string('device_id')->value() ?: null,
            $request->string('device_platform')->value() ?: null,
        );

        $user->load(['roles', 'residencies.flat.block']);

        return $this->ok([
            'token' => $token,
            'society' => new SocietyResource($society),
            'user' => new UserResource($user),
        ], 'Logged in successfully.');
    }

    public function logout(ApiTokenService $tokenService): JsonResponse
    {
        $tokenService->revoke(request()->bearerToken());

        return $this->ok(null, 'Logged out successfully.');
    }

    public function me(): JsonResponse
    {
        $user = $this->user()->load(['roles', 'residencies.flat.block']);

        return $this->ok([
            'society' => new SocietyResource(request()->attributes->get('api_society')),
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Always responds the same way whether or not the email matches an
     * account, so the endpoint can't be used to discover which addresses
     * are registered.
     */
    public function forgotPassword(ForgotPasswordRequest $request, ApiPasswordResetService $resetService): JsonResponse
    {
        $email = $request->string('email')->value();
        $plainTextToken = $resetService->requestReset($email);

        if ($plainTextToken) {
            // MAIL_MAILER=log in local/dev — the reset link lands in
            // storage/logs until real SMTP credentials are configured.
            Mail::raw(
                "Use this code in the FlatCare app to reset your password: {$plainTextToken}\n\nThis code expires in 30 minutes. If you didn't request this, you can ignore this email.",
                function ($message) use ($email) {
                    $message->to($email)->subject('Reset your FlatCare password');
                }
            );

            Log::info('API password reset requested', ['email' => $email]);
        }

        return $this->ok(null, 'If that email is registered, a reset code has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request, ApiPasswordResetService $resetService): JsonResponse
    {
        $reset = $resetService->reset(
            $request->string('email')->value(),
            $request->string('token')->value(),
            $request->string('password')->value(),
        );

        if (!$reset) {
            throw ValidationException::withMessages([
                'token' => 'This reset code is invalid or has expired.',
            ]);
        }

        return $this->ok(null, 'Password reset successfully. Please log in again.');
    }
}
