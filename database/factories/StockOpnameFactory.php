<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Inventory\Models\StockOpname;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockOpname> */
class StockOpnameFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opname_no' => fake()->unique()->bothify('OP-######'),
            'opname_date' => today(),
            'status' => 'draft',
            'performed_by' => User::factory(),
            'notes' => null,
        ];
    }
}
