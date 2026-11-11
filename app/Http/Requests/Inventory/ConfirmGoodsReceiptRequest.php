<?php

namespace App\Http\Requests\Inventory;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Inventory\Models\GoodsReceipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConfirmGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.receipt.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirm_matching' => ['nullable', 'boolean'],
            'cash_bank_account_id' => [
                'nullable',
                'integer',
                Rule::exists('cash_bank_accounts', 'id'),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $receipt = $this->route('goodsReceipt');

            if (! $receipt instanceof GoodsReceipt || $receipt->status === 'confirmed' || $receipt->payment_method !== 'tunai') {
                return;
            }

            if (blank($this->input('cash_bank_account_id'))) {
                $validator->errors()->add('cash_bank_account_id', 'Akun kas/bank wajib dipilih untuk penerimaan tunai.');

                return;
            }

            if ($validator->errors()->has('cash_bank_account_id')) {
                return;
            }

            $account = CashBankAccount::query()->find($this->integer('cash_bank_account_id'));

            if (! $account?->is_active) {
                $validator->errors()->add('cash_bank_account_id', 'Akun kas/bank tidak ditemukan atau sudah nonaktif.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cash_bank_account_id.exists' => 'Akun kas/bank tidak ditemukan.',
        ];
    }
}
