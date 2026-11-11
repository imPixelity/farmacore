<?php

namespace App\Modules\Purchasing\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Md\Models\Supplier;
use Database\Factories\PayableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'supplier_id', 'goods_receipt_id', 'invoice_no', 'total_amount', 'paid_amount', 'due_date',
    'status', 'created_by', 'updated_by',
])]
#[UseFactory(PayableFactory::class)]
class Payable extends Model
{
    /** @use HasFactory<PayableFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
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

    public function payments(): HasMany
    {
        return $this->hasMany(PayablePayment::class);
    }

    public function remainingAmount(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->paid_amount, 2);
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
