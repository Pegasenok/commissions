<?php

namespace Tests\Commission\Calculator;

use App\Commission\Calculator\NonEuroCommission;
use App\Commission\MoneyAmount;
use App\Exception\NoExchangeRateException;
use App\Services\ExchangeRate\ExchangeRate;
use App\Services\ExchangeRate\ExchangeRateInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class NonEuroCommissionTest extends TestCase
{
    private ExchangeRate|MockObject $mockExchangeRate;
    private NonEuroCommission $calculator;

    protected function setUp(): void
    {
        $this->mockExchangeRate = $this->createMock(ExchangeRateInterface::class);
        $this->calculator = new NonEuroCommission($this->mockExchangeRate);
    }

    public function testIsSuitableReturnsTrueForNonEuroCurrency(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'USD';

        $this->assertTrue($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }

    public function testIsSuitableReturnsFalseForEuroCurrency(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'EUR';

        $this->assertFalse($this->calculator->isSuitable($moneyAmount, $bin, $currency));
    }

    public function testGetMoneyAmountAdjustCallbackConvertsToEuro(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'USD';

        // Configure the mock to return an exchange rate
        $this->mockExchangeRate->expects($this->exactly(2))
            ->method('getRate')
            ->with($currency)
            ->willReturn(1.25); // 1 EUR = 1.25 USD

        $callback = $this->calculator->getMoneyAmountAdjustCallback($moneyAmount, $bin, $currency);

        $this->assertIsCallable($callback);
        $this->assertEquals('80.0000', $callback('100.00')); // 100 USD / 1.25 = 80 EUR
        $this->assertEquals('40.0000', $callback('50.00')); // 50 USD / 1.25 = 40 EUR
    }

    public function testGetMoneyAmountAdjustCallbackHandlesZeroRate(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'USD';

        // Configure the mock to return a zero rate
        $this->mockExchangeRate->expects($this->once())
            ->method('getRate')
            ->with($currency)
            ->willReturn(0);

        $callback = $this->calculator->getMoneyAmountAdjustCallback($moneyAmount, $bin, $currency);

        $this->assertIsCallable($callback);
        $this->assertEquals('100.00', $callback('100.00')); // Should return original amount when rate is 0
    }

    public function testGetMoneyAmountAdjustCallbackHandlesExchangeRateException(): void
    {
        $moneyAmount = new MoneyAmount('100.00');
        $bin = '45717360';
        $currency = 'XYZ'; // Invalid currency

        // Configure the mock to throw an exception
        $this->mockExchangeRate->expects($this->once())
            ->method('getRate')
            ->with($currency)
            ->willThrowException(new NoExchangeRateException('Exchange rate for XYZ not found'));

        // We expect the calculator to handle the exception and return the original amount
        $callback = $this->calculator->getMoneyAmountAdjustCallback($moneyAmount, $bin, $currency);

        $this->assertIsCallable($callback);
        try {
            $this->assertEquals('100.00', $callback('100.00'));
            $this->fail('Expected NoExchangeRateException was not thrown when currency is invalid.');
        } catch (NoExchangeRateException $e) {
            $this->assertTrue(true);
        }
    }
}
