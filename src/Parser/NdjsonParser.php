<?php

namespace App\Parser;

use RuntimeException;
use Sunaoka\Ndjson\NDJSON;

class NdjsonParser implements ParserInterface
{
    /**
     * @var object|NDJSON
     */
    private object $parser;

    /**
     * @param  object|NDJSON  $parser
     */
    public function __construct(object $parser)
    {
        if (!method_exists($parser, 'readline')) {
            throw new \Exception('Parser does not have readline method');
        }

        $this->parser = $parser;
    }

    public function readline(): array|null
    {
        try {
            return $this->parser->readline();
        } catch (RuntimeException $exception) {
            // when ndjson file does not have empty line at the end
            return null;
        }
    }
}
