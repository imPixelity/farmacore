<?php

namespace App\Modules\Accounting\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\Patient;
use App\Modules\Pos\Models\Sale;
use Database\Factories\ReceivableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sale_id', 'patient_id', 'total_amount', 'paid_amount', 'due_date', 'status', 'created_by', 'updated_by'])]
#[UseFactory(ReceivableFactory::class)]
class Receivable extends Model
{
    /** @use HasFactory<ReceivableFactory> */
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
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
