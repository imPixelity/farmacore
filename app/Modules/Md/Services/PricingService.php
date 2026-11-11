<?php

namespace App\Modules\Md\Services;

use App\Modules\Core\Services\SettingsService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Md\Models\MarginSetting;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PricingService
{
    private const SALES_TYPES = ['resep', 'obat_bebas', 'produk_lain'];

    public function __construct(
        private TaxService $taxService,
        private SettingsService $settingsService,
    ) {}

    /** @return array{purchase_price_incl_tax: string, selling_price: string} */
    public function calculate(
        string $hnaBase,
        bool $isTaxable,
        string $taxRate,
        string $marginPercent,
        string $roundingStep,
    ): array {
        if (bccomp($hnaBase, '0', 4) < 0 || bccomp($marginPercent, '0', 2) < 0 || bccomp($roundingStep, '0', 4) <= 0) {
            throw ValidationException::withMessages([
                'pricing' => 'HNA dan margin tidak boleh negatif, serta langkah pembulatan harus lebih dari nol.',
            ]);
        }

        $taxFactor = $isTaxable
            ? bcdiv(bcadd('100', $taxRate, 8), '100', 10)
            : '1';
        $purchasePrice = $this->roundDecimal(bcmul($hnaBase, $taxFactor, 10), 4);
        $marginFactor = bcdiv(bcadd('100', $marginPercent, 8), '100', 10);
        $unroundedSellingPrice = bcmul($purchasePrice, $marginFactor, 10);
        $roundingUnits = bcdiv($unroundedSellingPrice, $roundingStep, 10);
        $roundedUnits = bcadd($roundingUnits, '0.5', 0);
        $sellingPrice = $this->roundDecimal(bcmul($roundedUnits, $roundingStep, 10), 2);

        return [
            'purchase_price_incl_tax' => $purchasePrice,
            'selling_price' => $sellingPrice,
        ];
    }

    /** @return Collection<int, ItemPrice> */
    public function recalculateItem(Item $item, ?DateTimeInterface $effectiveFrom = null): Collection
    {
        $effectiveFrom ??= now();

        return DB::transaction(function () use ($item, $effectiveFrom): Collection {
            $lockedItem = Item::query()->lockForUpdate()->findOrFail($item->id);

            if (bccomp($lockedItem->hna_base, '0', 4) <= 0) {
                return new Collection;
            }

            $prices = new Collection;
            $roundingStep = (string) ($this->settingsService->get('price_rounding_step', 1) ?? 1);

            foreach (self::SALES_TYPES as $salesType) {
                $margin = $this->resolveMargin($lockedItem, $salesType);

                if (! $margin) {
                    continue;
                }

                $taxRate = $lockedItem->is_taxable
                    ? $this->taxService->rateForDate($effectiveFrom)
                    : '0.00';

                if ($taxRate === null) {
                    continue;
                }

                $calculation = $this->calculate(
                    (string) $lockedItem->hna_base,
                    $lockedItem->is_taxable,
                    $taxRate,
                    (string) $margin->margin_percent,
                    $roundingStep,
                );

                $currentPrice = ItemPrice::query()
                    ->where('item_id', $lockedItem->id)
                    ->where('sales_type', $salesType)
                    ->where('is_current', true)
                    ->lockForUpdate()
                    ->first();

                if ($currentPrice && $this->hasSameCalculation($currentPrice, $calculation, $margin)) {
                    $prices->push($currentPrice);

                    continue;
                }

                if ($currentPrice) {
                    $currentPrice->forceFill([
                        'is_current' => false,
                        'updated_by' => Auth::id(),
                    ])->save();
                }

                $price = new ItemPrice;
                $price->fill([
                    'item_id' => $lockedItem->id,
                    'sales_type' => $salesType,
                    'purchase_price_incl_tax' => $calculation['purchase_price_incl_tax'],
                    'margin_percent' => $margin->margin_percent,
                    'selling_price' => $calculation['selling_price'],
                    'effective_from' => $effectiveFrom,
                    'is_current' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
                $price->save();
                $prices->push($price);
            }

            return $prices;
        });
    }

    public function currentPrice(Item $item, string $salesType, ?DateTimeInterface $effectiveFrom = null): ?ItemPrice
    {
        if (! in_array($salesType, self::SALES_TYPES, true)) {
            throw ValidationException::withMessages(['sales_type' => 'Jenis penjualan tidak valid.']);
        }

        $this->recalculateItem($item, $effectiveFrom);

        return ItemPrice::query()
            ->where('item_id', $item->id)
            ->where('sales_type', $salesType)
            ->where('is_current', true)
            ->first();
    }

    /** @param array{scope_type: string, scope_id?: int|string|null, sales_type: string, margin_percent: string} $data */
    public function saveMarginSetting(array $data): MarginSetting
    {
        return DB::transaction(function () use ($data): MarginSetting {
            $scopeId = $data['scope_type'] === 'global' ? null : (int) $data['scope_id'];
            $scopeKey = match ($data['scope_type']) {
                'global' => 'global',
                'category' => 'category-'.$scopeId,
                'item' => 'item-'.$scopeId,
            };

            if ($data['scope_type'] === 'item') {
                Item::query()->whereKey($scopeId)->lockForUpdate()->firstOrFail();
            } elseif ($data['scope_type'] === 'category') {
                ItemCategory::query()->whereKey($scopeId)->lockForUpdate()->firstOrFail();
            }

            $setting = MarginSetting::query()
                ->where('scope_key', $scopeKey)
                ->where('sales_type', $data['sales_type'])
                ->lockForUpdate()
                ->first() ?? new MarginSetting;
            $setting->fill([
                'scope_key' => $scopeKey,
                'scope_type' => $data['scope_type'],
                'item_id' => $data['scope_type'] === 'item' ? $scopeId : null,
                'category_id' => $data['scope_type'] === 'category' ? $scopeId : null,
                'sales_type' => $data['sales_type'],
                'margin_percent' => $data['margin_percent'],
            ]);
            $setting->updated_by = Auth::id();

            if (! $setting->exists) {
                $setting->created_by = Auth::id();
            }

            $setting->save();
            $this->recalculateItemsForScope($data['scope_type'], $scopeId);

            return $setting;
        });
    }

    public function recalculateTaxableItems(?DateTimeInterface $effectiveFrom = null): void
    {
        $this->recalculateItems(Item::query()->where('is_taxable', true), $effectiveFrom);
    }

    private function resolveMargin(Item $item, string $salesType): ?MarginSetting
    {
        return MarginSetting::query()
            ->where('item_id', $item->id)
            ->where('sales_type', $salesType)
            ->first()
            ?? MarginSetting::query()
                ->whereNull('item_id')
                ->where('category_id', $item->category_id)
                ->where('sales_type', $salesType)
                ->first()
            ?? MarginSetting::query()
                ->where('scope_type', 'global')
                ->where('sales_type', $salesType)
                ->first();
    }

    private function hasSameCalculation(ItemPrice $currentPrice, array $calculation, MarginSetting $margin): bool
    {
        return bccomp((string) $currentPrice->purchase_price_incl_tax, $calculation['purchase_price_incl_tax'], 4) === 0
            && bccomp((string) $currentPrice->margin_percent, (string) $margin->margin_percent, 2) === 0
            && bccomp((string) $currentPrice->selling_price, $calculation['selling_price'], 2) === 0;
    }

    private function recalculateItemsForScope(string $scopeType, ?int $scopeId): void
    {
        $items = Item::query()->where('hna_base', '>', 0);

        if ($scopeType === 'item') {
            $items->whereKey($scopeId);
        } elseif ($scopeType === 'category') {
            $items->where('category_id', $scopeId);
        }

        $this->recalculateItems($items);
    }

    /** @param Builder<Item> $items */
    private function recalculateItems(Builder $items, ?DateTimeInterface $effectiveFrom = null): void
    {
        $items->orderBy('id')->chunkById(100, function (Collection $items) use ($effectiveFrom): void {
            foreach ($items as $item) {
                $this->recalculateItem($item, $effectiveFrom);
            }
        });
    }

    private function roundDecimal(string $value, int $precision): string
    {
        $halfUnit = '0.'.str_repeat('0', $precision).'5';

        return bcadd($value, $halfUnit, $precision);
    }
}
