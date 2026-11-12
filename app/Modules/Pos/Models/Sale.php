<?php

namespace App\Modules\Pos\Models;

use App\Models\User;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\TaxRecord;
use App\Modules\Rx\Models\Prescription;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'invoice_no', 'sale_type', 'sale_date', 'cashier_id', 'patient_id', 'prescription_id',
    'subtotal', 'discount_total', 'doctor_fee', 'tuslah_fee', 'service_fee_total', 'tax_amount',
    'grand_total', 'payment_status', 'status', 'cancel_reason', 'cancelled_by', 'cancelled_at',
])]
#[UseFactory(SaleFactory::class)]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'doctor_fee' => 'decimal:2',
            'tuslah_fee' => 'decimal:2',
            'service_fee_total' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Penjualan tidak dapat dihapus. Gunakan pembatalan pada phase retur.');
        });
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function receivable(): HasOne
    {
        return $this->hasOne(Receivable::class);
    }

    public function taxRecords(): MorphMany
    {
        return $this->morphMany(TaxRecord::class, 'source');
    }
}
