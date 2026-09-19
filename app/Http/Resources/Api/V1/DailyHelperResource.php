<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant\DailyHelper
 */
class DailyHelperResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flat_id' => $this->flat_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'helper_type' => $this->helper_type,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
        ];
    }
}
