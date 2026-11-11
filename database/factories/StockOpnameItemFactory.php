<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockOpnameItem> */
class StockOpnameItemFactory extends Factory
{
    public function definition(): array
    {
        $systemQty = '0.000';

        return [
            'stock_opname_id' => StockOpname::factory(),
            'item_batch_id' => ItemBatch::factory(),
            'system_qty' => $systemQty,
            'physical_qty' => $systemQty,
            'diff_qty' => '0.000',
        ];
    }
}
