<?php

namespace Database\Factories;

use App\Modules\Accounting\Models\TaxObligation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxObligation> */
class TaxObligationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tax_type' => 'PPN',
            'period' => '2026-11',
            'amount_due' => '110.00',
            'due_date' => '2026-12-10',
            'status' => 'belum',
            'paid_at' => null,
            'notes' => null,
        ];
    }
}
