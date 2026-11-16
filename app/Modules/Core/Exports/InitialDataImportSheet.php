<?php

namespace App\Modules\Core\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class InitialDataImportSheet implements FromArray, WithTitle
{
    /** @param array<int, string> $headings */
    public function __construct(
        private string $sheetTitle,
        private array $headings,
    ) {}

    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        return [$this->headings];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
