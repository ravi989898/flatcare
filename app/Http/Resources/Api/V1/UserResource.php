<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated resident's own profile — full detail, since a user
 * always sees their own unmasked data (contrast with DirectoryResource).
 *
 * @mixin \App\Models\Tenant\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_photo_url' => $this->profile_photo_path ? asset('storage/'.$this->profile_photo_path) : null,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'status' => $this->status,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'flats' => $this->whenLoaded(
                'residencies',
                fn () => $this->residencies
                    ->where('status', 'active')
                    ->map(fn ($residency) => [
                        'residency_id' => $residency->id,
                        'resident_type' => $residency->resident_type,
                        'is_primary' => $residency->is_primary,
                        'flat' => new FlatResource($residency->flat),
                    ])
                    ->values()
            ),
        ];
    }
}
