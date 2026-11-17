<?php

namespace App\Modules\Core\Services;

use InvalidArgumentException;

class QuantityFormatter
{
    private const SCALE = 6;

    public function format(mixed $quantity): string
    {
        $normalized = $this->normalize($quantity);

        if ($normalized === null) {
            return '—';
        }

        $isNegative = str_starts_with($normalized, '-');
        $absoluteQuantity = ltrim($normalized, '-');
        [$whole, $fraction] = explode('.', $absoluteQuantity, 2);
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = rtrim($fraction, '0');
        $formattedWhole = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole);
        $formattedFraction = $fraction === '' ? '' : ','.$fraction;
        $sign = $isNegative && ($whole !== '0' || $fraction !== '') ? '-' : '';

        return $sign.$formattedWhole.$formattedFraction;
    }

    public function formatInput(mixed $quantity): string
    {
        $normalized = $this->normalize($quantity);

        if ($normalized === null) {
            return '';
        }

        [$whole, $fraction] = explode('.', ltrim($normalized, '-'), 2);
        $fraction = rtrim($fraction, '0');
        $sign = str_starts_with($normalized, '-') ? '-' : '';

        return $sign.$whole.($fraction === '' ? '' : '.'.$fraction);
    }

    private function normalize(mixed $quantity): ?string
    {
        if ($quantity === null || $quantity === '') {
            return null;
        }

        $value = is_float($quantity) ? sprintf('%.6F', $quantity) : (string) $quantity;

        if (preg_match('/^-?\d+(?:\.\d{1,6})?$/D', $value) !== 1) {
            throw new InvalidArgumentException('Quantity values must be numeric with up to six decimal places.');
        }

        $normalized = bcadd($value, '0', self::SCALE);

        return bccomp($normalized, '0', self::SCALE) === 0 ? '0.000000' : $normalized;
    }
}
