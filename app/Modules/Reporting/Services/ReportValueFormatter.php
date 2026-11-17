<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Core\Services\QuantityFormatter;
use Carbon\CarbonImmutable;

class ReportValueFormatter
{
    public function __construct(private QuantityFormatter $quantityFormatter) {}

    public function display(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($format) {
            'money' => 'Rp '.number_format((float) $value, 2, ',', '.'),
            'quantity' => $this->quantityFormatter->format($value),
            'decimal' => number_format((float) $value, 1, ',', '.'),
            'integer' => number_format((float) $value, 0, ',', '.'),
            'percent' => number_format((float) $value, 2, ',', '.').'%',
            'date' => CarbonImmutable::parse($value)->format('d-m-Y'),
            'datetime' => CarbonImmutable::parse($value)->format('d-m-Y H:i'),
            'direction' => $value === 'in' ? 'Masuk' : 'Keluar',
            default => str_replace('_', ' ', (string) $value),
        };
    }

    public function spreadsheetValue(mixed $value, string $format): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($format) {
            'money', 'quantity', 'decimal', 'percent' => (float) $value,
            'integer' => (int) $value,
            default => $this->display($value, $format),
        };
    }
}
