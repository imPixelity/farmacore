<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Pos\Models\SaleItem;
use App\Modules\Pos\Models\SaleItemBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleItemBatch> */
class SaleItemBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_item_id' => SaleItem::factory(),
            'item_batch_id' => ItemBatch::factory(),
            'qty_base' => '1.000',
        ];
    }
}
