<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use Database\Factories\GoodsReceiptItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

#[Fillable([
    'goods_receipt_id', 'purchase_order_item_id', 'item_id', 'unit_id', 'qty_received', 'qty_base', 'batch_no', 'expiry_date',
    'unit_price', 'discount_amount', 'line_total', 'match_status', 'is_return_candidate', 'approved_by', 'created_by', 'updated_by',
])]
#[UseFactory(GoodsReceiptItemFactory::class)]
class GoodsReceiptItem extends Model
{
    /** @use HasFactory<GoodsReceiptItemFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'qty_received' => 'decimal:3',
            'qty_base' => 'decimal:3',
            'expiry_date' => 'date',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_return_candidate' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (GoodsReceiptItem $receiptItem): void {
            if ($receiptItem->receipt()->value('status') !== 'draft') {
                throw new LogicException('Baris penerimaan yang sudah dikonfirmasi tidak dapat diubah.');
            }
        });

        static::deleting(function (GoodsReceiptItem $receiptItem): void {
            if ($receiptItem->receipt()->value('status') !== 'draft') {
                throw new LogicException('Baris penerimaan yang sudah dikonfirmasi tidak dapat dihapus.');
            }
        });
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batch(): HasOne
    {
        return $this->hasOne(ItemBatch::class, 'goods_receipt_item_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
