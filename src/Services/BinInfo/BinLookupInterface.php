<?php

namespace App\Services\BinInfo;

use App\Exception\CommissionFailureInterface;

interface BinLookupInterface
{
    /**
     * @throws CommissionFailureInterface
     */
    public function getCountryCodeByBin(string $bin): string;
}
