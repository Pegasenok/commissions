<?php

namespace Tests\Commission\Calculator;

use App\Commission\Calculator\EuropeCountryCommission;
use App\Commission\MoneyAmount;
use App\Services\BinInfo\BinLookupInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EuropeCountryCommissionTest extends TestCase
{
    private BinLookupInterface|MockObject $mockBinLookup;
    private EuropeCountryCommission $calculator;
    
    protected function setUp(): void
    {
        $this->mockBinLookup = $this->createMock(BinLookupInterface::class);
        $this->calculator = new EuropeCountryCommission($this->mockBinLookup);
    }
    
    public function testIsSuitableReturnsTrueForEuCountry(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'EUR';
        
        // Configure the mock to return an EU country code
        $this->mockBinLookup->expects($this->once())
            ->method('getCountryCodeByBin')
            ->with($bin)
            ->willReturn('DE'); // Germany is in the EU
        
        $this->assertTrue($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }
    
    public function testIsSuitableReturnsFalseForNonEuCountry(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'USD';
        
        // Configure the mock to return a non-EU country code
        $this->mockBinLookup->expects($this->once())
            ->method('getCountryCodeByBin')
            ->with($bin)
            ->willReturn('US'); // US is not in the EU
        
        $this->assertFalse($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }
    
    public function testGetMoneyAmountAdjustCallbackAppliesEuCommission(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'EUR';
        
        $callback = $this->calculator->getMoneyAmountAdjustCallback($moneyAmount, $bin, $currency);
        
        $this->assertIsCallable($callback);
        $this->assertEquals('1.0000', $callback('100.00')); // 1% of 100.00
        $this->assertEquals('0.5000', $callback('50.00')); // 1% of 50.00
    }
    
    public function testIsEuReturnsTrueForEuCountries(): void
    {
        $euCountries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'];
        
        foreach ($euCountries as $countryCode) {
            $this->assertTrue(EuropeCountryCommission::isEu($countryCode), "Country $countryCode should be recognized as EU");
        }
    }
    
    public function testIsEuReturnsFalseForNonEuCountries(): void
    {
        $nonEuCountries = ['US', 'CA', 'JP', 'AU', 'CH', 'NO', 'UK', 'RU', 'CN', 'BR'];
        
        foreach ($nonEuCountries as $countryCode) {
            $this->assertFalse(EuropeCountryCommission::isEu($countryCode), "Country $countryCode should not be recognized as EU");
        }
    }
}