<?php

namespace App\Parser;

use Sunaoka\Ndjson\NDJSON;

class ParserFactory
{
    public static function getParser(string $path): ParserInterface
    {
        return new NdjsonParser(new NDJSON($path));
    }
}
