<?php

namespace App\Services\ExchangeRate;

use App\Exception\NoExchangeRateException;
use App\Exception\ValidationException;
use App\Http\ExchangeRateClient;
use App\Validator\ValidatorInterface;
use GuzzleHttp\Exception\GuzzleException;
use TypeError;

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
        try {
            $this->initRates();
        } catch (GuzzleException|TypeError) {
            throw new NoExchangeRateException("Exchange rate service unavailable.");
        }
        if (!isset($this->ratesResponse->rates->{$currency})) {
            throw new NoExchangeRateException("Exchange rate for {$currency} not found");
        }

        return $this->ratesResponse->rates->{$currency};
    }

    /**
     * @throws GuzzleException
     * @throws TypeError
     * @throws NoExchangeRateException
     */
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
