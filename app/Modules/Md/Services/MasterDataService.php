<?php

namespace App\Modules\Md\Services;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Unit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    /** @param array<string, mixed> $data */
    public function saveCategory(array $data, ?ItemCategory $category = null): ItemCategory
    {
        return DB::transaction(function () use ($data, $category): ItemCategory {
            if ($category?->exists) {
                $category = ItemCategory::query()->lockForUpdate()->findOrFail($category->id);
            } else {
                $category = new ItemCategory;
            }

            if ($category->exists && $category->type !== $data['type'] && $category->items()->exists()) {
                throw ValidationException::withMessages([
                    'type' => 'Tipe kategori tidak dapat diubah selama masih digunakan barang.',
                ]);
            }

            $category->fill(Arr::only($data, ['name', 'type', 'is_active']));
            $category->updated_by = Auth::id();

            if (! $category->exists) {
                $category->created_by = Auth::id();
            }

            $category->save();

            return $category;
        });
    }

    public function setCategoryActive(ItemCategory $category, bool $isActive): ItemCategory
    {
        return DB::transaction(function () use ($category, $isActive): ItemCategory {
            $category = ItemCategory::query()->lockForUpdate()->findOrFail($category->id);

            if (! $isActive && $category->items()->exists()) {
                throw ValidationException::withMessages([
                    'is_active' => 'Kategori yang masih digunakan barang tidak dapat dinonaktifkan.',
                ]);
            }

            $category->forceFill(['is_active' => $isActive, 'updated_by' => Auth::id()])->save();

            return $category;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveUnit(array $data, ?Unit $unit = null): Unit
    {
        return DB::transaction(function () use ($data, $unit): Unit {
            if ($unit?->exists) {
                $unit = Unit::query()->lockForUpdate()->findOrFail($unit->id);
            } else {
                $unit = new Unit;
            }

            $unit->fill(Arr::only($data, ['name', 'is_active']));
            $unit->updated_by = Auth::id();

            if (! $unit->exists) {
                $unit->created_by = Auth::id();
            }

            $unit->save();

            return $unit;
        });
    }

    public function setUnitActive(Unit $unit, bool $isActive): Unit
    {
        return DB::transaction(function () use ($unit, $isActive): Unit {
            $unit = Unit::query()->lockForUpdate()->findOrFail($unit->id);

            if (! $isActive && ($unit->baseItems()->exists() || $unit->orderItems()->exists() || $unit->itemUnits()->exists())) {
                throw ValidationException::withMessages([
                    'is_active' => 'Satuan yang masih digunakan barang atau konversi tidak dapat dinonaktifkan.',
                ]);
            }

            $unit->forceFill(['is_active' => $isActive, 'updated_by' => Auth::id()])->save();

            return $unit;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveItem(array $data, ?Item $item = null): Item
    {
        return DB::transaction(function () use ($data, $item): Item {
            $category = ItemCategory::query()->lockForUpdate()->find($data['category_id']);
            $expectedCategoryType = $data['item_type'] === 'obat' ? 'obat' : 'non_obat';

            if (! $category || ! $category->is_active || $category->type !== $expectedCategoryType) {
                throw ValidationException::withMessages([
                    'category_id' => 'Kategori harus aktif dan sesuai dengan tipe barang.',
                ]);
            }

            $conversions = array_filter(
                $data['conversions'],
                fn (mixed $factor): bool => $factor !== null && $factor !== '',
            );
            $baseUnitId = (int) $data['base_unit_id'];
            $orderUnitId = (int) $data['order_unit_id'];
            $conversions[$baseUnitId] = 1;

            foreach ($conversions as $unitId => $factor) {
                if (! is_numeric($factor) || (float) $factor <= 0 || ! preg_match('/^\\d+(?:\\.\\d{1,3})?$/', (string) $factor)) {
                    throw ValidationException::withMessages([
                        "conversions.{$unitId}" => 'Faktor konversi harus lebih besar dari nol dan maksimal tiga angka desimal.',
                    ]);
                }
            }

            $unitIds = array_values(array_unique([
                $baseUnitId,
                $orderUnitId,
                ...array_map('intval', array_keys($conversions)),
            ]));
            $activeUnitIds = Unit::query()
                ->whereIn('id', $unitIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn (int|string $id): int => (int) $id)
                ->all();

            if (count($activeUnitIds) !== count($unitIds)) {
                throw ValidationException::withMessages([
                    'conversions' => 'Semua satuan dan konversi harus merujuk pada satuan yang aktif.',
                ]);
            }

            if (! array_key_exists($orderUnitId, $conversions)) {
                throw ValidationException::withMessages([
                    'conversions' => 'Satuan pemesanan harus memiliki faktor konversi.',
                ]);
            }

            if ($item?->exists) {
                $item = Item::query()->lockForUpdate()->findOrFail($item->id);
            } else {
                $item = new Item;
            }

            $item->fill(Arr::only($data, [
                'code', 'item_type', 'name', 'dosage_form', 'strength_volume', 'category_id',
                'base_unit_id', 'order_unit_id', 'barcode', 'is_taxable', 'is_active',
            ]));
            $item->updated_by = Auth::id();

            if (! $item->exists) {
                $item->created_by = Auth::id();
            }

            $item->save();

            $existingConversions = $item->itemUnits()->get()->keyBy('unit_id');

            foreach ($conversions as $unitId => $factor) {
                $unitId = (int) $unitId;
                $conversion = $existingConversions->get($unitId) ?? new ItemUnit;
                $conversion->fill([
                    'item_id' => $item->id,
                    'unit_id' => $unitId,
                    'factor_to_base' => $unitId === $baseUnitId ? 1 : $factor,
                    'created_by' => $conversion->exists ? $conversion->created_by : Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
                $conversion->save();
                $existingConversions->forget($unitId);
            }

            foreach ($existingConversions as $conversion) {
                $conversion->delete();
            }

            return $item->load(['category', 'baseUnit', 'orderUnit', 'itemUnits.unit']);
        });
    }

    public function setItemActive(Item $item, bool $isActive): Item
    {
        return DB::transaction(function () use ($item, $isActive): Item {
            $item = Item::query()->lockForUpdate()->findOrFail($item->id);
            $item->forceFill(['is_active' => $isActive, 'updated_by' => Auth::id()])->save();

            return $item;
        });
    }
}
