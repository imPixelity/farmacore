<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Models\StockOpnameItem;
use App\Modules\Inventory\Models\StockWriteOff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(private StockService $stock) {}

    /** @return Collection<int, StockOpname> */
    public function history(?string $status = null): Collection
    {
        return StockOpname::query()
            ->with(['performer:id,name', 'approver:id,name'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->withCount('items')
            ->orderByDesc('opname_date')
            ->orderByDesc('id')
            ->get();
    }

    public function createDraft(string $opnameDate, ?string $notes = null): StockOpname
    {
        return DB::transaction(function () use ($opnameDate, $notes): StockOpname {
            $opname = new StockOpname;
            $opname->fill([
                'opname_no' => 'OP-'.Str::ulid(),
                'opname_date' => $opnameDate,
                'status' => 'draft',
                'performed_by' => Auth::id(),
                'notes' => $notes,
            ]);
            $opname->save();

            ItemBatch::query()
                ->with('item:id,name,code')
                ->where('qty_on_hand', '>', 0)
                ->whereHas('item', static fn (Builder $query) => $query->where('is_active', true))
                ->orderBy('id')
                ->chunkById(200, function (Collection $batches) use ($opname): void {
                    foreach ($batches as $batch) {
                        $item = new StockOpnameItem;
                        $item->fill([
                            'stock_opname_id' => $opname->id,
                            'item_batch_id' => $batch->id,
                            'system_qty' => $batch->qty_on_hand,
                            'physical_qty' => null,
                            'diff_qty' => null,
                        ]);
                        $item->save();
                    }
                });

            return $opname->fresh(['items.itemBatch.item']);
        });
    }

    /** @param array<int, array{physical_qty: string|int|float, note?: string|null}> $items */
    public function updatePhysical(StockOpname $stockOpname, array $items): StockOpname
    {
        return DB::transaction(function () use ($stockOpname, $items): StockOpname {
            $opname = StockOpname::query()->lockForUpdate()->findOrFail($stockOpname->id);
            $this->assertStatus($opname, 'draft');

            foreach ($items as $itemId => $data) {
                $opnameItem = StockOpnameItem::query()
                    ->where('stock_opname_id', $opname->id)
                    ->lockForUpdate()
                    ->findOrFail((int) $itemId);
                $physicalQty = $this->quantity((string) $data['physical_qty'], 'physical_qty');

                $opnameItem->forceFill([
                    'physical_qty' => $physicalQty,
                    'diff_qty' => bcsub($physicalQty, (string) $opnameItem->system_qty, 3),
                    'note' => filled($data['note'] ?? null) ? trim((string) $data['note']) : null,
                ])->save();
            }

            return $opname->fresh(['items.itemBatch.item']);
        });
    }

    public function submit(StockOpname $stockOpname): StockOpname
    {
        return DB::transaction(function () use ($stockOpname): StockOpname {
            $opname = StockOpname::query()->with('items')->lockForUpdate()->findOrFail($stockOpname->id);
            $this->assertStatus($opname, 'draft');

            if ($opname->items->isEmpty() || $opname->items->contains(fn (StockOpnameItem $item): bool => $item->physical_qty === null)) {
                throw ValidationException::withMessages(['items' => 'Semua hasil hitung fisik harus diisi sebelum diajukan.']);
            }

            $opname->forceFill(['status' => 'submitted'])->save();

            return $opname->fresh(['items.itemBatch.item']);
        });
    }

    public function approve(StockOpname $stockOpname): StockOpname
    {
        return DB::transaction(function () use ($stockOpname): StockOpname {
            $opname = StockOpname::query()->with('items')->lockForUpdate()->findOrFail($stockOpname->id);
            $this->assertStatus($opname, 'submitted');

            foreach ($opname->items as $opnameItem) {
                $batch = ItemBatch::query()->lockForUpdate()->findOrFail($opnameItem->item_batch_id);
                $difference = bcsub((string) $opnameItem->physical_qty, (string) $batch->qty_on_hand, 3);

                if (bccomp($difference, '0', 3) > 0) {
                    $this->stock->increase($batch, $difference, 'opname_adjust', 'Penyesuaian stock opname '.$opname->opname_no, StockOpname::class, $opname->id);
                } elseif (bccomp($difference, '0', 3) < 0) {
                    $this->stock->decrease($batch, bcsub('0', $difference, 3), 'opname_adjust', 'Penyesuaian stock opname '.$opname->opname_no, StockOpname::class, $opname->id);
                }
            }

            $opname->forceFill([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ])->save();

            return $opname->fresh(['items.itemBatch.item', 'performer', 'approver']);
        }, attempts: 5);
    }

    public function reject(StockOpname $stockOpname, ?string $notes = null): StockOpname
    {
        return DB::transaction(function () use ($stockOpname, $notes): StockOpname {
            $opname = StockOpname::query()->lockForUpdate()->findOrFail($stockOpname->id);
            $this->assertStatus($opname, 'submitted');
            $opname->forceFill([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'notes' => filled($notes) ? trim($notes) : $opname->notes,
            ])->save();

            return $opname;
        });
    }

    public function createWriteOff(ItemBatch $batch, string $writeOffDate, string $qtyBase, string $reason, ?string $notes = null): StockWriteOff
    {
        $quantity = $this->quantity($qtyBase, 'qty_base');
        if (! in_array($reason, ['rusak', 'kedaluwarsa'], true)) {
            throw ValidationException::withMessages(['reason' => 'Alasan write-off tidak valid.']);
        }

        return DB::transaction(function () use ($batch, $writeOffDate, $quantity, $reason, $notes): StockWriteOff {
            $lockedBatch = ItemBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if (bccomp($quantity, (string) $lockedBatch->qty_on_hand, 3) > 0) {
                throw ValidationException::withMessages(['qty_base' => 'Jumlah write-off melebihi stok batch.']);
            }

            $writeOff = new StockWriteOff;
            $writeOff->fill([
                'write_off_no' => 'WO-'.Str::ulid(),
                'item_batch_id' => $lockedBatch->id,
                'write_off_date' => $writeOffDate,
                'qty_base' => $quantity,
                'reason' => $reason,
                'loss_amount' => bcmul($quantity, (string) ($lockedBatch->unit_cost_base ?? '0'), 2),
                'status' => 'draft',
                'notes' => filled($notes) ? trim($notes) : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $writeOff->save();

            return $writeOff->fresh(['itemBatch.item']);
        });
    }

    public function approveWriteOff(StockWriteOff $stockWriteOff): StockWriteOff
    {
        return DB::transaction(function () use ($stockWriteOff): StockWriteOff {
            $writeOff = StockWriteOff::query()->lockForUpdate()->findOrFail($stockWriteOff->id);
            if ($writeOff->status !== 'draft') {
                throw ValidationException::withMessages(['write_off' => 'Write-off ini sudah diproses.']);
            }

            $batch = ItemBatch::query()->lockForUpdate()->findOrFail($writeOff->item_batch_id);
            if (bccomp((string) $writeOff->qty_base, (string) $batch->qty_on_hand, 3) > 0) {
                throw ValidationException::withMessages(['write_off' => 'Stok batch tidak mencukupi untuk write-off.']);
            }

            $this->stock->decrease($batch, (string) $writeOff->qty_base, $writeOff->reason, 'Write-off '.$writeOff->write_off_no, StockWriteOff::class, $writeOff->id);
            $writeOff->forceFill([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'updated_by' => Auth::id(),
            ])->save();

            return $writeOff->fresh(['itemBatch.item', 'approver']);
        }, attempts: 5);
    }

    /** @return Collection<int, StockWriteOff> */
    public function writeOffHistory(?string $status = null): Collection
    {
        return StockWriteOff::query()
            ->with(['itemBatch.item:id,name,code', 'approver:id,name'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('write_off_date')
            ->orderByDesc('id')
            ->get();
    }

    private function assertStatus(StockOpname $opname, string $expected): void
    {
        if ($opname->status !== $expected) {
            throw ValidationException::withMessages(['opname' => 'Status stock opname tidak memungkinkan tindakan ini.']);
        }
    }

    private function quantity(string $quantity, string $field): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $quantity)
            || bccomp($quantity, '0', 3) < 0
            || bccomp($quantity, '999999999999.999', 3) > 0) {
            throw ValidationException::withMessages([$field => 'Jumlah harus nol atau positif dengan maksimal tiga angka desimal.']);
        }

        return bcadd($quantity, '0', 3);
    }
}
