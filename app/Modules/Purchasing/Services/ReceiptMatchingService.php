<?php

namespace App\Modules\Purchasing\Services;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptMatchingService
{
    /** @return array{status: string, warnings: array<int, string>, lines: array<int, array{receipt_item: GoodsReceiptItem, status: string, return_candidate: bool, po_item: ?PurchaseOrderItem, remaining: ?string}>} */
    public function preview(GoodsReceipt $receipt): array
    {
        if ($receipt->purchase_order_id === null) {
            return ['status' => 'tidak_ada_po', 'warnings' => [], 'lines' => []];
        }

        $receipt->loadMissing(['items.item', 'purchaseOrder.items.item']);
        $lines = [];
        $warnings = [];

        foreach ($receipt->items as $receiptItem) {
            $poItem = $receipt->purchaseOrder->items->firstWhere('item_id', $receiptItem->item_id);
            $line = $this->matchLine($receiptItem, $poItem);
            $lines[] = $line;

            if (in_array($line['status'], ['lebih', 'harga_beda'], true)) {
                $warnings[] = $this->warning($line['status'], $receiptItem, $line['remaining']);
            }
        }

        $status = collect($lines)->every(fn (array $line): bool => $line['status'] === 'sesuai') ? 'sesuai' : 'tidak_sesuai';

        return ['status' => $status, 'warnings' => $warnings, 'lines' => $lines];
    }

    public function matchAndApply(GoodsReceipt $goodsReceipt, bool $confirmed = false): GoodsReceipt
    {
        return DB::transaction(function () use ($goodsReceipt, $confirmed): GoodsReceipt {
            $receipt = GoodsReceipt::query()
                ->with(['items.item.itemUnits', 'purchaseOrder.items.item.itemUnits'])
                ->lockForUpdate()
                ->findOrFail($goodsReceipt->id);

            if ($receipt->purchase_order_id === null) {
                return $receipt;
            }
            if (! $receipt->purchaseOrder || ! in_array($receipt->purchaseOrder->status, ['sent', 'partially_received'], true)) {
                throw ValidationException::withMessages(['purchase_order' => 'PO harus berstatus terkirim atau diterima sebagian.']);
            }
            if ((int) $receipt->supplier_id !== (int) $receipt->purchaseOrder->supplier_id) {
                throw ValidationException::withMessages(['purchase_order' => 'Supplier penerimaan harus sama dengan supplier pada PO.']);
            }

            $preview = $this->preview($receipt);
            if ($preview['warnings'] !== [] && ! $confirmed) {
                throw ValidationException::withMessages(['matching' => 'Jumlah atau harga penerimaan berbeda dari PO. Periksa peringatan lalu konfirmasi kembali.']);
            }

            foreach ($preview['lines'] as $line) {
                /** @var GoodsReceiptItem $receiptItem */
                $receiptItem = $line['receipt_item'];
                $poItem = $line['po_item'];
                $receiptItem->forceFill([
                    'purchase_order_item_id' => $poItem?->id,
                    'match_status' => $line['status'],
                    'is_return_candidate' => $line['return_candidate'],
                    'updated_by' => Auth::id(),
                ])->save();

                if ($poItem) {
                    $lockedPoItem = PurchaseOrderItem::query()->lockForUpdate()->findOrFail($poItem->id);
                    $lockedPoItem->forceFill([
                        'qty_received' => bcadd((string) $lockedPoItem->qty_received, (string) $receiptItem->qty_base, 3),
                    ])->save();
                }
            }

            $receipt->forceFill([
                'matching_status' => $preview['status'],
                'updated_by' => Auth::id(),
            ])->save();

            $this->updatePurchaseOrderStatus($receipt->purchaseOrder->fresh('items'));
            $this->updateSupplierLeadTime($receipt->supplier_id);

            return $receipt->fresh(['supplier', 'purchaseOrder', 'items.item', 'items.purchaseOrderItem']);
        }, attempts: 5);
    }

