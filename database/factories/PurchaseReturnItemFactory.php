<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\PurchaseReturn;
use App\Modules\Inventory\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseReturnItem> */
class PurchaseReturnItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchase_return_id' => PurchaseReturn::factory(),
            'goods_receipt_item_id' => GoodsReceiptItem::factory(),
            'item_batch_id' => ItemBatch::factory(),
            'qty_base' => '1.000',
            'line_value' => '1000.00',
            'reason' => 'rusak',
        ];
    }
}
