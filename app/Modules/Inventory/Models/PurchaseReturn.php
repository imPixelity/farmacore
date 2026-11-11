<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\Supplier;
use Database\Factories\PurchaseReturnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'return_no', 'supplier_id', 'goods_receipt_id', 'return_date', 'total_value', 'payable_adjustment_amount',
    'supplier_credit_amount', 'status', 'notes', 'confirmed_by', 'confirmed_at', 'created_by', 'updated_by',
])]
#[UseFactory(PurchaseReturnFactory::class)]
class PurchaseReturn extends Model
{
    /** @use HasFactory<PurchaseReturnFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'total_value' => 'decimal:2',
            'payable_adjustment_amount' => 'decimal:2',
            'supplier_credit_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
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
