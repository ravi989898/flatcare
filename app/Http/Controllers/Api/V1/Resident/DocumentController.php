<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Tenant\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only: documents are uploaded by the admin (Society\DocumentController),
 * residents can only list/download.
 */
class DocumentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->category($request->string('category')->trim()->value() ?: null)
            ->latest()
            ->paginate(20);

        return $this->paginated(DocumentResource::collection($documents), $documents);
    }

    public function show(int $id): JsonResponse
    {
        return $this->ok(new DocumentResource(Document::findOrFail($id)));
    }
}
