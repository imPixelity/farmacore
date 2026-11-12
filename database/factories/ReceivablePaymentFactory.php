<?php

namespace Database\Factories;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Accounting\Models\ReceivablePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReceivablePayment> */
class ReceivablePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receivable_id' => Receivable::factory(),
            'payment_date' => today(),
            'amount' => '100.00',
            'cash_bank_account_id' => CashBankAccount::factory(),
        ];
    }
}
