<?php

namespace App\Modules\Purchasing\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Md\Models\Supplier;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['po_no', 'supplier_id', 'order_date', 'expected_date', 'status', 'sent_at', 'total_estimated', 'notes', 'created_by', 'updated_by'])]
#[UseFactory(PurchaseOrderFactory::class)]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use Auditable, HasFactory;

    protected $attributes = ['status' => 'draft', 'total_estimated' => '0.00'];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'sent_at' => 'datetime',
            'total_estimated' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (PurchaseOrder $order): void {
            if ($order->getOriginal('status') !== 'draft' && $order->isDirty(['supplier_id', 'order_date', 'expected_date', 'total_estimated', 'notes'])) {
                throw new LogicException('PO yang sudah dikirim tidak dapat diedit.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Purchase order tidak dapat dihapus.');
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
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
