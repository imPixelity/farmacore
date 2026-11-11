<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accounting.cash.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['kas', 'bank'])],
            'account_no' => ['nullable', 'required_if:type,bank', 'prohibited_if:type,kas', 'string', 'max:50'],
            'opening_balance' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
        ];
    }
}
