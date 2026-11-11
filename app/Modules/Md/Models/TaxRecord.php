<?php

namespace App\Modules\Md\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\TaxRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['source_type', 'source_id', 'direction', 'rate_percent', 'taxable_amount', 'tax_amount', 'transaction_date', 'created_by', 'updated_by'])]
#[UseFactory(TaxRecordFactory::class)]
class TaxRecord extends Model
{
    /** @use HasFactory<TaxRecordFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
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
