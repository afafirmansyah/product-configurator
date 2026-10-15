<?php

namespace App\Support;

class Money
{
    /**
     * Convert a monetary amount expressed in major units (e.g. "12.34")
     * into integer minor units (cents), rounding half-up at two decimals.
     */
    public static function toCents(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round(((float) $amount) * 100.0, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Convert integer minor units (cents) back into a normalized
     * two-decimal major-unit string (e.g. 1234 => "12.34").
     */
    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100.0, 2, '.', '');
    }

    /**
     * Sum any number of integer minor-unit amounts.
     */
    public static function addCents(int ...$cents): int
    {
        return array_sum($cents);
    }

    /**
     * Multiply an integer minor-unit amount by a (possibly fractional)
     * quantity, rounding the result half-up to whole cents.
     */
    public static function mulCents(int $cents, string|int|float $qty): int
    {
        return (int) round($cents * (float) $qty, 0, PHP_ROUND_HALF_UP);
    }
}
