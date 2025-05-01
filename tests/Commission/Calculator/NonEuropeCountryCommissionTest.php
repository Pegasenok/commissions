<?php

namespace Tests\Commission\Calculator;

use App\Commission\Calculator\NonEuropeCountryCommission;
use App\Commission\MoneyAmount;
use App\Services\BinInfo\BinLookupInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class NonEuropeCountryCommissionTest extends TestCase
{
    private BinLookupInterface|MockObject $mockBinLookup;
    private NonEuropeCountryCommission $calculator;
    
    protected function setUp(): void
    {
        $this->mockBinLookup = $this->createMock(BinLookupInterface::class);
        $this->calculator = new NonEuropeCountryCommission($this->mockBinLookup);
    }
    
    public function testIsSuitableReturnsTrueForNonEuCountry(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'USD';
        
        // Configure the mock to return a non-EU country code
        $this->mockBinLookup->expects($this->once())
            ->method('getCountryCodeByBin')
            ->with($bin)
            ->willReturn('US'); // US is not in the EU
        
        $this->assertTrue($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }
    
    public function testIsSuitableReturnsFalseForEuCountry(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'EUR';
        
        // Configure the mock to return an EU country code
        $this->mockBinLookup->expects($this->once())
            ->method('getCountryCodeByBin')
            ->with($bin)
            ->willReturn('DE'); // Germany is in the EU
        
        $this->assertFalse($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }
    
    public function testGetMoneyAmountAdjustCallbackAppliesNonEuCommission(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'USD';
        
        $callback = $this->calculator->getMoneyAmountAdjustCallback($moneyAmount, $bin, $currency);
        
        $this->assertIsCallable($callback);
        $this->assertEquals('2.0000', $callback('100.00')); // 2% of 100.00
        $this->assertEquals('1.0000', $callback('50.00')); // 2% of 50.00
    }
    
    public function testInheritsFromEuropeCountryCommission(): void
    {
        $this->assertInstanceOf(\App\Commission\Calculator\EuropeCountryCommission::class, $this->calculator);
    }
}