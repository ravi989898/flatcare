<?php

namespace App\Console\Commands;

use App\Models\Society;
use App\Models\Tenant\Flat;
use App\Models\Tenant\FlatResident;
use App\Services\TenantService;
use Illuminate\Console\Command;

/**
 * One-off cleanup for flats that ended up sharing one resident User account
 * (see Society\BlockController::syncResident()'s now-fixed phone-reuse and
 * restore bugs) — detaches the given flat from its current resident without
 * touching the User account or any other flat still linked to it, so the
 * admin can then edit this flat in the UI and attach its real resident.
 */
class UnlinkFlatResident extends Command
{
    protected $signature = 'flats:unlink-resident {society_id} {flat_number} {--clear-mobile : Also clear the flat\'s mobile_number so a fresh resident can be linked}';

    protected $description = "Detach one flat from its currently-linked resident account, without affecting any other flat sharing that same account";

    public function handle(TenantService $tenantService): int
    {
        $societyId = (int) $this->argument('society_id');
        $flatNumber = $this->argument('flat_number');

        $society = Society::find($societyId);

        if (!$society) {
            $this->error("No society found with id {$societyId}.");

            return self::FAILURE;
        }

        $tenantService->switchConnection($societyId);

        $flat = Flat::withTrashed()->where('flat_number', $flatNumber)->first();

        if (!$flat) {
            $this->error("No flat found with flat_number \"{$flatNumber}\" in society #{$societyId}.");

            return self::FAILURE;
        }

        $residencies = FlatResident::where('flat_id', $flat->id)->active()->with('user')->get();

        if ($residencies->isEmpty()) {
            $this->warn("Flat {$flatNumber} has no active resident link - nothing to do.");

            return self::SUCCESS;
        }

        foreach ($residencies as $residency) {
            $sharedElsewhere = FlatResident::where('user_id', $residency->user_id)
                ->where('flat_id', '!=', $flat->id)
                ->active()
                ->exists();

            $this->info(sprintf(
                'Unlinking user #%d (%s, %s)%s',
                $residency->user_id,
                $residency->user->name ?? 'unknown',
                $residency->user->phone ?? 'no phone',
                $sharedElsewhere ? ' — still linked to another flat, left untouched there' : ''
            ));

            $residency->update(['status' => 'moved_out', 'moved_out_date' => now()]);
        }

        if ($this->option('clear-mobile')) {
            $flat->update(['mobile_number' => null]);
            $this->info("Cleared mobile_number on flat {$flatNumber}.");
        }

        $this->info("Flat {$flatNumber} is now resident-less. Edit it in the admin UI to attach the correct resident.");

        return self::SUCCESS;
    }
}
