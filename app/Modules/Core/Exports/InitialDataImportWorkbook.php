<?php

namespace App\Modules\Core\Exports;

use App\Modules\Core\Services\InitialDataImportReader;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InitialDataImportWorkbook implements Export, WithMultipleSheets
{
    /** @return array<int, InitialDataImportSheet> */
    public function sheets(): array
    {
        return [
            new InitialDataImportSheet('Barang', InitialDataImportReader::HEADINGS['items']),
            new InitialDataImportSheet('Supplier', InitialDataImportReader::HEADINGS['suppliers']),
            new InitialDataImportSheet('Stok Awal', InitialDataImportReader::HEADINGS['stock']),
        ];
    }
}
