<?php

namespace App\Commission\Calculator;

use App\Commission\MoneyAmount;

class BaseEuroCommission implements CommissionCalculatorInterface
{
    const string EUR = 'EUR';

    public function isSuitable(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): bool
    {
        if ($currency === self::EUR) {
            return true;
        }
        return false;
    }

    public function getMoneyAmountAdjustCallback(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): callable
    {
        return function(string $amount) use ($moneyAmount) {
            return $moneyAmount->getOriginalAmount();
        };
    }
}
