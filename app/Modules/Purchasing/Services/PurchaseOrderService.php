<?php

namespace App\Modules\Purchasing\Services;

use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(private StockService $stock) {}

    /** @return Collection<int, Item> */
    public function recommendations(): Collection
    {
        $items = Item::query()->active()->whereNotNull('reorder_point')
            ->with(['orderUnit:id,name', 'baseUnit:id,name', 'itemUnits:id,item_id,unit_id,factor_to_base'])
            ->orderBy('name')->orderBy('id')->get();

        return $items->filter(function (Item $item): bool {
            return bccomp($this->stock->available($item), (string) $item->reorder_point, 3) <= 0;
        })->each(function (Item $item): void {
            $available = $this->stock->available($item);
            $target = $item->buffer_stock ?? $item->reorder_point;
            $factor = $this->orderUnitFactor($item);
            $shortage = bcsub((string) $target, $available, 3);
            $recommendedBase = bccomp($shortage, '0', 3) > 0 ? $shortage : '0.000';
            $recommendedOrder = $this->roundUp(bcdiv($recommendedBase, $factor, 6));
            $item->setAttribute('available_stock', $available);
            $item->setAttribute('recommended_qty', $recommendedOrder);
            $item->setAttribute('estimated_unit_price', bcmul((string) ($item->hna_base ?? '0'), $factor, 4));
        })->values();
    }

    /** @param array<string, mixed> $data */
    public function createDraft(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data): PurchaseOrder {
            $supplier = Supplier::query()->lockForUpdate()->findOrFail((int) $data['supplier_id']);
            if (! $supplier->is_active) {
                throw ValidationException::withMessages(['supplier_id' => 'Supplier sudah nonaktif.']);
            }
            $lines = $this->prepareLines($data['lines']);
            $order = PurchaseOrder::query()->create([
                'po_no' => 'PO-'.Str::upper(Str::random(12)), 'supplier_id' => $supplier->id,
                'order_date' => CarbonImmutable::parse($data['order_date'])->toDateString(),
                'expected_date' => filled($data['expected_date'] ?? null) ? CarbonImmutable::parse($data['expected_date'])->toDateString() : null,
                'status' => 'draft', 'total_estimated' => $this->total($lines), 'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(), 'updated_by' => Auth::id(),
            ]);
            $order->items()->createMany($lines);

            return $order->load(['supplier', 'items.item', 'items.unit']);
        }, attempts: 5);
    }

    /** @param array<string, mixed> $data */
    public function updateDraft(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $data): PurchaseOrder {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['purchase_order' => 'PO yang sudah dikirim tidak dapat diedit.']);
            }
            $supplier = Supplier::query()->lockForUpdate()->findOrFail((int) $data['supplier_id']);
            if (! $supplier->is_active) {
                throw ValidationException::withMessages(['supplier_id' => 'Supplier sudah nonaktif.']);
            }
            $lines = $this->prepareLines($data['lines']);
            $order->forceFill([
                'supplier_id' => $supplier->id, 'order_date' => CarbonImmutable::parse($data['order_date'])->toDateString(),
                'expected_date' => filled($data['expected_date'] ?? null) ? CarbonImmutable::parse($data['expected_date'])->toDateString() : null,
                'total_estimated' => $this->total($lines), 'notes' => $data['notes'] ?? null, 'updated_by' => Auth::id(),
            ])->save();
            $order->items()->delete();
            $order->items()->createMany($lines);

            return $order->load(['supplier', 'items.item', 'items.unit']);
        }, attempts: 5);
    }

    public function send(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder): PurchaseOrder {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['purchase_order' => 'Hanya PO draft yang dapat dikirim.']);
            }
            if (! $order->items()->exists()) {
                throw ValidationException::withMessages(['purchase_order' => 'PO harus memiliki minimal satu barang.']);
            }
            $order->forceFill(['status' => 'sent', 'sent_at' => now(), 'updated_by' => Auth::id()])->save();

            return $order;
        }, attempts: 5);
    }

    /** @param array<int, array<string, mixed>> $lines @return array<int, array<string, mixed>> */
    private function prepareLines(array $lines): array
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'PO harus memiliki minimal satu barang.']);
        }
        $itemIds = collect($lines)->pluck('item_id')->map(fn ($id): int => (int) $id);
        if ($itemIds->unique()->count() !== $itemIds->count()) {
            throw ValidationException::withMessages(['lines' => 'Barang yang sama tidak boleh dicantumkan lebih dari satu kali.']);
        }
        $items = Item::query()->active()->whereIn('id', $itemIds)->with('itemUnits')->get()->keyBy('id');
        $prepared = [];
        foreach ($lines as $line) {
            $item = $items->get((int) $line['item_id']);
            if (! $item) {
                throw ValidationException::withMessages(['lines' => 'Barang tidak ditemukan atau sudah nonaktif.']);
            }
            $qty = (string) $line['qty_ordered'];
            $unitId = (int) $line['unit_id'];
            $factor = $unitId === (int) $item->base_unit_id ? '1.000' : (string) ($item->itemUnits->firstWhere('unit_id', $unitId)?->factor_to_base ?? '');
            if ($factor === '' || bccomp($factor, '0', 3) <= 0) {
                throw ValidationException::withMessages(['lines' => "Satuan pemesanan {$item->name} tidak valid."]);
            }
            $unitPrice = (string) ($line['unit_price'] ?? bcmul((string) ($item->hna_base ?? '0'), $factor, 4));
            $prepared[] = ['item_id' => $item->id, 'unit_id' => $unitId, 'qty_ordered' => bcadd($qty, '0', 3), 'qty_base' => bcmul($qty, $factor, 3), 'unit_price' => bcadd($unitPrice, '0', 4), 'line_total' => bcmul($qty, $unitPrice, 2)];
        }

        return $prepared;
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function total(array $lines): string
    {
        return array_reduce($lines, fn (string $total, array $line): string => bcadd($total, (string) $line['line_total'], 2), '0.00');
    }

    private function orderUnitFactor(Item $item): string
    {
        return (int) $item->order_unit_id === (int) $item->base_unit_id
            ? '1.000' : (string) ($item->itemUnits->firstWhere('unit_id', $item->order_unit_id)?->factor_to_base ?? '1.000');
    }

    private function roundUp(string $quantity): string
    {
        $whole = bcdiv($quantity, '1', 0);
        if (bccomp($quantity, $whole, 6) > 0) {
            $whole = bcadd($whole, '1', 0);
        }

        return bcadd($whole, '0', 3);
    }
}
