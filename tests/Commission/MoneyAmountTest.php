<?php

namespace Tests\Commission;

use App\Commission\MoneyAmount;
use PHPUnit\Framework\TestCase;

class MoneyAmountTest extends TestCase
{
    public function testConstructorSetsOriginalAmount(): void
    {
        $amount = new MoneyAmount('100.50');

        $this->assertEquals('100.50', $amount->getOriginalAmount());
        $this->assertEquals('100.50', $amount->getAmount());
        $this->assertEquals('0.0000', $amount->getDelta());
    }

    public function testAddModifierChangesAmount(): void
    {
        $amount = new MoneyAmount('100.00');

        $amount->addModifier(function ($value) {
            return MoneyAmount::formatToString((float)$value * 0.9);
        });

        $this->assertEquals('90.0000', $amount->getAmount());
        $this->assertEquals('100.00', $amount->getOriginalAmount());
        $this->assertEquals('10.0000', $amount->getDelta());
    }

    public function testMultipleModifiersAppliedInOrder(): void
    {
        $amount = new MoneyAmount('100.00');

        $amount->addModifier(function ($value) {
            return MoneyAmount::formatToString((float)$value * 0.9); // 90.0000
        });

        $amount->addModifier(function ($value) {
            return MoneyAmount::formatToString((float)$value - 10); // 80.0000
        });

        $this->assertEquals('80.0000', $amount->getAmount());
        $this->assertEquals('100.00', $amount->getOriginalAmount());
        $this->assertEquals('20.0000', $amount->getDelta());
    }

    public function testFormatToStringFormatsCorrectly(): void
    {
        $this->assertEquals('100.0000', MoneyAmount::formatToString(100));
        $this->assertEquals('100.5000', MoneyAmount::formatToString(100.5));
        $this->assertEquals('100.5670', MoneyAmount::formatToString(100.567));
        $this->assertEquals('0.0000', MoneyAmount::formatToString(0));
    }

    public function testFormatToStringRoundsCorrectly(): void
    {
        $this->assertEquals('100.5678', MoneyAmount::formatToString(100.56781));
        $this->assertEquals('100.5679', MoneyAmount::formatToString(100.56789));
        $this->assertEquals('0.0001', MoneyAmount::formatToString(0.00011));
        $this->assertEquals('0.0000', MoneyAmount::formatToString(0.00001));
    }
}
