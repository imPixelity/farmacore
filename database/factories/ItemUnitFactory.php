<?php

namespace Database\Factories;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemUnit>
 */
class ItemUnitFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'unit_id' => Unit::factory(),
            'factor_to_base' => fake()->randomFloat(3, 0.001, 1000),
        ];
    }
}
