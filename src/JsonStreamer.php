<?php

namespace App;

use App\Parser\ParserInterface;

class JsonStreamer
{
    private ParserInterface $parser;

    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return \Generator<array>
     */
    public function iterate(): \Generator
    {
        while ($line = $this->parser->readline()) {
            yield $line;
        }
    }
}
