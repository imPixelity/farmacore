<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.opname.manage') ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.physical_qty' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/', 'numeric', 'min:0', 'max:999999999999.999'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
