<?php

namespace Database\Factories;

use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Models\SalePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalePayment> */
class SalePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'method' => 'tunai',
            'channel' => null,
            'amount' => '1000.00',
            'cash_received' => '1000.00',
            'change_amount' => '0.00',
            'cash_bank_account_id' => null,
            'paid_at' => now(),
        ];
    }
}
