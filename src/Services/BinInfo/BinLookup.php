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

    public function getCountryCodeByBin(string $bin): string
    {
        return $this->getBinlistInfo($bin)->country->alpha2;
    }

    /**
     * @return object{
     *     country: object{
     *         alpha2: string
     *     },
     *     ...
     * }
     */
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
}
