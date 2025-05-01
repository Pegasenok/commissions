<?php

namespace App\Services\ExchangeRate;

use App\Exception\NoExchangeRateException;

interface ExchangeRateInterface
{
    /**
     * @throws NoExchangeRateException
     */
    public function getRate(string $currency): int|float;
}
