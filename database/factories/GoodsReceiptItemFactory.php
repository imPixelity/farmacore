<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptItem>
 */
class GoodsReceiptItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'item_id' => Item::factory(),
            'unit_id' => Unit::factory(),
            'qty_received' => '1.000',
            'qty_base' => '1.000',
            'batch_no' => fake()->bothify('BATCH-######'),
            'expiry_date' => today()->addYear()->toDateString(),
            'unit_price' => '1000.00',
            'discount_amount' => '0.00',
            'line_total' => '1000.00',
            'match_status' => 'tidak_dipesan',
        ];
    }
}
