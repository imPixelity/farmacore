<?php

namespace Database\Factories;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'trx_no' => 'FT-'.Str::ulid(),
            'trx_date' => today(),
            'cash_bank_account_id' => CashBankAccount::factory(),
            'category_id' => TransactionCategory::factory(),
            'direction' => 'out',
            'amount' => '100.00',
            'description' => fake()->sentence(5),
            'ref_type' => null,
            'ref_id' => null,
            'status' => 'posted',
            'reverses_id' => null,
        ];
    }
}
