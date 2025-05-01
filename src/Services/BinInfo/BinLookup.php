<?php

namespace App\Services\BinInfo;

use App\Exception\NoBinInfoException;
use App\Exception\ValidationException;
use App\Http\BinlistClient;
use App\Validator\ValidatorInterface;
use GuzzleHttp\Exception\GuzzleException;

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
        try {
            return $this->getBinlistInfo($bin)->country->alpha2;
        } catch (GuzzleException $exception) {
            throw new NoBinInfoException($exception->getMessage());
        }
    }

    /**
     * @return object{
     *     country: object{
     *         alpha2: string
     *     },
     *     ...
     * }
     * @throws GuzzleException|ValidationException
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
