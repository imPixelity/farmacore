<?php

namespace Database\Factories;

use App\Modules\Md\Models\TaxRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxRule> */
class TaxRuleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'item_type' => 'obat',
            'is_taxable' => false,
        ];
    }
}
