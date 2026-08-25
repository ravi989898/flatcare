<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\CommitteeMemberResource;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;

class CommitteeMemberController extends ApiController
{
    public function index(): JsonResponse
    {
        $members = User::query()->active()->committeeMembers()->get();

        return $this->ok(CommitteeMemberResource::collection($members));
    }
}
