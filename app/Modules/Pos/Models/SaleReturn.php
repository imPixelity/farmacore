<?php

namespace App\Modules\Pos\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['return_no', 'sale_id', 'processed_by', 'return_date', 'reason', 'refund_amount', 'restock'])]
class SaleReturn extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return [
            'return_date' => 'datetime',
            'refund_amount' => 'decimal:2',
            'restock' => 'boolean',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
