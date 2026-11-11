<?php

namespace App\Http\Requests\Md;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarginSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.price.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $scopeType = $this->input('scope_type');
        $scopeIdRules = match ($scopeType) {
            'item' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'category' => ['required', 'integer', Rule::exists('item_categories', 'id')->where('is_active', true)],
            'global' => ['prohibited'],
            default => ['nullable'],
        };

        return [
            'scope_type' => ['required', Rule::in(['global', 'category', 'item'])],
            'scope_id' => $scopeIdRules,
            'sales_type' => ['required', Rule::in(['resep', 'obat_bebas', 'produk_lain'])],
            'margin_percent' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999.99'],
        ];
    }
}
