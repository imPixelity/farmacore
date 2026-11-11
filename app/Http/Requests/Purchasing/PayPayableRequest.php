<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class PayPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchasing.payable.pay') ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cash_bank_account_id.required' => 'Akun kas/bank wajib dipilih.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'amount.required' => 'Jumlah pembayaran wajib diisi.',
            'amount.regex' => 'Jumlah pembayaran tidak valid.',
        ];
    }
}
