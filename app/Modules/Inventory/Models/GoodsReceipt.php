<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

#[Fillable([
    'receipt_no', 'supplier_id', 'purchase_order_id', 'invoice_no', 'invoice_date', 'due_date', 'payment_method',
    'discount_amount', 'total_before_tax', 'tax_amount', 'total_after_tax', 'matching_status',
    'status', 'confirmed_by', 'confirmed_at', 'created_by', 'updated_by',
])]
#[UseFactory(GoodsReceiptFactory::class)]
class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use Auditable, HasFactory;

    private static bool $confirming = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'discount_amount' => 'decimal:2',
            'total_before_tax' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_after_tax' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    /** @template TReturn
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function duringConfirmation(callable $callback): mixed
    {
        $previous = self::$confirming;
        self::$confirming = true;

        try {
            return $callback();
        } finally {
            self::$confirming = $previous;
        }
    }

    protected static function booted(): void
    {
        static::creating(function (GoodsReceipt $receipt): void {
            if ($receipt->status !== 'draft') {
                throw new LogicException('Penerimaan barang harus dibuat sebagai draft.');
            }
        });

        static::updating(function (GoodsReceipt $receipt): void {
            if ($receipt->getOriginal('status') !== 'draft') {
                throw new LogicException('Penerimaan yang sudah dikonfirmasi tidak dapat diubah.');
            }

            if ($receipt->isDirty('status') && $receipt->status === 'confirmed' && ! self::$confirming) {
                throw new LogicException('Penerimaan hanya dapat dikonfirmasi melalui GoodsReceiptService.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Penerimaan barang tidak dapat dihapus.');
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function payable(): HasOne
    {
        return $this->hasOne(Payable::class);
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
