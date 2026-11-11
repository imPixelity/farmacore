<?php

namespace Database\Factories;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Purchasing\Models\PayablePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayablePayment> */
class PayablePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payable_id' => Payable::factory(),
            'cash_bank_account_id' => CashBankAccount::factory(),
            'payment_date' => today(),
            'amount' => '100.00',
            'reference_no' => fake()->optional()->bothify('TRF-######'),
            'notes' => null,
        ];
    }
}
