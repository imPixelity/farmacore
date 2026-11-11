<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockWriteOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.opname.manage') ?? false;
    }

    /** @return array<string, array<int, string|Rule>> */
    public function rules(): array
    {
        return [
            'item_batch_id' => ['required', 'integer', Rule::exists('item_batches', 'id')],
            'write_off_date' => ['required', 'date_format:Y-m-d'],
            'qty_base' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:999999999999.999'],
            'reason' => ['required', Rule::in(['rusak', 'kedaluwarsa'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
