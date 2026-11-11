<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_order_id', 'item_id', 'unit_id', 'qty_ordered', 'qty_base', 'unit_price', 'line_total'])]
class PurchaseOrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'qty_ordered' => 'decimal:3',
            'qty_base' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
