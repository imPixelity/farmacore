<?php

namespace App\Modules\Md\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\DiscountRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'rule_type', 'sales_type', 'item_id', 'category_id', 'min_qty', 'min_amount', 'discount_type', 'discount_value', 'effective_from', 'effective_until', 'is_active', 'created_by', 'updated_by'])]
#[UseFactory(DiscountRuleFactory::class)]
class DiscountRule extends Model
{
    /** @use HasFactory<DiscountRuleFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'min_qty' => 'decimal:3',
            'min_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class);
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
