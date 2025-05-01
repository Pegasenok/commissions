<?php

namespace App\Commission\Calculator;

trait UtilsCalculatorTrait
{
    const int INTERMEDIARY_DECIMALS = 4;

    public static function formatToString(float $value): string
    {
        return number_format($value, self::INTERMEDIARY_DECIMALS, thousands_separator: '');
    }

    public static function formatToFloat(string $value): float
    {
        return (float) $value;
    }
}
