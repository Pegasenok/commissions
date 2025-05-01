<?php

namespace App\Http;

use App\Exception\NoExchangeRateException;
use GuzzleHttp\Client;

class ExchangeRateClient extends Client
{
    private const BASE_URI = 'https://api.exchangeratesapi.io/latest';
    private ?string $apiKey;

    public function __construct(array $config = [], ?string $apiKey = null)
    {
        if (empty($apiKey)) {
            throw new NoExchangeRateException('Exchange rate service api key is required.');
        }
        $this->apiKey = $apiKey;
        $defaultConfig = [
            'base_uri' => self::BASE_URI,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 5,
        ];

        parent::__construct(array_merge($defaultConfig, $config));
    }

    /**
     * @return object{
     *     rates: object{
     *         ...: float
     *     },
     * }
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \TypeError
     */
    public function getRates(): object
    {
        $response = $this->get('', [
            'query' => [
                'access_key' => $this->apiKey,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), false);
    }
}
