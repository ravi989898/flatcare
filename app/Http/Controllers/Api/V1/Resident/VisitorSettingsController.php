<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Tenant\Flat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Visitor > Settings toggles. They belong to the household (the flat),
 * and are enforced at the gate by Guard\VisitorController::store().
 */
class VisitorSettingsController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->ok($this->payload($this->flat($request)));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'flat_id' => ['nullable', 'integer'],
            'guest_approval_required' => ['sometimes', 'boolean'],
            'house_closed' => ['sometimes', 'boolean'],
        ]);

        $flat = $this->flat($request);
        $flat->update(collect($data)->only(['guest_approval_required', 'house_closed'])->all());

        return $this->ok($this->payload($flat), 'Settings saved.');
    }

    /**
     * The requested flat when it is one of the resident's own, else their
     * first — never a client-supplied id that isn't theirs.
     */
    private function flat(Request $request): Flat
    {
        $ids = $this->myFlatIds();
        $requested = (int) $request->input('flat_id', $request->query('flat_id'));

        return Flat::findOrFail(in_array($requested, $ids, true) ? $requested : ($ids[0] ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Flat $flat): array
    {
        return [
            'flat_id' => $flat->id,
            'guest_approval_required' => (bool) $flat->guest_approval_required,
            'house_closed' => (bool) $flat->house_closed,
        ];
    }
}
