<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Tenant\SecurityGuard
 */
class SecurityGuardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'shift' => $this->shift,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
        ];
    }
}
