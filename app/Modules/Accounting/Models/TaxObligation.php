<?php

namespace App\Modules\Accounting\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\TaxObligationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tax_type', 'period', 'amount_due', 'due_date', 'status', 'paid_at', 'notes', 'created_by', 'updated_by'])]
#[UseFactory(TaxObligationFactory::class)]
class TaxObligation extends Model
{
    /** @use HasFactory<TaxObligationFactory> */
    use Auditable, HasFactory;

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
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
