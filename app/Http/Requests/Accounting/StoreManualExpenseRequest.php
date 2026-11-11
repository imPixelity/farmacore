<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accounting.cash.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'cash_bank_account_id' => ['required', 'integer', Rule::exists('cash_bank_accounts', 'id')->where('is_active', true)],
            'category_id' => ['required', 'integer', Rule::exists('transaction_categories', 'id')->where('direction', 'out')->where('is_active', true)],
            'trx_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
