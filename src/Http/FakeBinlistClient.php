<?php

namespace App\Http;

class FakeBinlistClient extends BinlistClient
{
    public function lookupBin(string $bin): object
    {
        return json_decode(
            <<<JSON
{
    "number": {},
    "scheme": "visa",
    "type": "credit",
    "brand": "Visa Classic",
    "country": {
        "numeric": "392",
        "alpha2": "JP",
        "name": "Japan",
        "emoji": "🇯🇵",
        "currency": "JPY",
        "latitude": 36,
        "longitude": 138
    },
    "bank": {
        "name": "Credit Saison Co., Ltd."
    }
}
JSON,
            false);
    }
}
