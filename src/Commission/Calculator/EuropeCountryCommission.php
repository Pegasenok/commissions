<?php

namespace App\Commission\Calculator;

use App\Commission\MoneyAmount;
use App\Services\BinInfo\BinLookupInterface;

class EuropeCountryCommission implements CommissionCalculatorInterface
{
    use UtilsCalculatorTrait;

    private const EU_COMMISSION = 0.01;

    public function __construct(
        protected BinLookupInterface $binInfo,
    ) {
    }

    public function isSuitable(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): bool
    {
        return EuropeCountryCommission::isEu(
            $this->binInfo->getCountryCodeByBin($bin)
        );
    }

    public function getMoneyAmountAdjustCallback(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): callable
    {
        return function (string $amount) use ($moneyAmount, $currency): string {
            return $this->formatToString(
                $this->formatToFloat($amount) * EuropeCountryCommission::EU_COMMISSION
            );
        };
    }

    public static function isEu(string $countryCode): bool
    {
        return match ($countryCode) {
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK' => true,
            default => false,
        };
    }
}
