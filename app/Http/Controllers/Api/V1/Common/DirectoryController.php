<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\DirectoryResource;
use App\Models\Tenant\FlatResident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = FlatResident::query()->active()->with(['user', 'flat.block']);

        if ($blockId = $request->string('block_id')->trim()->value()) {
            $query->whereHas('flat', fn ($q) => $q->where('block_id', $blockId));
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('flat', function ($f) use ($search) {
                    $f->where('flat_number', 'like', "%{$search}%")
                        ->orWhereHas('block', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                });
            });
        }

        $residencies = $query->orderByDesc('is_primary')->latest()->paginate(20);
        $viewer = $this->user();
        $viewerIsGuard = $viewer->hasRole('security');

        $items = $residencies->getCollection()->map(
            fn ($residency) => new DirectoryResource($residency, $viewer->id, $viewerIsGuard)
        );

        return $this->paginated($items, $residencies);
    }

    public function show(int $userId): JsonResponse
    {
        $residency = FlatResident::active()->with(['user', 'flat.block'])
            ->where('user_id', $userId)
            ->firstOrFail();

        $viewer = $this->user();

        return $this->ok(new DirectoryResource($residency, $viewer->id, $viewer->hasRole('security')));
    }
}
