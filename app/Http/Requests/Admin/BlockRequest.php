<?php

namespace App\Http\Requests\Admin;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by SocietyStructureController::blocksStore()/blocksUpdate(). The
 * `unique:society.blocks,...` rule only checks the right tenant database if
 * the `society` connection has already been switched — done here in
 * prepareForValidation() rather than the controller body, since FormRequest
 * rules() runs first (see StoreSocietyAdminRequest's docblock for the same
 * reasoning).
 */
class BlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        app(TenantService::class)->switchConnection((int) $this->route('id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $blockId = $this->route('blockId');

        return [
            'block_number' => [
                'required', 'string', 'max:255',
                'unique:society.blocks,block_number'.($blockId ? ",{$blockId}" : ''),
            ],
        ];
    }
}
