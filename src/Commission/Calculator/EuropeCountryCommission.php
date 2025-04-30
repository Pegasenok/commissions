<?php

namespace App\Commission\Calculator;

use App\Commission\MoneyAmount;
use App\Services\BinInfo\BinLookupInterface;

class EuropeCountryCommission implements CommissionCalculatorInterface
{
    private const EU_COMMISSION = 0.01;

    public function __construct(
        protected BinLookupInterface $binInfo,
    ) {
    }

    public function isSuitable(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): bool
    {
        $isEu = EuropeCountryCommission::isEu(
            $this->binInfo->getCountryCodeByBin($bin)
        );
        return $isEu;
    }

    public function getMoneyAmountAdjustCallback(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): callable
    {
        return function (string $amount) use ($moneyAmount, $currency) {
            return number_format((float) $amount * EuropeCountryCommission::EU_COMMISSION, 4);
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
