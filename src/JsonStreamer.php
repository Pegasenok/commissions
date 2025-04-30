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
     * @return \Generator<array{
     *     bin: string,
     *     amount: string,
     *     currency: string
     * }>
     */
    public function iterate(): \Generator
    {
        while ($line = $this->parser->readline()) {
            yield $line;
        }
    }
}
