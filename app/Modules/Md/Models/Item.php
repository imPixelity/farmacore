<?php

namespace App\Modules\Md\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Inventory\Models\ItemBatch;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'item_type', 'name', 'dosage_form', 'strength_volume', 'category_id',
    'base_unit_id', 'order_unit_id', 'barcode', 'is_taxable', 'hna_base', 'movement_class', 'min_stock_override',
    'min_stock', 'average_monthly_usage', 'buffer_stock', 'reorder_point', 'stock_policy_recalculated_at',
    'is_active', 'created_by', 'updated_by',
])]
#[UseFactory(ItemFactory::class)]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'hna_base' => 'decimal:4',
            'min_stock_override' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'average_monthly_usage' => 'decimal:6',
            'buffer_stock' => 'decimal:3',
            'reorder_point' => 'decimal:3',
            'stock_policy_recalculated_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<Item> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function orderUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'order_unit_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ItemBatch::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function itemUnits(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
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
