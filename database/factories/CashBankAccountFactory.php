<?php

namespace Database\Factories;

use App\Modules\Accounting\Models\CashBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBankAccount>
 */
class CashBankAccountFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => 'kas',
            'account_no' => null,
            'opening_balance' => '0.00',
            'current_balance' => '0.00',
            'is_active' => true,
        ];
    }
}
