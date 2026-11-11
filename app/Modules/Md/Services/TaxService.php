<?php

namespace App\Modules\Md\Services;

use App\Modules\Md\Models\TaxRecord;
use App\Modules\Md\Models\TaxRule;
use App\Modules\Md\Models\TaxSetting;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxService
{
    public function rateForDate(DateTimeInterface $date, string $name = 'PPN'): ?string
    {
        return TaxSetting::query()
            ->where('name', $name)
            ->whereDate('effective_from', '<=', $date->format('Y-m-d'))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('rate_percent');
    }

    public function defaultTaxableFor(string $itemType): ?bool
    {
        $rule = TaxRule::query()->where('item_type', $itemType)->first();

        return $rule?->is_taxable;
    }

    /** @param array{name: string, rate_percent: string, effective_from: string} $data */
    public function addRate(array $data): TaxSetting
    {
        return DB::transaction(function () use ($data): TaxSetting {
            $setting = new TaxSetting;
            $setting->fill($data);
            $setting->created_by = Auth::id();
            $setting->updated_by = Auth::id();
            $setting->save();

            return $setting;
        });
    }

    /** @param array{item_type: string, is_taxable: bool|int|string} $data */
    public function saveRule(array $data, ?TaxRule $taxRule = null): TaxRule
    {
        return DB::transaction(function () use ($data, $taxRule): TaxRule {
            $rule = TaxRule::query()
                ->where('item_type', $data['item_type'])
                ->lockForUpdate()
                ->first();

            if ($taxRule?->exists && $taxRule->item_type !== $data['item_type']) {
                throw ValidationException::withMessages([
                    'item_type' => 'Jenis barang pada aturan pajak tidak dapat diubah.',
                ]);
            }

            $rule ??= new TaxRule;
            $rule->fill($data);
            $rule->updated_by = Auth::id();

            if (! $rule->exists) {
                $rule->created_by = Auth::id();
            }

            $rule->save();

            return $rule;
        });
    }

    /** @return array{rate_percent: string, taxable_amount: string, tax_amount: string} */
    public function calculateIncludedTax(string $grossAmount, string $ratePercent): array
    {
        $grossAmount = $this->roundDecimal($grossAmount, 2);
        $denominator = bcadd('100', $ratePercent, 8);
        $taxableAmount = bcdiv(bcmul($grossAmount, '100', 8), $denominator, 8);
        $taxableAmount = $this->roundDecimal($taxableAmount, 2);
        $taxAmount = bcsub($grossAmount, $taxableAmount, 2);

        return [
            'rate_percent' => $ratePercent,
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
        ];
    }

    public function recordInputTax(Model $source, string $grossAmount, bool $isTaxable, DateTimeInterface $transactionDate): TaxRecord
    {
        return $this->record($source, 'input', $grossAmount, $isTaxable, $transactionDate);
    }

    public function recordOutputTax(Model $source, string $grossAmount, bool $isTaxable, DateTimeInterface $transactionDate): TaxRecord
    {
        return $this->record($source, 'output', $grossAmount, $isTaxable, $transactionDate);
    }

    private function record(Model $source, string $direction, string $grossAmount, bool $isTaxable, DateTimeInterface $transactionDate): TaxRecord
    {
        if (! $source->exists) {
            throw ValidationException::withMessages([
                'source' => 'Pajak hanya dapat dicatat untuk transaksi yang sudah tersimpan.',
            ]);
        }

        return DB::transaction(function () use ($source, $direction, $grossAmount, $isTaxable, $transactionDate): TaxRecord {
            $ratePercent = $isTaxable ? $this->rateForDate($transactionDate) : '0.00';

            if ($ratePercent === null) {
                throw ValidationException::withMessages([
                    'tax_setting' => 'Atur tarif PPN yang berlaku sebelum mencatat transaksi kena pajak.',
                ]);
            }

            $tax = $this->calculateIncludedTax($grossAmount, $ratePercent);
            $sourceType = $source->getMorphClass();
            $sourceId = $source->getKey();

            return TaxRecord::query()->firstOrCreate(
                [
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'direction' => $direction,
                ],
                [
                    ...$tax,
                    'transaction_date' => $transactionDate->format('Y-m-d'),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ],
            );
        });
    }

    private function roundDecimal(string $value, int $precision): string
    {
        $halfUnit = '0.'.str_repeat('0', $precision).'5';

        return bcadd($value, $halfUnit, $precision);
    }
}
