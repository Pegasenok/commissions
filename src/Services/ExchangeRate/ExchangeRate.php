<?php

namespace App\Services\ExchangeRate;

use App\Exception\NoExchangeRateException;
use App\Exception\ValidationException;
use App\Http\ExchangeRateClient;
use App\Validator\ValidatorInterface;

class ExchangeRate
{
    private $ratesResponse = [];

    public function __construct(
        private ExchangeRateClient $client,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws NoExchangeRateException
     */
    public function getRate(string $currency)
    {
        $this->initRates();
        if (!isset($this->ratesResponse->rates->{$currency})) {
            throw new NoExchangeRateException("Exchange rate for {$currency} not found");
        }

        return $this->ratesResponse->rates->{$currency};
    }

    private function initRates(): void
    {
        if (empty($this->ratesResponse)) {
            $this->ratesResponse = $this->client->getRates();
            try {
                $this->validator->validate($this->ratesResponse);
            } catch (ValidationException $exception) {
                throw new NoExchangeRateException($exception->getMessage());
            }
        }
    }
}
