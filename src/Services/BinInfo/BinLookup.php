<?php

namespace App\Services\BinInfo;

use App\Http\BinlistClient;
use App\Validator\ValidatorInterface;

class BinLookup implements BinLookupInterface
{
    private array $cache = [];

    public function __construct(
        private BinlistClient $client,
        private ValidatorInterface $validator
    ) {
    }

    private function getBinlistInfo(string $bin): object
    {
        if (isset($this->cache[$bin])) {
            return $this->cache[$bin];
        }

        $response = $this->client->lookupBin($bin);
        $this->validator->validate($response);
        $this->cache[$bin] = $response;

        return $response;
    }

    public function getCountryCodeByBin(string $bin): string
    {
        return $this->getBinlistInfo($bin)->country->alpha2;
    }
}
