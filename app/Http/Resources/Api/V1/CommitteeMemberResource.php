<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Unlike DirectoryResource, phone numbers here are never masked — a
 * committee member's contact info is meant to be publicly reachable by
 * every resident (e.g. the mockup's tap-to-call Committee Members list).
 *
 * @mixin \App\Models\Tenant\User
 */
class CommitteeMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'position' => $this->committee_position,
            'photo_url' => $this->profile_photo_path ? asset('storage/'.$this->profile_photo_path) : null,
        ];
    }
}
