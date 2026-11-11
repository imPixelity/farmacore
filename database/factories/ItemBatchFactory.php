<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Md\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemBatch>
 */
class ItemBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'batch_no' => fake()->unique()->bothify('BATCH-######'),
            'expiry_date' => fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
        ];
    }
}
