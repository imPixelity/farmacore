<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use Database\Factories\ItemBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['item_id', 'batch_no', 'expiry_date', 'created_by', 'updated_by'])]
#[UseFactory(ItemBatchFactory::class)]
class ItemBatch extends Model
{
    /** @use HasFactory<ItemBatchFactory> */
    use HasFactory;

    /** @var array<string, string> */
    protected $attributes = [
        'qty_on_hand' => '0.000',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'qty_on_hand' => 'decimal:3',
        ];
    }

    /** @param Builder<ItemBatch> $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->whereDate('expiry_date', '>=', today()->toDateString())
            ->where('qty_on_hand', '>', 0)
            ->whereHas('item', static fn (Builder $itemQuery) => $itemQuery->where('is_active', true));
    }

    protected static function booted(): void
    {
        static::creating(function (ItemBatch $batch): void {
            if (bccomp((string) $batch->qty_on_hand, '0', 3) !== 0) {
                throw new LogicException('Saldo awal batch harus dicatat melalui StockService.');
            }
        });

        static::updating(function (ItemBatch $batch): void {
            if ($batch->isDirty('qty_on_hand') && ! StockService::isPostingStock()) {
                throw new LogicException('Saldo batch hanya boleh diubah melalui StockService.');
            }

            if ($batch->isDirty(['item_id', 'batch_no', 'expiry_date']) && $batch->movements()->exists()) {
                throw new LogicException('Identitas batch tidak dapat diubah setelah memiliki mutasi stok.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Batch stok tidak dapat dihapus.');
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'item_batch_id');
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
