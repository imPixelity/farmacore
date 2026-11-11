<?php

namespace App\Http\Requests\Md;

use App\Modules\Md\Models\DiscountRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DiscountRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.price.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $discountRule = $this->route('discountRule');
        $uniqueName = Rule::unique('discount_rules', 'name');

        if ($discountRule instanceof DiscountRule) {
            $uniqueName->ignore($discountRule);
        }

        return [
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'rule_type' => ['required', Rule::in(['qty', 'nominal'])],
            'sales_type' => ['nullable', Rule::in(['resep', 'obat_bebas', 'produk_lain'])],
            'item_id' => ['nullable', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'category_id' => ['nullable', 'integer', Rule::exists('item_categories', 'id')->where('is_active', true)],
            'min_qty' => ['nullable', 'required_if:rule_type,qty', 'prohibited_if:rule_type,nominal', 'numeric', 'decimal:0,3', 'gt:0'],
            'min_amount' => ['nullable', 'required_if:rule_type,nominal', 'prohibited_if:rule_type,qty', 'numeric', 'decimal:0,2', 'gt:0'],
            'discount_type' => ['required', Rule::in(['percent', 'amount'])],
            'discount_value' => [
                'required', 'numeric', 'decimal:0,2', 'gt:0',
                Rule::when($this->input('discount_type') === 'percent', ['max:100']),
            ],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['item_id', 'category_id', 'effective_from', 'effective_until'])) {
                return;
            }

            if ($this->filled('item_id') && $this->filled('category_id')) {
                $validator->errors()->add('item_id', 'Aturan diskon hanya dapat ditujukan ke barang atau kategori, bukan keduanya.');
            }
        }];
    }
}
