<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row per active residency (FlatResident), matching Society\DirectoryController.
 * Phone numbers are shown in full so society members can call or
 * WhatsApp each other from the directory. Email stays masked for
 * everyone except the viewer's own row.
 *
 * @mixin \App\Models\Tenant\FlatResident
 */
class DirectoryResource extends JsonResource
{
    public function __construct(private $residency, private int $viewerUserId)
    {
        parent::__construct($residency);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSelf = $this->residency->user_id === $this->viewerUserId;

        return [
            'residency_id' => $this->residency->id,
            'resident_type' => $this->residency->resident_type,
            'is_primary' => $this->residency->is_primary,
            'user_id' => $this->residency->user_id,
            'name' => $this->residency->user?->name,
            'phone' => $this->residency->user?->phone,
            'email' => $isSelf ? $this->residency->user?->email : $this->maskEmail($this->residency->user?->email),
            'flat' => new FlatResource($this->residency->flat),
        ];
    }

    private function maskEmail(?string $email): ?string
    {
        if ($email === null || !str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 2);

        return $visible.str_repeat('*', max(strlen($local) - 2, 1)).'@'.$domain;
    }
}
