<?php

namespace App\Modules\Md\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\ItemPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['item_id', 'sales_type', 'purchase_price_incl_tax', 'margin_percent', 'selling_price', 'effective_from', 'is_current', 'created_by', 'updated_by'])]
#[UseFactory(ItemPriceFactory::class)]
class ItemPrice extends Model
{
    /** @use HasFactory<ItemPriceFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'purchase_price_incl_tax' => 'decimal:4',
            'margin_percent' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'effective_from' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
