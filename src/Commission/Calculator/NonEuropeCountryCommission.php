<?php

namespace App\Commission\Calculator;

use App\Commission\MoneyAmount;

class NonEuropeCountryCommission extends EuropeCountryCommission implements CommissionCalculatorInterface
{
    use UtilsCalculatorTrait;

    private const NON_EU_COMMISSION = 0.02;

    public function isSuitable(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): bool
    {
        return false === EuropeCountryCommission::isEu(
            $this->binInfo->getCountryCodeByBin($bin)
        );
    }

    public function getMoneyAmountAdjustCallback(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): callable
    {
        return function (string $amount) use ($moneyAmount, $currency): string {
            return $this->formatToString(
                $this->formatToFloat($amount) * self::NON_EU_COMMISSION
            );
        };
    }
}
