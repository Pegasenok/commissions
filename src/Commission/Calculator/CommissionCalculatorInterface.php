<?php

namespace App\Commission\Calculator;

use App\Commission\MoneyAmount;

interface CommissionCalculatorInterface
{
    public function isSuitable(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): bool;

    public function getMoneyAmountAdjustCallback(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): callable;
}
