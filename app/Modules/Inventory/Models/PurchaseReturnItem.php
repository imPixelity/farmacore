<?php

namespace App\Modules\Inventory\Models;

use Database\Factories\PurchaseReturnItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_return_id', 'goods_receipt_item_id', 'item_batch_id', 'qty_base', 'line_value', 'reason'])]
#[UseFactory(PurchaseReturnItemFactory::class)]
class PurchaseReturnItem extends Model
{
    /** @use HasFactory<PurchaseReturnItemFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['qty_base' => 'decimal:3', 'line_value' => 'decimal:2'];
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }
}
