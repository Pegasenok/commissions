<?php

namespace Tests\Commission\Calculator;

use App\Commission\Calculator\BaseEuroCommission;
use App\Commission\MoneyAmount;
use PHPUnit\Framework\TestCase;

class BaseEuroCommissionTest extends TestCase
{
    private BaseEuroCommission $calculator;
    
    protected function setUp(): void
    {
        $this->calculator = new BaseEuroCommission();
    }
    
    public function testIsSuitableReturnsTrueForEuroCurrency(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'EUR';
        
        $this->assertTrue($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }
    
    public function testIsSuitableReturnsFalseForNonEuroCurrency(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'USD';
        
        $this->assertFalse($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }
    
    public function testGetMoneyAmountAdjustCallbackReturnsOriginalAmount(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'EUR';
        
        $callback = $this->calculator->getMoneyAmountAdjustCallback($moneyAmount, $bin, $currency);
        
        $this->assertIsCallable($callback);
        $this->assertEquals('100.00', $callback('100.00'));
        $this->assertEquals('100.00', $callback('50.00')); // Should always return original amount
    }
}