<?php

namespace App\Modules\Pos\Models;

use App\Modules\Inventory\Models\ItemBatch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_return_id', 'sale_item_id', 'item_batch_id', 'qty_base', 'unit_price_base', 'line_total'])]
class SaleReturnItem extends Model
{
    protected function casts(): array
    {
        return [
            'qty_base' => 'decimal:3',
            'unit_price_base' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }
}
