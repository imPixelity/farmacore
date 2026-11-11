<?php

namespace App\Http\Requests\Md;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.price.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $name = $this->input('name');

        return [
            'name' => ['required', Rule::in(['PPN'])],
            'rate_percent' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'effective_from' => [
                'required', 'date',
                Rule::unique('tax_settings', 'effective_from')->where('name', $name),
            ],
        ];
    }
}
