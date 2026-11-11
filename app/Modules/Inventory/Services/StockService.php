<?php

namespace App\Modules\Inventory\Services;

use App\Events\Inventory\StockDecreased;
use App\Modules\Inventory\Exceptions\ExpiredBatchNotSellableException;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Md\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    private const MAX_QUANTITY = '999999999999.999';

    private static bool $postingStock = false;

    public function increase(
        ItemBatch $itemBatch,
        string $qtyBase,
        string $type,
        string $reason,
        ?string $refType = null,
        ?int $refId = null,
    ): StockMovement {
        return $this->postMovement($itemBatch, $qtyBase, $type, $reason, 'in', $refType, $refId);
    }

    public function decrease(
        ItemBatch $itemBatch,
        string $qtyBase,
        string $type,
        string $reason,
        ?string $refType = null,
        ?int $refId = null,
    ): StockMovement {
        return $this->postMovement($itemBatch, $qtyBase, $type, $reason, 'out', $refType, $refId);
    }

    public function available(Item $item): string
    {
        $availableQuantity = ItemBatch::query()
            ->available()
            ->where('item_id', $item->getKey())
            ->selectRaw('COALESCE(SUM(qty_on_hand), 0) AS aggregate')
            ->value('aggregate');

        return bcadd((string) ($availableQuantity ?? '0'), '0', 3);
    }

    public static function isPostingStock(): bool
    {
        return self::$postingStock;
    }

    private function postMovement(
        ItemBatch $itemBatch,
        string $qtyBase,
        string $type,
        string $reason,
        string $direction,
        ?string $refType,
        ?int $refId,
    ): StockMovement {
        $quantity = $this->normalizeQuantity($qtyBase);
        $type = trim($type);
        $reason = trim($reason);
        $refType = $refType === null ? null : trim($refType);

        if (! preg_match('/^[a-z][a-z0-9_]{0,39}$/', $type)) {
            throw new InvalidArgumentException('Jenis mutasi stok tidak valid.');
        }

        if ($reason === '') {
            throw new InvalidArgumentException('Alasan mutasi stok wajib diisi.');
        }

        if ($refType !== null && mb_strlen($refType) > 255) {
            throw new InvalidArgumentException('Jenis referensi mutasi stok terlalu panjang.');
        }

        if ($refId !== null && $refId < 1) {
            throw new InvalidArgumentException('ID referensi mutasi stok harus bernilai positif.');
        }

        $movement = DB::transaction(function () use ($itemBatch, $quantity, $type, $reason, $direction, $refType, $refId): StockMovement {
            $lockedBatch = ItemBatch::query()
                ->lockForUpdate()
                ->findOrFail($itemBatch->getKey());

            if ($direction === 'out'
                && in_array($type, ['sale', 'dispensed', 'used'], true)
                && $lockedBatch->expiry_date->lt(today())) {
                throw new ExpiredBatchNotSellableException((int) $lockedBatch->getKey());
            }

            $currentQuantity = bcadd((string) $lockedBatch->qty_on_hand, '0', 3);
            $newQuantity = $direction === 'in'
                ? bcadd($currentQuantity, $quantity, 3)
                : bcsub($currentQuantity, $quantity, 3);

            if ($direction === 'out' && bccomp($newQuantity, '0', 3) < 0) {
                throw new InsufficientStockException(
                    (int) $lockedBatch->getKey(),
                    $quantity,
                    $currentQuantity,
                );
            }

            if (bccomp($newQuantity, self::MAX_QUANTITY, 3) > 0) {
                throw new InvalidArgumentException('Saldo stok melebihi batas yang dapat dicatat.');
            }

            $previousPostingState = self::$postingStock;
            self::$postingStock = true;

            try {
                $lockedBatch->forceFill([
                    'qty_on_hand' => $newQuantity,
                    'updated_by' => Auth::id(),
                ])->save();

                $movement = new StockMovement;
                $movement->forceFill([
                    'item_batch_id' => $lockedBatch->getKey(),
                    'qty_change' => $direction === 'in' ? $quantity : bcsub('0', $quantity, 3),
                    'qty_after' => $newQuantity,
                    'type' => $type,
                    'reason' => $reason,
                    'ref_type' => $refType === '' ? null : $refType,
                    'ref_id' => $refId,
                    'created_by' => Auth::id(),
                ])->save();

                return $movement;
            } finally {
                self::$postingStock = $previousPostingState;
            }
        }, attempts: 5);

        if ($direction === 'out') {
            StockDecreased::dispatch($itemBatch);
        }

        return $movement;
    }

    private function normalizeQuantity(string $quantity): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $quantity)
            || bccomp($quantity, '0', 3) <= 0
            || bccomp($quantity, self::MAX_QUANTITY, 3) > 0) {
            throw new InvalidArgumentException('Jumlah stok harus positif dengan maksimal tiga angka desimal.');
        }

        return bcadd($quantity, '0', 3);
    }
}
