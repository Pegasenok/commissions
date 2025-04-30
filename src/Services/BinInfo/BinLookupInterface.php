<?php

namespace App\Services\BinInfo;

interface BinLookupInterface
{
    public function getCountryCodeByBin(string $bin): string;
}
