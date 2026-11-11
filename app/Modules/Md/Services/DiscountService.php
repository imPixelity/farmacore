<?php

namespace App\Modules\Md\Services;

use App\Modules\Md\Models\DiscountRule;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiscountService
{
    private const SALES_TYPES = ['resep', 'obat_bebas', 'produk_lain'];

    /**
     * @param  array<int, array{item_id: int, category_id: int|null, quantity: string, unit_price: string}>  $lines
     * @return array{subtotal: string, discount_amount: string, total: string, lines: list<array{item_id: int, category_id: int|null, quantity: string, unit_price: string, subtotal: string, discount_amount: string, total: string, rule_id: int|null}>, applied_rule_ids: list<int>}
     */
    public function apply(array $lines, string $salesType, ?DateTimeInterface $date = null): array
    {
        if (! in_array($salesType, self::SALES_TYPES, true)) {
            throw ValidationException::withMessages(['sales_type' => 'Jenis penjualan tidak valid.']);
        }

        $date ??= now();
        $normalizedLines = $this->normalizeLines($lines);
        $rules = $this->applicableRules($salesType, $date);
        $candidates = array_fill(0, count($normalizedLines), []);
        $subtotals = array_column($normalizedLines, 'subtotal');

        foreach ($rules as $rule) {
            $eligibleIndexes = [];

            foreach ($normalizedLines as $index => $line) {
                if ($this->matchesTarget($rule, $line)) {
                    $eligibleIndexes[] = $index;
                }
            }

            if ($eligibleIndexes === []) {
                continue;
            }

            if ($rule->rule_type === 'qty') {
                foreach ($eligibleIndexes as $index) {
                    if (bccomp($normalizedLines[$index]['quantity'], (string) $rule->min_qty, 3) < 0) {
                        continue;
                    }

                    $discount = $this->discountAmount($rule, $normalizedLines[$index]['subtotal']);
                    $candidates[$index][] = ['amount' => $discount, 'rule_id' => $rule->id];
                }

                continue;
            }

            $eligibleSubtotal = '0.00';

            foreach ($eligibleIndexes as $index) {
                $eligibleSubtotal = bcadd($eligibleSubtotal, $subtotals[$index], 2);
            }

            if (bccomp($eligibleSubtotal, (string) $rule->min_amount, 2) < 0) {
                continue;
            }

            $remainingDiscount = $this->discountAmount($rule, $eligibleSubtotal);
            $remainingSubtotal = $eligibleSubtotal;

            foreach ($eligibleIndexes as $position => $index) {
                $lineSubtotal = $normalizedLines[$index]['subtotal'];
                $isLastLine = $position === array_key_last($eligibleIndexes);
                $discount = $isLastLine
                    ? $remainingDiscount
                    : $this->roundMoney(bcdiv(bcmul($remainingDiscount, $lineSubtotal, 8), $remainingSubtotal, 8));
                $discount = $this->minDecimal($discount, $lineSubtotal);
                $candidates[$index][] = ['amount' => $discount, 'rule_id' => $rule->id];
                $remainingDiscount = bcsub($remainingDiscount, $discount, 2);
                $remainingSubtotal = bcsub($remainingSubtotal, $lineSubtotal, 2);
            }
        }

        $discountTotal = '0.00';
        $appliedRuleIds = [];

        foreach ($normalizedLines as $index => &$line) {
            $bestCandidate = null;

            foreach ($candidates[$index] as $candidate) {
                if ($bestCandidate === null
                    || bccomp($candidate['amount'], $bestCandidate['amount'], 2) > 0
                    || (bccomp($candidate['amount'], $bestCandidate['amount'], 2) === 0 && $candidate['rule_id'] < $bestCandidate['rule_id'])) {
                    $bestCandidate = $candidate;
                }
            }

            $lineDiscount = $bestCandidate['amount'] ?? '0.00';
            $line['discount_amount'] = $lineDiscount;
            $line['total'] = bcsub($line['subtotal'], $lineDiscount, 2);
            $line['rule_id'] = $bestCandidate['rule_id'] ?? null;
            $discountTotal = bcadd($discountTotal, $lineDiscount, 2);

            if ($bestCandidate !== null) {
                $appliedRuleIds[] = $bestCandidate['rule_id'];
            }
        }
        unset($line);

        $subtotal = '0.00';

        foreach ($normalizedLines as $line) {
            $subtotal = bcadd($subtotal, $line['subtotal'], 2);
        }

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountTotal,
            'total' => bcsub($subtotal, $discountTotal, 2),
            'lines' => $normalizedLines,
            'applied_rule_ids' => array_values(array_unique($appliedRuleIds)),
        ];
    }

    /** @param array<string, mixed> $data */
    public function saveRule(array $data, ?DiscountRule $discountRule = null): DiscountRule
    {
        return DB::transaction(function () use ($data, $discountRule): DiscountRule {
            if ($discountRule?->exists) {
                $discountRule = DiscountRule::query()->lockForUpdate()->findOrFail($discountRule->id);
            } else {
                $discountRule = new DiscountRule;
            }

            $discountRule->fill($data);
            $discountRule->updated_by = Auth::id();

            if (! $discountRule->exists) {
                $discountRule->created_by = Auth::id();
            }

            $discountRule->save();

            return $discountRule;
        });
    }

    public function setRuleActive(DiscountRule $discountRule, bool $isActive): DiscountRule
    {
        return DB::transaction(function () use ($discountRule, $isActive): DiscountRule {
            $rule = DiscountRule::query()->lockForUpdate()->findOrFail($discountRule->id);
            $rule->forceFill(['is_active' => $isActive, 'updated_by' => Auth::id()])->save();

            return $rule;
        });
    }

    /** @param array<int, array{item_id: int, category_id: int|null, quantity: string, unit_price: string}> $lines
     * @return list<array{item_id: int, category_id: int|null, quantity: string, unit_price: string, subtotal: string, discount_amount: string, total: string, rule_id: int|null}>
     */
    private function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $index => $line) {
            if (! isset($line['item_id'], $line['quantity'], $line['unit_price'])
                || ! is_numeric($line['quantity'])
                || ! is_numeric($line['unit_price'])
                || bccomp((string) $line['quantity'], '0', 3) <= 0
                || bccomp((string) $line['unit_price'], '0', 2) < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Baris keranjang membutuhkan barang, jumlah positif, dan harga yang valid.',
                ]);
            }

            $quantity = (string) $line['quantity'];
            $unitPrice = $this->roundMoney((string) $line['unit_price']);

            $normalized[] = [
                'item_id' => (int) $line['item_id'],
                'category_id' => isset($line['category_id']) ? (int) $line['category_id'] : null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $this->roundMoney(bcmul($quantity, $unitPrice, 8)),
                'discount_amount' => '0.00',
                'total' => '0.00',
                'rule_id' => null,
            ];
        }

        return $normalized;
    }

    private function applicableRules(string $salesType, DateTimeInterface $date): Collection
    {
        return DiscountRule::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('sales_type')->orWhere('sales_type', $salesType))
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date->format('Y-m-d')))
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date->format('Y-m-d')))
            ->orderBy('id')
            ->get();
    }

    /** @param array{item_id: int, category_id: int|null} $line */
    private function matchesTarget(DiscountRule $rule, array $line): bool
    {
        return ($rule->item_id === null || (int) $rule->item_id === $line['item_id'])
            && ($rule->category_id === null || (int) $rule->category_id === $line['category_id']);
    }

    private function discountAmount(DiscountRule $rule, string $subtotal): string
    {
        if ($rule->discount_type === 'percent') {
            return $this->minDecimal(
                $this->roundMoney(bcmul($subtotal, bcdiv((string) $rule->discount_value, '100', 8), 8)),
                $subtotal,
            );
        }

        return $this->minDecimal((string) $rule->discount_value, $subtotal);
    }

    private function roundMoney(string $amount): string
    {
        return bcadd($amount, '0.005', 2);
    }

    private function minDecimal(string $left, string $right): string
    {
        return bccomp($left, $right, 2) <= 0 ? $left : $right;
    }
}
