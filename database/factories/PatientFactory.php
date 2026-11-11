<?php

namespace Database\Factories;

use App\Modules\Md\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PAT-######'),
            'name' => fake()->name(),
            'identity_no' => fake()->unique()->numerify('################'),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'is_active' => true,
        ];
    }
}
