<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Md\Models\PharmacyStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PharmacyStaff>
 */
class PharmacyStaffFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'position' => 'Tenaga farmasi',
            'is_active' => true,
        ];
    }
}
