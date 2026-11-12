<?php

namespace Database\Factories;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SaleItem> */
class SaleItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'item_id' => Item::factory(),
            'unit_id' => Unit::factory(),
            'qty_input' => '1.000',
            'qty_base' => '1.000',
            'unit_price_base' => '1000.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'line_total' => '1000.00',
        ];
    }
}
