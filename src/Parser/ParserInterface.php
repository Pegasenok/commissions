<?php

namespace App\Parser;

interface ParserInterface
{
    public function readline(): array|null;
}
