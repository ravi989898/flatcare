<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\OtpRequestRequest;
use App\Http\Requests\Api\V1\Auth\OtpVerifyRequest;
use App\Http\Resources\Api\V1\SocietyResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Role;
use App\Models\Tenant\User as TenantUser;
use App\Services\Api\ApiTokenService;
use App\Services\Api\OtpService;
use App\Services\Api\TenantAccountLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Resident app login by mobile number + OTP, an alternative to
 * AuthController's email/password login for residents who never got a
 * formal account from the society-admin directory (see
 * Society\DirectoryController::store) — a super admin only has to set the
 * flat's mobile_number (Admin\SocietyStructureController::flatsUpdate) and
 * whoever verifies that number here is signed in as that flat's resident,
 * with the account created on first login if it doesn't exist yet.
 */
class OtpAuthController extends ApiController
{
    /**
     * Unlike AuthController::forgotPassword, this deliberately tells the
     * caller when the number isn't registered to any flat - product
     * decision: a real resident whose number isn't set up yet needs to know
     * to contact their society admin, and the numbers this could leak are
     * ones an admin already chose to put on a flat, not secret to begin
     * with.
     */
    public function request(OtpRequestRequest $request, OtpService $otp, TenantAccountLocator $locator): JsonResponse
    {
        $mobileNumber = $request->string('mobile_number')->value();

        if (!$locator->findFlatByMobileNumber($mobileNumber)) {
            throw ValidationException::withMessages([
                'mobile_number' => "This mobile number is not registered. Please contact your society admin to register your number, then you can log in.",
            ]);
        }

        $otp->send($mobileNumber);

        return $this->ok(null, 'An OTP has been sent.');
    }

    public function verify(OtpVerifyRequest $request, OtpService $otp, TenantAccountLocator $locator, ApiTokenService $tokenService): JsonResponse
    {
        $found = $request->authenticate($otp, $locator);
        $society = $found['society'];
        $flat = $found['flat'];

        $user = $this->findOrCreateResident($flat, $request->string('mobile_number')->value());

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

    /**
     * An existing resident (matched by phone) reuses their account; a
     * number with no account yet gets a minimal one auto-provisioned -
     * mirrors Society\DirectoryController::store, minus the fields only a
     * human filling out that form would have (a real name, a real email).
     * Either way the flat/user link is created if it's missing, so a
     * number moved from one flat's mobile_number to another still works.
     */
    private function findOrCreateResident(Flat $flat, string $mobileNumber): TenantUser
    {
        $user = TenantUser::where('phone', $mobileNumber)->first();

        if (!$user) {
            $user = TenantUser::create([
                'name' => "Resident of {$flat->flat_number}",
                // users.email is required + unique with no resident-facing
                // use yet - a placeholder the resident can replace from
                // their profile once the app has that screen.
                'email' => Str::uuid().'@resident.flatcare.local',
                'phone' => $mobileNumber,
                'password' => Hash::make(Str::password(20)),
                'country' => 'India',
                'status' => 'active',
            ]);

            if ($residentRole = Role::where('name', 'resident')->first()) {
                $user->assignRole($residentRole);
            }
        }

        $alreadyLinked = FlatResident::where('flat_id', $flat->id)->where('user_id', $user->id)->exists();

        if (!$alreadyLinked) {
            FlatResident::create([
                'flat_id' => $flat->id,
                'user_id' => $user->id,
                'resident_type' => 'owner',
                'moved_in_date' => now(),
                'status' => 'active',
                'is_primary' => !FlatResident::where('flat_id', $flat->id)->active()->exists(),
            ]);
        }

        return $user;
    }
}
