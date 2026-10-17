<?php

namespace App\Services;

use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Writes a flat edit form's Resident Name/Email through to that flat's own
 * resident User - the same account the FlatCare app's Profile screen edits
 * (Api\V1\Resident\ProfileController::update()). Shared by
 * Society\BlockController and Admin\SocietyStructureController so both
 * panels behave identically.
 *
 * Guarantees an edit only ever touches the flat being edited:
 *  - email is not unique among residents (see the
 *    drop_unique_email_from_users_table tenant migration), so a duplicate
 *    email is simply saved as-is;
 *  - if this flat's resident account is still shared with another flat
 *    (legacy data, see UnlinkFlatResident), the account is split first so
 *    each flat gets its own User and the other flat keeps its current
 *    name/email untouched;
 *  - a flat with no mobile number still gets a (phone-less) resident
 *    account when a name/email is entered, instead of the input being
 *    silently dropped.
 */
class FlatResidentSync
{
    private const PLACEHOLDER_EMAIL_DOMAIN = '@resident.flatcare.local';

    /**
     * @return string|null a warning to flash, or null on success
     */
    public function sync(Flat $flat, string $name, string $email): ?string
    {
        return DB::connection('society')->transaction(function () use ($flat, $name, $email) {
            $residency = $this->primaryResidency($flat);

            if ($residency && $this->isShared($residency)) {
                $residency = $this->splitSharedAccount($flat, $residency);
            }

            if ($residency) {
                $user = $residency->user;

                $user->update([
                    // Always write both fields through, falling back to the
                    // creation-time placeholders - clearing a box must clear it.
                    'name' => $name ?: "Resident of {$flat->flat_number}",
                    'email' => $email ?: $this->placeholderEmail(),
                ]);

                // A resident added before the flat had a mobile number picks
                // the number up now, so their first OTP login lands on this
                // same account instead of provisioning a duplicate.
                if ($flat->mobile_number && !$user->phone && !$this->phoneTaken($flat->mobile_number, $user->id)) {
                    $user->update(['phone' => $flat->mobile_number]);
                }

                return null;
            }

            if (!$flat->mobile_number && $name === '' && $email === '') {
                return null;
            }

            $user = $flat->mobile_number ? User::where('phone', $flat->mobile_number)->first() : null;

            if ($user && FlatResident::where('user_id', $user->id)->where('flat_id', '!=', $flat->id)->active()->exists()) {
                // That phone number's account is already the active resident
                // of a different flat - linking it here too would make both
                // flats share one name/email again.
                return "Mobile number {$flat->mobile_number} is already linked to another flat's resident - resident not updated.";
            }

            if ($user) {
                $user->update([
                    'name' => $name ?: $user->name,
                    'email' => $email ?: $user->email,
                ]);
            } else {
                $user = $this->createResident(
                    $name ?: "Resident of {$flat->flat_number}",
                    $email ?: $this->placeholderEmail(),
                    $flat->mobile_number,
                );
            }

            FlatResident::create([
                'flat_id' => $flat->id,
                'user_id' => $user->id,
                'resident_type' => 'owner',
                'moved_in_date' => now(),
                'status' => 'active',
                'is_primary' => !FlatResident::where('flat_id', $flat->id)->active()->exists(),
            ]);

            return null;
        });
    }

    private function primaryResidency(Flat $flat): ?FlatResident
    {
        return FlatResident::where('flat_id', $flat->id)->active()->orderByDesc('is_primary')->with('user')->first();
    }

    private function isShared(FlatResident $residency): bool
    {
        return FlatResident::where('user_id', $residency->user_id)
            ->where('flat_id', '!=', $residency->flat_id)
            ->active()
            ->exists();
    }

    /**
     * Gives the edited flat its own User. The shared account stays with
     * whichever flat owns its phone number (flats.mobile_number is unique
     * and phone is the OTP-login identifier): if that's this flat, every
     * other flat on the account is moved to its own copy instead.
     */
    private function splitSharedAccount(Flat $flat, FlatResident $residency): FlatResident
    {
        $shared = $residency->user;

        if ($shared->phone && $shared->phone === $flat->mobile_number) {
            FlatResident::where('user_id', $shared->id)
                ->where('flat_id', '!=', $flat->id)
                ->active()
                ->get()
                ->each(fn (FlatResident $other) => $this->moveToCopy($other, $shared, null));

            return $residency;
        }

        $phone = $flat->mobile_number && !$this->phoneTaken($flat->mobile_number) ? $flat->mobile_number : null;

        return $this->moveToCopy($residency, $shared, $phone);
    }

    /**
     * Ends $residency and re-creates it against a fresh copy of $shared.
     */
    private function moveToCopy(FlatResident $residency, User $shared, ?string $phone): FlatResident
    {
        $copy = $this->createResident($shared->name, $shared->email, $phone);

        $residency->update(['status' => 'inactive', 'moved_out_date' => now()]);

        return FlatResident::create([
            'flat_id' => $residency->flat_id,
            'user_id' => $copy->id,
            'resident_type' => $residency->resident_type,
            'moved_in_date' => $residency->moved_in_date ?? now(),
            'status' => 'active',
            'is_primary' => $residency->is_primary,
        ])->load('user');
    }

    private function createResident(string $name, string $email, ?string $phone): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make(Str::password(20)),
            'country' => 'India',
            'status' => 'active',
        ]);

        if ($residentRole = Role::where('name', 'resident')->first()) {
            $user->assignRole($residentRole);
        }

        return $user;
    }

    private function phoneTaken(string $phone, ?int $exceptUserId = null): bool
    {
        return User::where('phone', $phone)
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->exists();
    }

    private function placeholderEmail(): string
    {
        return Str::uuid().self::PLACEHOLDER_EMAIL_DOMAIN;
    }
}
