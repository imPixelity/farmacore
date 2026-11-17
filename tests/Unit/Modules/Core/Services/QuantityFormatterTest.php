<?php

use App\Modules\Core\Services\QuantityFormatter;
use App\Modules\Reporting\Services\ReportValueFormatter;

it('formats quantities with Indonesian separators and removes insignificant decimals', function (string $value, string $expected): void {
    expect((new QuantityFormatter)->format($value))->toBe($expected);
})->with([
    'whole tablets' => ['60.000', '60'],
    'fractional quantities' => ['1.500', '1,5'],
    'fraction with a trailing zero' => ['1.050', '1,05'],
    'six decimal averages' => ['1.234500', '1,2345'],
    'small six decimal fractions' => ['0.000001', '0,000001'],
    'thousand separator' => ['1000.000', '1.000'],
    'large quantities' => ['999999999999.999', '999.999.999.999,999'],
    'negative differences' => ['-2.000', '-2'],
    'negative zero' => ['-0.000', '0'],
]);

it('uses a dash for missing quantities', function (): void {
    $formatter = new QuantityFormatter;

    expect($formatter->format(null))->toBe('—')
        ->and($formatter->format(''))->toBe('—');
});

it('keeps input quantities in a numeric format without localization', function (mixed $value, string $expected): void {
    expect((new QuantityFormatter)->formatInput($value))->toBe($expected);
})->with([
    'whole quantities' => ['60.000', '60'],
    'fractional quantities' => ['1.500', '1.5'],
    'six decimal fractions remain ungrouped' => ['1.234500', '1.2345'],
    'thousands remain ungrouped' => ['1000.000', '1000'],
    'empty values' => [null, ''],
]);

it('formats report quantities with the shared display format', function (): void {
    $formatter = new ReportValueFormatter(new QuantityFormatter);

    expect($formatter->display('1000.000', 'quantity'))->toBe('1.000');
});
