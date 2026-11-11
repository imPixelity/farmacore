<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\PurchaseReturn;
use App\Modules\Inventory\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService
{
    public const REASONS = ['rusak', 'tidak_sesuai', 'mendekati_ed', 'salah_kirim'];

    public function __construct(private StockService $stock) {}

    /** @return Collection<int, PurchaseReturn> */
    public function history(?string $status = null): Collection
    {
        return PurchaseReturn::query()
            ->with(['supplier:id,name', 'goodsReceipt:id,receipt_no,invoice_no', 'creator:id,name', 'confirmer:id,name'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->withCount('items')
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();
    }

    /** @return Collection<int, GoodsReceipt> */
    public function receiptsAvailableForReturn(): Collection
    {
        return GoodsReceipt::query()
            ->with(['supplier:id,name', 'items.item:id,name,code', 'items.batch:id,goods_receipt_item_id,batch_no,expiry_date,qty_on_hand,unit_cost_base'])
            ->where('status', 'confirmed')
            ->whereHas('items.batch', static fn (Builder $query) => $query->where('qty_on_hand', '>', 0))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();
    }

    /** @param array<int, array{goods_receipt_item_id: int|string, item_batch_id: int|string, qty_base: string|int|float, reason: string}> $lines */
    public function createDraft(GoodsReceipt $goodsReceipt, string $returnDate, array $lines, ?string $notes = null): PurchaseReturn
    {
        return DB::transaction(function () use ($goodsReceipt, $returnDate, $lines, $notes): PurchaseReturn {
            $receipt = GoodsReceipt::query()->with(['items.batch'])->lockForUpdate()->findOrFail($goodsReceipt->id);
            $this->assertConfirmedReceipt($receipt);

            if ($lines === []) {
                throw ValidationException::withMessages(['lines' => 'Minimal satu baris retur harus dipilih.']);
            }

            $return = new PurchaseReturn;
            $return->fill([
                'return_no' => 'RET-'.Str::ulid(),
                'supplier_id' => $receipt->supplier_id,
                'goods_receipt_id' => $receipt->id,
                'return_date' => $returnDate,
                'total_value' => '0.00',
                'payable_adjustment_amount' => '0.00',
                'supplier_credit_amount' => '0.00',
                'status' => 'draft',
                'notes' => filled($notes) ? trim($notes) : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $return->save();

            $total = '0.00';
            foreach ($lines as $line) {
                $receiptItem = $receipt->items->firstWhere('id', (int) $line['goods_receipt_item_id']);
                $batch = $receiptItem?->batch;
                if (! $receiptItem || ! $batch || (int) $batch->id !== (int) $line['item_batch_id']) {
                    throw ValidationException::withMessages(['lines' => 'Baris retur tidak sesuai dengan penerimaan asal.']);
                }

                if (bccomp((string) $line['qty_base'], '0', 3) === 0) {
                    continue;
                }

                $quantity = $this->quantity((string) $line['qty_base']);
                $this->assertReason((string) $line['reason']);
                $this->assertReturnLimit($receiptItem, $batch, $quantity);
                $lineValue = $this->lineValue($batch, $receiptItem, $quantity);

                $returnItem = new PurchaseReturnItem;
                $returnItem->fill([
                    'purchase_return_id' => $return->id,
                    'goods_receipt_item_id' => $receiptItem->id,
                    'item_batch_id' => $batch->id,
                    'qty_base' => $quantity,
                    'line_value' => $lineValue,
                    'reason' => $line['reason'],
                ]);
                $returnItem->save();
                $total = bcadd($total, $lineValue, 2);
            }

            if (bccomp($total, '0', 2) <= 0) {
                throw ValidationException::withMessages(['lines' => 'Minimal satu jumlah retur harus lebih besar dari nol.']);
            }

            $return->forceFill(['total_value' => $total])->save();

            return $return->fresh(['supplier', 'goodsReceipt', 'items.goodsReceiptItem.item', 'items.itemBatch']);
        });
    }

    public function confirm(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn): PurchaseReturn {
            $return = PurchaseReturn::query()
                ->with(['items.goodsReceiptItem', 'goodsReceipt.payable'])
                ->lockForUpdate()
                ->findOrFail($purchaseReturn->id);
            if ($return->status !== 'draft') {
                throw ValidationException::withMessages(['return' => 'Retur ini sudah diproses.']);
            }

            foreach ($return->items as $returnItem) {
                $batch = ItemBatch::query()->lockForUpdate()->findOrFail($returnItem->item_batch_id);
                $this->assertReturnLimit($returnItem->goodsReceiptItem, $batch, (string) $returnItem->qty_base, $return->id);
                $this->stock->decrease($batch, (string) $returnItem->qty_base, 'purchase_return', 'Retur pembelian '.$return->return_no, PurchaseReturn::class, $return->id);
            }

            $payableAdjustment = '0.00';
            $supplierCredit = (string) $return->total_value;
            $payable = $return->goodsReceipt?->payable;
            if ($payable) {
                $remaining = $payable->remainingAmount();
                $payableAdjustment = bccomp($return->total_value, $remaining, 2) > 0 ? $remaining : (string) $return->total_value;
                $supplierCredit = bcsub((string) $return->total_value, $payableAdjustment, 2);
                $newTotal = bcsub((string) $payable->total_amount, $payableAdjustment, 2);
                $payable->forceFill([
                    'total_amount' => $newTotal,
                    'status' => bccomp((string) $payable->paid_amount, $newTotal, 2) >= 0 ? 'lunas' : (bccomp((string) $payable->paid_amount, '0', 2) > 0 ? 'sebagian' : 'belum_dibayar'),
                    'updated_by' => Auth::id(),
                ])->save();
            }

            $return->forceFill([
                'status' => 'confirmed',
                'payable_adjustment_amount' => $payableAdjustment,
                'supplier_credit_amount' => $supplierCredit,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'updated_by' => Auth::id(),
            ])->save();

            return $return->fresh(['supplier', 'goodsReceipt.payable', 'items.itemBatch.item', 'confirmer']);
        }, attempts: 5);
    }

    public function cancel(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return DB::transaction(function () use ($purchaseReturn): PurchaseReturn {
            $return = PurchaseReturn::query()->lockForUpdate()->findOrFail($purchaseReturn->id);
            if ($return->status !== 'draft') {
                throw ValidationException::withMessages(['return' => 'Hanya draft retur yang dapat dibatalkan.']);
            }

            $return->forceFill(['status' => 'cancelled', 'updated_by' => Auth::id()])->save();

            return $return;
        });
    }

    private function assertConfirmedReceipt(GoodsReceipt $receipt): void
    {
        if ($receipt->status !== 'confirmed') {
            throw ValidationException::withMessages(['goods_receipt_id' => 'Retur hanya dapat dibuat dari penerimaan yang sudah dikonfirmasi.']);
        }
    }

    private function assertReason(string $reason): void
    {
        if (! in_array($reason, self::REASONS, true)) {
            throw ValidationException::withMessages(['reason' => 'Alasan retur tidak valid.']);
        }
    }

    private function assertReturnLimit(GoodsReceiptItem $receiptItem, ItemBatch $batch, string $quantity, ?int $ignoreReturnId = null): void
    {
        $returned = PurchaseReturnItem::query()
            ->where('goods_receipt_item_id', $receiptItem->id)
            ->where('item_batch_id', $batch->id)
            ->whereHas('purchaseReturn', function (Builder $query) use ($ignoreReturnId): void {
                $query->where('status', '!=', 'cancelled')->when($ignoreReturnId, fn (Builder $query) => $query->where('id', '!=', $ignoreReturnId));
            })
            ->sum('qty_base');
        $remainingPurchased = bcsub((string) $receiptItem->qty_base, (string) $returned, 3);

        if (bccomp($quantity, $remainingPurchased, 3) > 0) {
            throw ValidationException::withMessages(['qty_base' => 'Jumlah retur melebihi jumlah yang diterima setelah retur sebelumnya.']);
        }
        if (bccomp($quantity, (string) $batch->qty_on_hand, 3) > 0) {
            throw ValidationException::withMessages(['qty_base' => 'Jumlah retur melebihi stok batch saat ini.']);
        }
    }

    private function lineValue(ItemBatch $batch, GoodsReceiptItem $receiptItem, string $quantity): string
    {
        $unitCost = $batch->unit_cost_base;
        if ($unitCost === null) {
            $unitCost = bcdiv((string) $receiptItem->line_total, (string) $receiptItem->qty_base, 8);
        }

        return bcmul($quantity, (string) $unitCost, 2);
    }

    private function quantity(string $quantity): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $quantity) || bccomp($quantity, '0', 3) <= 0 || bccomp($quantity, '999999999999.999', 3) > 0) {
            throw ValidationException::withMessages(['qty_base' => 'Jumlah retur harus positif dengan maksimal tiga angka desimal.']);
        }

        return bcadd($quantity, '0', 3);
    }
}
