<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Tenant\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::with('postedBy');

        $when = $request->string('when')->trim()->value();
        match ($when) {
            'past' => $query->past(),
            default => $query->upcoming(),
        };

        $events = $query->orderBy('start_at')->paginate(10);

        return $this->paginated(EventResource::collection($events), $events);
    }

    public function show(int $id): JsonResponse
    {
        $event = Event::with('postedBy')->findOrFail($id);

        return $this->ok(new EventResource($event));
    }
}
