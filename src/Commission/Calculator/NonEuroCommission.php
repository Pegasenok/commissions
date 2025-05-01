<?php

namespace App\Commission\Calculator;

use App\Commission\MoneyAmount;
use App\Exception\NoExchangeRateException;
use App\Services\ExchangeRate\ExchangeRate;

class NonEuroCommission implements CommissionCalculatorInterface
{
    use UtilsCalculatorTrait;

    public function __construct(private ExchangeRate $exchangeRate)
    {
    }

    public function isSuitable(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): bool
    {
        if ($currency !== 'EUR') {
            return true;
        }
        return false;
    }

    public function getMoneyAmountAdjustCallback(MoneyAmount $moneyAmount, mixed $bin, mixed $currency): callable
    {
        return function (string $amount) use ($moneyAmount, $currency): string {
            $rate = $this->exchangeRate->getRate($currency);
            if ($rate === 0) {
                return $amount;
            }
            return $this->formatToString(
                $this->formatToFloat($amount) / $rate
            );
        };
    }
}
