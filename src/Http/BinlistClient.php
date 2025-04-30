<?php

namespace App\Http;

use GuzzleHttp\Client;

class BinlistClient extends Client
{
    private const BASE_URI = 'https://lookup.binlist.net/';

    public function __construct(array $config = [])
    {
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

    public function lookupBin(string $bin): object
    {
        $response = $this->get($bin);

        return json_decode($response->getBody()->getContents(), false);
    }
}
