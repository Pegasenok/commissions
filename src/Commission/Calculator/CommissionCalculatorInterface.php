<?php

namespace App\Commission\Calculator;

use App\Commission\MoneyAmount;
use App\Exception\CommissionFailureInterface;

interface CommissionCalculatorInterface
{
    /**
     * @throws CommissionFailureInterface
     */
    public function isSuitable(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): bool;

    /**
     * @return callable(string $amount): string
     * @throws CommissionFailureInterface
     */
    public function getMoneyAmountAdjustCallback(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): callable;
}
