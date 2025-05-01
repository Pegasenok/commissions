<?php

namespace App\Commission;

use App\Commission\Calculator\UtilsCalculatorTrait;

class MoneyAmount
{
    use UtilsCalculatorTrait;

    /** @var array<callable (string $originalAmount): string> */
    private $modifiers = [];

    public function __construct(
        private readonly string $originalAmount = '0',
    ) {
    }

    /**
     * @param $modifier callable(string $originalAmount): string
     * @return void
     */
    public function addModifier(callable $modifier): void
    {
        $this->modifiers[] = $modifier;
    }

    public function getOriginalAmount(): string
    {
        return $this->originalAmount;
    }

    public function getAmount(): string
    {
        $amount = $this->originalAmount;
        foreach ($this->modifiers as $modifier) {
            $amount = $modifier($amount);
        }

        return $amount;
    }

    public function getDelta(): string
    {
        return $this->formatToString((float) $this->getOriginalAmount() - (float) $this->getAmount());
    }
}
