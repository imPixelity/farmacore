<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accounting.receivable.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'],
            'cash_bank_account_id' => [
                'required',
                'integer',
                Rule::exists('cash_bank_accounts', 'id')->where('is_active', true),
            ],
            'payment_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
