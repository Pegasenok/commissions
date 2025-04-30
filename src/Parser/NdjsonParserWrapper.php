<?php

namespace App\Parser;

use Sunaoka\Ndjson\NDJSON;

class NdjsonParserWrapper implements ParserInterface
{
    /** @var object|NDJSON */
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
        return $this->parser->readline();
    }
}
