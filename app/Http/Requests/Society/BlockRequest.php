<?php

namespace App\Http\Requests\Society;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by Society\BlockController::blocksStore() and ::blocksUpdate() —
 * the only difference is that update must exclude the block's own row from
 * the block_number `unique` check (via the {blockId} route parameter).
 */
class BlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
