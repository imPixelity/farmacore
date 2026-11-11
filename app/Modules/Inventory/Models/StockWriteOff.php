<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\StockWriteOffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'write_off_no', 'item_batch_id', 'write_off_date', 'qty_base', 'reason', 'loss_amount', 'status',
    'approved_by', 'approved_at', 'notes', 'created_by', 'updated_by',
])]
#[UseFactory(StockWriteOffFactory::class)]
class StockWriteOff extends Model
{
    /** @use HasFactory<StockWriteOffFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'write_off_date' => 'date',
            'qty_base' => 'decimal:3',
            'loss_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
