<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Md\Models\TaxRecord;
use App\Modules\Pos\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class TaxReportService
{
    /** @return array{from: CarbonImmutable, to: CarbonImmutable, input_base: string, input_tax: string, output_base: string, output_tax: string, tax_difference: string, gross_sales: string, records: Collection<int, TaxRecord>} */
    public function summarize(string $period): array
    {
        $from = CarbonImmutable::createFromFormat('!Y-m', $period)->startOfMonth();
        $to = $from->endOfMonth();
        $records = TaxRecord::query()
            ->with('source')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $inputBase = $this->sum($records->where('direction', 'input')->pluck('taxable_amount')->all());
        $inputTax = $this->sum($records->where('direction', 'input')->pluck('tax_amount')->all());
        $outputBase = $this->sum($records->where('direction', 'output')->pluck('taxable_amount')->all());
        $outputTax = $this->sum($records->where('direction', 'output')->pluck('tax_amount')->all());
        $grossSales = $this->sum(Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$from, $to])
            ->pluck('grand_total')
            ->all());

        return [
            'from' => $from,
            'to' => $to,
            'input_base' => $inputBase,
            'input_tax' => $inputTax,
            'output_base' => $outputBase,
            'output_tax' => $outputTax,
            'tax_difference' => bcsub($outputTax, $inputTax, 2),
            'gross_sales' => $grossSales,
            'records' => $records,
        ];
    }

    /** @param array<int, mixed> $values */
    private function sum(array $values): string
    {
        return array_reduce(
            $values,
            static fn (string $carry, mixed $value): string => bcadd($carry, (string) $value, 2),
            '0.00',
        );
    }
}
