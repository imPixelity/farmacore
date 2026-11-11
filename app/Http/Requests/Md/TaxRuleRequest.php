<?php

namespace App\Http\Requests\Md;

use App\Modules\Md\Models\TaxRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.price.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $taxRule = $this->route('taxRule');
        $uniqueItemType = Rule::unique('tax_rules', 'item_type');

        if ($taxRule instanceof TaxRule) {
            $uniqueItemType->ignore($taxRule);
        }

        return [
            'item_type' => ['required', Rule::in(['obat', 'alkes', 'non_obat']), $uniqueItemType],
            'is_taxable' => ['required', 'boolean'],
        ];
    }
}
