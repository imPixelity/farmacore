<?php

namespace Database\Factories;

use App\Modules\Md\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'contact_person' => fake()->name(),
            'payment_terms' => 'Tunai',
            'payment_term_days' => 0,
            'lead_time_days' => fake()->randomFloat(1, 0, 30),
            'is_active' => true,
        ];
    }
}