    /** @return array{average_lead_time_days: ?string, delivery_accuracy_percent: ?string, matching_accuracy_percent: ?string, price_history: Collection<int, GoodsReceiptItem>} */
    public function supplierMetrics(Supplier $supplier): array
    {
        $receipts = GoodsReceipt::query()
            ->with(['purchaseOrder', 'items.item'])
            ->where('supplier_id', $supplier->id)
            ->where('status', 'confirmed')
            ->whereNotNull('purchase_order_id')
            ->get();

        $leadTimes = $receipts->map(function (GoodsReceipt $receipt): ?int {
            if (! $receipt->purchaseOrder?->sent_at) {
                return null;
            }

            return max(0, $receipt->purchaseOrder->sent_at->startOfDay()->diffInDays($receipt->invoice_date, false));
        })->filter(fn (?int $leadTime): bool => $leadTime !== null);
        $deliveredOnTime = $receipts->filter(fn (GoodsReceipt $receipt): bool => $receipt->purchaseOrder?->expected_date !== null && $receipt->invoice_date->lte($receipt->purchaseOrder->expected_date))->count();
        $matchingCount = $receipts->filter(fn (GoodsReceipt $receipt): bool => $receipt->matching_status === 'sesuai')->count();

        return [
            'average_lead_time_days' => $leadTimes->isEmpty() ? null : number_format($leadTimes->avg(), 1, '.', ''),
            'delivery_accuracy_percent' => $receipts->isEmpty() ? null : number_format($deliveredOnTime / $receipts->count() * 100, 2, '.', ''),
            'matching_accuracy_percent' => $receipts->isEmpty() ? null : number_format($matchingCount / $receipts->count() * 100, 2, '.', ''),
            'price_history' => GoodsReceiptItem::query()->with(['receipt:id,supplier_id,invoice_date', 'item:id,name,code'])->whereHas('receipt', fn ($query) => $query->where('supplier_id', $supplier->id)->where('status', 'confirmed'))->latest('id')->limit(50)->get(),
        ];
    }

    public function updateSupplierLeadTime(int $supplierId): void
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        $metrics = $this->supplierMetrics($supplier);
        if ($metrics['average_lead_time_days'] !== null) {
            $supplier->forceFill(['lead_time_days' => $metrics['average_lead_time_days'], 'updated_by' => Auth::id()])->save();
        }
    }

    /** @return array{receipt_item: GoodsReceiptItem, status: string, return_candidate: bool, po_item: ?PurchaseOrderItem, remaining: ?string} */
    private function matchLine(GoodsReceiptItem $receiptItem, ?PurchaseOrderItem $poItem): array
    {
        if (! $poItem || (int) $poItem->item_id !== (int) $receiptItem->item_id) {
            return ['receipt_item' => $receiptItem, 'status' => 'tidak_dipesan', 'return_candidate' => true, 'po_item' => null, 'remaining' => null];
        }

        $remaining = bcsub((string) $poItem->qty_base, (string) $poItem->qty_received, 3);
        $status = bccomp((string) $receiptItem->qty_base, $remaining, 3) > 0
            ? 'lebih'
            : (bccomp((string) $receiptItem->qty_base, $remaining, 3) < 0 ? 'kurang' : 'sesuai');

        if ($status === 'sesuai' && $this->priceDiffers($receiptItem, $poItem)) {
            $status = 'harga_beda';
        }

        return ['receipt_item' => $receiptItem, 'status' => $status, 'return_candidate' => $status !== 'sesuai', 'po_item' => $poItem, 'remaining' => $remaining];
    }

    private function priceDiffers(GoodsReceiptItem $receiptItem, PurchaseOrderItem $poItem): bool
    {
        $receiptFactor = $this->factor($receiptItem->item, (int) $receiptItem->unit_id);
        $poFactor = $this->factor($poItem->item, (int) $poItem->unit_id);
        $receiptBasePrice = bcdiv((string) $receiptItem->unit_price, $receiptFactor, 6);
        $poBasePrice = bcdiv((string) $poItem->unit_price, $poFactor, 6);

        return bccomp($receiptBasePrice, $poBasePrice, 4) !== 0;
    }

    private function factor(Item $item, int $unitId): string
    {
        if ((int) $item->base_unit_id === $unitId) {
            return '1.000';
        }

        return (string) ($item->itemUnits->firstWhere('unit_id', $unitId)?->factor_to_base ?? '1.000');
    }

    private function updatePurchaseOrderStatus(PurchaseOrder $order): void
    {
        $ordered = $order->items->sum(fn (PurchaseOrderItem $item): float => (float) $item->qty_base);
        $received = $order->items->sum(fn (PurchaseOrderItem $item): float => (float) $item->qty_received);
        $status = $received <= 0 ? 'sent' : ($received < $ordered ? 'partially_received' : 'received');
        $order->forceFill(['status' => $status, 'updated_by' => Auth::id()])->save();
    }

    private function warning(string $status, GoodsReceiptItem $item, ?string $remaining): string
    {
        return $status === 'lebih'
            ? "Jumlah {$item->item->name} melebihi sisa PO ({$remaining} satuan dasar)."
            : "Harga {$item->item->name} berbeda dari harga PO.";
    }
}
