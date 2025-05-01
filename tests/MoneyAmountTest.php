<?php

namespace Tests;

use App\Commission\MoneyAmount;
use PHPUnit\Framework\TestCase;

class MoneyAmountTest extends TestCase
{
    public function testMoneyAmountCanBeModified()
    {
        $amount = new MoneyAmount('1234.55');
        $amount->addModifier(function ($amount) {
            return MoneyAmount::formatToString((float) $amount * 0.01);
        });

        $this->assertEquals('12.3455', $amount->getAmount());
        $this->assertEquals('1234.55', $amount->getOriginalAmount());
        $this->assertEquals('1222.2045', $amount->getDelta());
    }
}
