<?php

namespace App\Modules\Pos\Services;

use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Services\DiscountService;
use App\Modules\Md\Services\PricingService;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PosCartService
{
    /** @var list<string> */
    public const SALES_TYPES = ['resep', 'obat_bebas', 'produk_lain'];

    public function __construct(
        private PricingService $pricing,
        private DiscountService $discounts,
        private StockService $stock,
    ) {}

    /** @return Collection<int, Item> */
    public function searchItems(?string $search = null, ?int $categoryId = null): Collection
    {
        $search = trim((string) $search);

        return Item::query()
            ->with(['category:id,name', 'baseUnit:id,name'])
            ->withSum(['batches as available_stock' => fn (Builder $query): Builder => $query->available()], 'qty_on_hand')
            ->active()
            ->when($categoryId, fn (Builder $query, int $categoryId): Builder => $query->where('category_id', $categoryId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('code', "%{$search}%")
                        ->orWhereLike('barcode', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit(20)
            ->get();
    }

    /** @return Collection<int, ItemCategory> */
    public function categories(): Collection
    {
        return ItemCategory::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** @return Collection<int, Prescription> */
    public function verifiedPrescriptions(): Collection
    {
        return Prescription::query()
            ->where('status', 'verified')
            ->with(['patient:id,name,code', 'doctor:id,name', 'items.item:id,name,code,category_id,base_unit_id'])
            ->orderByDesc('rx_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    /**
     * @param  array<string, array{item_id: int|string, quantity: string|int|float, prescription_item_id?: int|string|null}>  $cart
     * @return array<string, array{item_id: int, quantity: string, prescription_item_id: int|null}>
     */
    public function addItem(array $cart, int $itemId, string $quantity = '1.000', ?int $prescriptionItemId = null): array
    {
        $item = $this->activeItem($itemId);
        $key = $prescriptionItemId === null ? "item-{$itemId}" : "rx-item-{$prescriptionItemId}";
        $currentQuantity = isset($cart[$key]) ? (string) $cart[$key]['quantity'] : '0.000';
        $newQuantity = bcadd($currentQuantity, $this->quantity($quantity), 3);

        $prescriptionItem = $this->assertPrescriptionItem($item, $prescriptionItemId);
        $this->assertPrescriptionQuantity($prescriptionItem, $newQuantity);
        $this->assertAvailableStock($item, $newQuantity);

        $cart[$key] = [
            'item_id' => $item->id,
            'quantity' => $newQuantity,
            'prescription_item_id' => $prescriptionItemId,
        ];

        return $cart;
    }

    /**
     * @param  array<string, array{item_id: int|string, quantity: string|int|float, prescription_item_id?: int|string|null}>  $cart
     * @return array<string, array{item_id: int, quantity: string, prescription_item_id: int|null}>
     */
    public function setQuantity(array $cart, string $key, string $quantity): array
    {
        if (! isset($cart[$key])) {
            throw ValidationException::withMessages(['cart' => 'Baris keranjang tidak ditemukan.']);
        }

        $normalizedQuantity = $this->quantity($quantity);
        $item = $this->activeItem((int) $cart[$key]['item_id']);
        $prescriptionItemId = isset($cart[$key]['prescription_item_id']) ? (int) $cart[$key]['prescription_item_id'] : null;
        $prescriptionItem = $this->assertPrescriptionItem($item, $prescriptionItemId);
        $this->assertPrescriptionQuantity($prescriptionItem, $normalizedQuantity);
        $this->assertAvailableStock($item, $normalizedQuantity);
        $cart[$key]['quantity'] = $normalizedQuantity;

        return $cart;
    }

    /**
     * @param  array<string, array{item_id: int|string, quantity: string|int|float, prescription_item_id?: int|string|null}>  $cart
     * @return array{subtotal: string, discount_amount: string, total: string, lines: list<array{key: string, item_id: int, name: string, code: string, barcode: string|null, unit_name: string, quantity: string, available_stock: string, unit_price: string, subtotal: string, discount_amount: string, total: string, prescription_item_id: int|null, prescription_no: string|null, dosage_instruction: string|null, rule_id: int|null}>, applied_rule_ids: list<int>}
     */
    public function summary(array $cart, string $salesType): array
    {
        $this->assertSalesType($salesType);
        $lines = [];

        foreach ($cart as $key => $line) {
            if (! is_array($line) || ! isset($line['item_id'], $line['quantity']) || ! is_numeric($line['item_id'])) {
                throw ValidationException::withMessages(['cart' => 'Format baris keranjang tidak valid.']);
            }

            $item = $this->activeItem((int) $line['item_id']);
            $quantity = $this->quantity((string) $line['quantity']);
            $this->assertAvailableStock($item, $quantity);
            $prescriptionItemId = isset($line['prescription_item_id']) ? (int) $line['prescription_item_id'] : null;
            $prescriptionItem = $this->assertPrescriptionItem($item, $prescriptionItemId);
            $price = $this->pricing->currentPrice($item, $salesType);

            if (! $price) {
                throw ValidationException::withMessages(['cart' => "Harga {$item->name} untuk jenis penjualan ini belum tersedia."]);
            }

            $lines[] = [
                'key' => (string) $key,
                'item_id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'barcode' => $item->barcode,
                'unit_name' => $item->baseUnit->name,
                'quantity' => $quantity,
                'available_stock' => $this->stock->available($item),
                'unit_price' => (string) $price->selling_price,
                'prescription_item_id' => $prescriptionItem?->id,
                'prescription_no' => $prescriptionItem?->prescription?->rx_no,
                'dosage_instruction' => $prescriptionItem?->dosage_instruction,
            ];
        }

        $discount = $this->discounts->apply(array_map(fn (array $line): array => [
            'item_id' => $line['item_id'],
            'category_id' => $this->activeItem($line['item_id'])->category_id,
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
        ], $lines), $salesType);

        foreach ($discount['lines'] as $index => $discountLine) {
            $lines[$index] = array_merge($lines[$index], [
                'subtotal' => $discountLine['subtotal'],
                'discount_amount' => $discountLine['discount_amount'],
                'total' => $discountLine['total'],
                'rule_id' => $discountLine['rule_id'],
            ]);
        }

        return [
            'subtotal' => $discount['subtotal'],
            'discount_amount' => $discount['discount_amount'],
            'total' => $discount['total'],
            'lines' => $lines,
            'applied_rule_ids' => $discount['applied_rule_ids'],
        ];
    }

    private function activeItem(int $itemId): Item
    {
        return Item::query()->with(['category:id,name', 'baseUnit:id,name'])->active()->findOrFail($itemId);
    }

    private function assertAvailableStock(Item $item, string $quantity): void
    {
        $available = $this->stock->available($item);

        if (bccomp($quantity, $available, 3) > 0) {
            throw ValidationException::withMessages([
                'cart' => "Stok {$item->name} tidak mencukupi. Tersedia {$available} {$item->baseUnit->name}.",
            ]);
        }
    }

    private function assertPrescriptionQuantity(?PrescriptionItem $prescriptionItem, string $quantity): void
    {
        if ($prescriptionItem !== null && bccomp($quantity, (string) $prescriptionItem->qty_prescribed, 3) > 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah item resep tidak boleh melebihi jumlah yang diresepkan.']);
        }
    }

    private function assertSalesType(string $salesType): void
    {
        if (! in_array($salesType, self::SALES_TYPES, true)) {
            throw ValidationException::withMessages(['sales_type' => 'Jenis penjualan tidak valid.']);
        }
    }

    private function quantity(string $quantity): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $quantity) || bccomp($quantity, '0', 3) <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah harus positif dengan maksimal tiga angka desimal.']);
        }

        return bcadd($quantity, '0', 3);
    }

    private function assertPrescriptionItem(Item $item, ?int $prescriptionItemId): ?PrescriptionItem
    {
        if ($prescriptionItemId === null) {
            return null;
        }

        $prescriptionItem = PrescriptionItem::query()
            ->with('prescription')
            ->whereKey($prescriptionItemId)
            ->where('item_id', $item->id)
            ->whereHas('prescription', fn (Builder $query): Builder => $query->where('status', 'verified'))
            ->first();

        if (! $prescriptionItem) {
            throw ValidationException::withMessages(['prescription' => 'Resep tidak ditemukan atau belum terverifikasi.']);
        }

        return $prescriptionItem;
    }
}
