<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Reporting\Services\ReportValueFormatter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportSpreadsheetExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithTitle
{
    /**
     * @param  array{title: string, requirement: string, columns: array<int, array{key: string, label: string, format: string}>}  $definition
     * @param  array<int, array{label: string, value: string|int|float|null, format: string}>  $summary
     * @param  Collection<int, array<string, mixed>|object>|array<int, array<string, mixed>|object>  $rows
     */
    public function __construct(
        private array $definition,
        private array $summary,
        private Collection|array $rows,
        private string $applicationName,
        private string $period,
        private string $printedAt,
        private string $createdBy,
        private ReportValueFormatter $formatter,
    ) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        $data = [
            ['Nama apotek', $this->applicationName],
            ['Laporan', $this->definition['title']],
            ['Requirement', $this->definition['requirement']],
            ['Periode', $this->period],
            ['Waktu cetak', $this->printedAt],
            ['Dibuat oleh', $this->createdBy],
            [],
            ['Ringkasan'],
        ];

        foreach ($this->summary as $card) {
            $data[] = [$card['label'], $this->formatter->spreadsheetValue($card['value'], $card['format'])];
        }

        $data[] = [];
        $data[] = array_column($this->definition['columns'], 'label');

        foreach ($this->rows as $row) {
            $data[] = array_map(
                fn (array $column): mixed => $this->formatter->spreadsheetValue(
                    data_get($row, $column['key']),
                    $column['format'],
                ),
                $this->definition['columns'],
            );
        }

        return $data;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function title(): string
    {
        return 'Laporan';
    }
}
