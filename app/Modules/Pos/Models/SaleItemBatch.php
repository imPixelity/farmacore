<?php

namespace App\Modules\Pos\Models;

use App\Modules\Inventory\Models\ItemBatch;
use Database\Factories\SaleItemBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_item_id', 'item_batch_id', 'qty_base'])]
#[UseFactory(SaleItemBatchFactory::class)]
class SaleItemBatch extends Model
{
    /** @use HasFactory<SaleItemBatchFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['qty_base' => 'decimal:3'];
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
