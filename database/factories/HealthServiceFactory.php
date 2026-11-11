<?php

namespace Database\Factories;

use App\Modules\Md\Models\HealthService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthService>
 */
class HealthServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'default_fee' => fake()->randomFloat(2, 0, 500000),
            'is_active' => true,
        ];
    }
}
