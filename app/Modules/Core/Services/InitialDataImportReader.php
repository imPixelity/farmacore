<?php

namespace App\Modules\Core\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class InitialDataImportReader
{
    /** @var array<string, array<int, string>> */
    public const HEADINGS = [
        'items' => [
            'code', 'item_type', 'name', 'category_name', 'category_type', 'base_unit',
            'order_unit', 'factor_to_base', 'barcode', 'is_taxable', 'dosage_form', 'strength_volume',
        ],
        'suppliers' => [
            'name', 'address', 'phone', 'email', 'contact_person', 'payment_terms',
            'payment_term_days', 'lead_time_days',
        ],
        'stock' => ['item_code', 'batch_no', 'expiry_date', 'qty_base', 'unit_cost_base'],
    ];

    private const SHEET_TYPES = [
        'barang' => 'items',
        'supplier' => 'suppliers',
        'stok awal' => 'stock',
    ];

    /**
     * @return array{rows: array<string, array<int, array{row: int, data: array<string, mixed>}>>, errors: array<int, string>}
     */
    public function read(string $path, ?string $csvType): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            if (! in_array($csvType, array_keys(self::HEADINGS), true)) {
                return ['rows' => [], 'errors' => ['Jenis template CSV tidak dikenali.']];
            }

            return $this->readCsv($path, $csvType);
        }

        if ($extension !== 'xlsx') {
            return ['rows' => [], 'errors' => ['Format berkas harus CSV atau XLSX.']];
        }

        return $this->readWorkbook($path);
    }

    /**
     * @return array{rows: array<string, array<int, array{row: int, data: array<string, mixed>}>>, errors: array<int, string>}
     */
    private function readCsv(string $path, string $type): array
    {
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            return ['rows' => [], 'errors' => ['Berkas CSV tidak dapat dibaca.']];
        }

        try {
            $headings = fgetcsv($stream, escape: '');
            if (! is_array($headings)) {
                return ['rows' => [], 'errors' => ['Template CSV tidak memiliki baris judul kolom.']];
            }

            $parsed = $this->parseRows([$type => [$headings, ...$this->csvRows($stream)]], [$type => 1]);
        } finally {
            fclose($stream);
        }

        return $parsed;
    }

    /** @param resource $stream
     * @return array<int, array<int, mixed>>
     */
    private function csvRows($stream): array
    {
        $rows = [];

        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array{rows: array<string, array<int, array{row: int, data: array<string, mixed>}>>, errors: array<int, string>}
     */
    private function readWorkbook(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $workbook = $reader->load($path);
            $sheets = [];
            $startRows = [];

            foreach ($workbook->getWorksheetIterator() as $worksheet) {
                $sheetName = mb_strtolower(trim($worksheet->getTitle()));
                $type = self::SHEET_TYPES[$sheetName] ?? null;

                if ($type === null) {
                    return ['rows' => [], 'errors' => ['Nama sheet tidak dikenal: '.$worksheet->getTitle().'.']];
                }

                if (array_key_exists($type, $sheets)) {
                    return ['rows' => [], 'errors' => ['Sheet '.$worksheet->getTitle().' muncul lebih dari sekali.']];
                }

                $values = $worksheet->toArray(null, false, false, false);
                $sheets[$type] = $values;
                $startRows[$type] = 1;
            }

            return $this->parseRows($sheets, $startRows);
        } catch (Throwable) {
            return ['rows' => [], 'errors' => ['Berkas XLSX tidak dapat dibaca. Pastikan berkas berasal dari template FarmaCore.']];
        }
    }

    /**
     * @param  array<string, array<int, array<int|string, mixed>>>  $sheets
     * @param  array<string, int>  $startRows
     * @return array{rows: array<string, array<int, array{row: int, data: array<string, mixed>}>>, errors: array<int, string>}
     */
    private function parseRows(array $sheets, array $startRows): array
    {
        $parsedRows = ['items' => [], 'suppliers' => [], 'stock' => []];
        $errors = [];

        foreach ($sheets as $type => $values) {
            if (! array_key_exists($type, self::HEADINGS)) {
                $errors[] = 'Jenis data impor tidak dikenali.';

                continue;
            }

            $headerRow = $values[0] ?? [];
            $headings = array_map(fn (mixed $heading): string => $this->normalizeHeading($heading), $headerRow);
            $expectedHeadings = self::HEADINGS[$type];
            $missingHeadings = array_diff($expectedHeadings, $headings);
            $unknownHeadings = array_diff($headings, $expectedHeadings);

            if ($headings === [] || in_array('', $headings, true)) {
                $errors[] = 'Sheet/template '.strtoupper($type).' tidak memiliki judul kolom yang valid.';

                continue;
            }

            if ($missingHeadings !== []) {
                $errors[] = 'Kolom wajib pada template '.strtoupper($type).' belum lengkap: '.implode(', ', $missingHeadings).'.';
            }

            if ($unknownHeadings !== []) {
                $errors[] = 'Kolom tidak dikenal pada template '.strtoupper($type).': '.implode(', ', array_filter($unknownHeadings)).'.';
            }

            if (count($headings) !== count(array_unique($headings))) {
                $errors[] = 'Template '.strtoupper($type).' memiliki judul kolom ganda.';
            }

            if ($missingHeadings !== [] || $unknownHeadings !== [] || count($headings) !== count(array_unique($headings))) {
                continue;
            }

            foreach (array_slice($values, 1) as $offset => $valuesByColumn) {
                if ($this->isBlankRow($valuesByColumn)) {
                    continue;
                }

                $data = [];
                foreach ($headings as $column => $heading) {
                    $data[$heading] = $valuesByColumn[$column] ?? null;
                }

                $parsedRows[$type][] = [
                    'row' => $offset + 2,
                    'data' => $data,
                ];

                if (count($parsedRows[$type]) > 5000) {
                    $errors[] = 'Maksimal 5.000 baris data diperbolehkan per sheet.';

                    break;
                }
            }
        }

        if (array_sum(array_map('count', $parsedRows)) === 0 && $errors === []) {
            $errors[] = 'Berkas tidak berisi baris data untuk diimpor.';
        }

        return ['rows' => $parsedRows, 'errors' => $errors];
    }

    private function normalizeHeading(mixed $heading): string
    {
        if (! is_string($heading) && ! is_numeric($heading)) {
            return '';
        }

        $value = trim((string) $heading);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        return mb_strtolower(str_replace([' ', '-'], '_', $value));
    }

    /** @param array<int|string, mixed> $row */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
