<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Resident\StoreDailyHelperRequest;
use App\Http\Resources\Api\V1\DailyHelperResource;
use App\Models\Tenant\DailyHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class DailyHelperController extends ApiController
{
    public function index(): JsonResponse
    {
        $helpers = DailyHelper::whereIn('flat_id', $this->myFlatIds())
            ->orderBy('name')
            ->get();

        return $this->ok(DailyHelperResource::collection($helpers));
    }

    public function store(StoreDailyHelperRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!in_array((int) $validated['flat_id'], $this->myFlatIds(), true)) {
            return $this->fail('You can only add a helper to your own flat.', 403);
        }

        $helper = DailyHelper::create([
            ...Arr::except($validated, ['photo']),
            'added_by_user_id' => $this->user()->id,
            'photo_path' => $request->file('photo')?->store('daily-helpers', 'public'),
        ]);

        return $this->ok(new DailyHelperResource($helper), 'Daily helper added.', 201);
    }

    public function destroy(int $id): JsonResponse
    {
        DailyHelper::whereIn('flat_id', $this->myFlatIds())->findOrFail($id)->delete();

        return $this->ok(null, 'Daily helper removed.');
    }
}
