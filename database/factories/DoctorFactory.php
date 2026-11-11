<?php

namespace Database\Factories;

use App\Modules\Md\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'identity_no' => fake()->numerify('SIP-########'),
            'phone' => fake()->numerify('08##########'),
            'origin' => fake()->randomElement(['apotek', 'luar']),
            'is_active' => true,
        ];
    }
}
