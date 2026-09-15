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
use App\Models\Tenant\SecurityGuard;
use App\Models\Tenant\User as TenantUser;
use App\Services\Api\ApiTokenService;
use App\Services\Api\OtpService;
use App\Services\Api\TenantAccountLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Resident/gate-security app login by mobile number + OTP, an alternative
 * to AuthController's email/password login for people who never got a
 * formal account from the society-admin directory. For a resident, a super
 * admin only has to set the flat's mobile_number
 * (Admin\SocietyStructureController::flatsUpdate); for a security guard, a
 * society admin only has to add them to the guard roster
 * (Society\SecurityGuardController::store) — whoever verifies one of those
 * numbers here is signed in as that flat's resident or as that guard, with
 * the account created on first login if it doesn't exist yet.
 */
class OtpAuthController extends ApiController
{
    /**
     * Unlike AuthController::forgotPassword, this deliberately tells the
     * caller when the number isn't registered to any flat or guard roster -
     * product decision: a real resident/guard whose number isn't set up yet
     * needs to know to contact their society admin, and the numbers this
     * could leak are ones an admin already chose to put on a flat or the
     * guard roster, not secret to begin with.
     */
    public function request(OtpRequestRequest $request, OtpService $otp, TenantAccountLocator $locator): JsonResponse
    {
        $mobileNumber = $request->string('mobile_number')->value();

        $registered = $locator->findFlatByMobileNumber($mobileNumber)
            || $locator->findSecurityGuardByMobileNumber($mobileNumber);

        if (!$registered) {
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
        $mobileNumber = $request->string('mobile_number')->value();

        $user = $found['guard']
            ? $this->findOrCreateGuardUser($found['guard'], $mobileNumber)
            : $this->findOrCreateResident($found['flat'], $mobileNumber);

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

    /**
     * An existing guard-login account (matched by phone) reuses their
     * account; a guard logging in for the first time gets one
     * auto-provisioned with the 'security' role and their real name copied
     * from the roster entry (App\Models\Tenant\SecurityGuard::$name) - a
     * guard, unlike a fresh resident, always has a real name on file
     * already since a society admin filled it in when adding them
     * (Society\SecurityGuardController::store). No flat/residency to link -
     * a guard isn't a resident of any flat.
     */
    private function findOrCreateGuardUser(SecurityGuard $guard, string $mobileNumber): TenantUser
    {
        $user = TenantUser::where('phone', $mobileNumber)->first();

        if (!$user) {
            $user = TenantUser::create([
                'name' => $guard->name,
                // users.email is required + unique with no guard-facing use
                // yet, same placeholder scheme as findOrCreateResident().
                'email' => Str::uuid().'@security.flatcare.local',
                'phone' => $mobileNumber,
                'password' => Hash::make(Str::password(20)),
                'country' => 'India',
                'status' => 'active',
            ]);
        }

        if (!$user->hasRole('security') && ($securityRole = Role::where('name', 'security')->first())) {
            $user->assignRole($securityRole);
        }

        return $user;
    }
}
