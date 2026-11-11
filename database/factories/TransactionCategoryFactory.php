<?php

namespace Database\Factories;

use App\Modules\Accounting\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionCategory>
 */
class TransactionCategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'direction' => 'out',
            'source_code' => null,
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
