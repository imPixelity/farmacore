<?php

namespace Database\Factories;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ItemPrice> */
class ItemPriceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'sales_type' => 'resep',
            'purchase_price_incl_tax' => '1000.0000',
            'margin_percent' => '20.00',
            'selling_price' => '1200.00',
            'effective_from' => now(),
            'is_current' => true,
        ];
    }
}
