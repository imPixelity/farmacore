<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['item_batch_id', 'qty_change', 'qty_after', 'type', 'reason', 'ref_type', 'ref_id', 'created_by'])]
class StockMovement extends Model
{
    protected static function booted(): void
    {
        static::creating(function (): void {
            if (! StockService::isPostingStock()) {
                throw new LogicException('Mutasi stok hanya boleh dibuat melalui StockService.');
            }
        });

        static::updating(function (): never {
            throw new LogicException('Mutasi stok bersifat append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Mutasi stok tidak dapat dihapus.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'qty_change' => 'decimal:3',
            'qty_after' => 'decimal:3',
        ];
    }

    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class, 'item_batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
