<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockWriteOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockWriteOff> */
class StockWriteOffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'write_off_no' => fake()->unique()->bothify('WO-######'),
            'item_batch_id' => ItemBatch::factory(),
            'write_off_date' => today(),
            'qty_base' => '1.000',
            'reason' => 'rusak',
            'loss_amount' => '0.00',
            'status' => 'draft',
            'notes' => null,
        ];
    }
}
