<?php

namespace Tests;

use App\Commission\Calculator\CommissionCalculatorInterface;
use App\Commission\MoneyAmount;
use App\CommissionHandler;
use App\Exception\ValidationException;
use App\JsonStreamer;
use App\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CommissionHandlerTest extends TestCase
{
    private CommissionHandler $handler;
    private JsonStreamer|MockObject $mockStreamer;
    private ValidatorInterface|MockObject $mockValidator;

    protected function setUp(): void
    {
        $this->handler = new CommissionHandler();
        $this->mockStreamer = $this->createMock(JsonStreamer::class);
        $this->mockValidator = $this->createMock(ValidatorInterface::class);
        $this->handler->setValidator($this->mockValidator);
    }

    public function testProcessCommissionsWithValidInput(): void
    {
        // Mock data to be returned by the streamer
        $mockData = [
            ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
            ['bin' => '41417360', 'amount' => '200.00', 'currency' => 'USD'],
        ];

        // Set up the streamer to return our mock data
        $this->mockStreamer->expects($this->once())
            ->method('iterate')
            ->willReturn($this->generateMockIterator($mockData));

        // Validator should be called for each line
        $this->mockValidator->expects($this->exactly(2))
            ->method('validate')
            ->willReturnCallback(function ($line) {
                // Just validate that the required fields exist
                if (!isset($line['bin']) || !isset($line['amount']) || !isset($line['currency'])) {
                    throw new ValidationException('Missing required fields');
                }
            });

        // Create a mock calculator that always applies a 10% discount
        $mockCalculator = $this->createMock(CommissionCalculatorInterface::class);
        $mockCalculator->expects($this->exactly(2))
            ->method('isSuitable')
            ->willReturn(true);
        $mockCalculator->expects($this->exactly(2))
            ->method('getMoneyAmountAdjustCallback')
            ->willReturnCallback(function (MoneyAmount $amount, $bin, $currency) {
                return function (string $value) {
                    return MoneyAmount::formatToString((float)$value * 0.9);
                };
            });

        $this->handler->addCommissionCalculator($mockCalculator);

        // Process the commissions
        $results = $this->handler->processCommissions($this->mockStreamer);

        // Check the results
        $this->assertCount(2, $results);
        $this->assertEquals('90.0000', $results[0]);
        $this->assertEquals('180.0000', $results[1]);
        $this->assertEmpty($this->handler->getErrors());
    }

    public function testProcessCommissionsWithValidationError(): void
    {
        // Mock data with one valid and one invalid line
        $mockData = [
            ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
            ['bin' => '41417360', 'amount' => '-200.00', 'currency' => 'USD'], // Invalid amount
        ];

        // Set up the streamer to return our mock data
        $this->mockStreamer->expects($this->once())
            ->method('iterate')
            ->willReturn($this->generateMockIterator($mockData));

        // Validator should throw an exception for the second line
        $this->mockValidator->expects($this->exactly(2))
            ->method('validate')
            ->willReturnCallback(function ($line) {
                if ($line['amount'] < 0) {
                    throw new ValidationException('Amount cannot be negative');
                }
            });

        // Create a mock calculator that always applies a 10% discount
        $mockCalculator = $this->createMock(CommissionCalculatorInterface::class);
        $mockCalculator->expects($this->once())
            ->method('isSuitable')
            ->willReturn(true);
        $mockCalculator->expects($this->once())
            ->method('getMoneyAmountAdjustCallback')
            ->willReturnCallback(function (MoneyAmount $amount, $bin, $currency) {
                return function (string $value) {
                    return MoneyAmount::formatToString((float)$value * 0.9);
                };
            });

        $this->handler->addCommissionCalculator($mockCalculator);

        // Process the commissions
        $results = $this->handler->processCommissions($this->mockStreamer);

        // Check the results
        $this->assertCount(1, $results);
        $this->assertEquals('90.0000', $results[0]);

        // Check that an error was recorded
        $errors = $this->handler->getErrors();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('broken_line:', $errors[0]);
        $this->assertStringContainsString('Amount cannot be negative', $errors[0]);
    }

    public function testProcessCommissionsWithBrokenInput(): void
    {
        // Mock data with one valid and one broken line
        $mockData = [
            ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
            ['broken' => 'data'], // Missing required fields
        ];

        // Set up the streamer to return our mock data
        $this->mockStreamer->expects($this->once())
            ->method('iterate')
            ->willReturn($this->generateMockIterator($mockData));

        // Validator should throw an exception for the second line
        $this->mockValidator->expects($this->exactly(2))
            ->method('validate')
            ->willReturnCallback(function ($line) {
                if (!isset($line['bin']) || !isset($line['amount']) || !isset($line['currency'])) {
                    throw new ValidationException('Missing required fields');
                }
            });

        // Create a mock calculator that always applies a 10% discount
        $mockCalculator = $this->createMock(CommissionCalculatorInterface::class);
        $mockCalculator->expects($this->once())
            ->method('isSuitable')
            ->willReturn(true);
        $mockCalculator->expects($this->once())
            ->method('getMoneyAmountAdjustCallback')
            ->willReturnCallback(function (MoneyAmount $amount, $bin, $currency) {
                return function (string $value) {
                    return MoneyAmount::formatToString((float)$value * 0.9);
                };
            });

        $this->handler->addCommissionCalculator($mockCalculator);

        // Process the commissions
        $results = $this->handler->processCommissions($this->mockStreamer);

        // Check the results
        $this->assertCount(1, $results);
        $this->assertEquals('90.0000', $results[0]);

        // Check that an error was recorded
        $errors = $this->handler->getErrors();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('broken_line', $errors[0]);
        $this->assertStringContainsString('Missing required fields', $errors[0]);
    }

    public function testMultipleCalculatorsAppliedInOrder(): void
    {
        // Mock data
        $mockData = [
            ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
        ];

        // Set up the streamer to return our mock data
        $this->mockStreamer->expects($this->once())
            ->method('iterate')
            ->willReturn($this->generateMockIterator($mockData));

        // Validator should pass
        $this->mockValidator->expects($this->once())
            ->method('validate');

        // First calculator applies 10% discount
        $calculator1 = $this->createMock(CommissionCalculatorInterface::class);
        $calculator1->expects($this->once())
            ->method('isSuitable')
            ->willReturn(true);
        $calculator1->expects($this->once())
            ->method('getMoneyAmountAdjustCallback')
            ->willReturnCallback(function (MoneyAmount $amount, $bin, $currency) {
                return function (string $value) {
                    return MoneyAmount::formatToString((float)$value * 0.9); // 90.0000
                };
            });

        // Second calculator subtracts 20
        $calculator2 = $this->createMock(CommissionCalculatorInterface::class);
        $calculator2->expects($this->once())
            ->method('isSuitable')
            ->willReturn(true);
        $calculator2->expects($this->once())
            ->method('getMoneyAmountAdjustCallback')
            ->willReturnCallback(function (MoneyAmount $amount, $bin, $currency) {
                return function (string $value) {
                    return MoneyAmount::formatToString((float)$value - 20); // 70.0000
                };
            });

        $this->handler->addCommissionCalculator($calculator1);
        $this->handler->addCommissionCalculator($calculator2);

        // Process the commissions
        $results = $this->handler->processCommissions($this->mockStreamer);

        // Check the results - both calculators should be applied in order
        $this->assertCount(1, $results);
        $this->assertEquals('70.0000', $results[0]);
    }

    public function testCalculatorOnlyAppliedWhenSuitable(): void
    {
        // Mock data
        $mockData = [
            ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
        ];

        // Set up the streamer to return our mock data
        $this->mockStreamer->expects($this->once())
            ->method('iterate')
            ->willReturn($this->generateMockIterator($mockData));

        // Validator should pass
        $this->mockValidator->expects($this->once())
            ->method('validate');

        // First calculator is not suitable
        $calculator1 = $this->createMock(CommissionCalculatorInterface::class);
        $calculator1->expects($this->once())
            ->method('isSuitable')
            ->willReturn(false);
        $calculator1->expects($this->never())
            ->method('getMoneyAmountAdjustCallback');

        // Second calculator is suitable and applies 20% discount
        $calculator2 = $this->createMock(CommissionCalculatorInterface::class);
        $calculator2->expects($this->once())
            ->method('isSuitable')
            ->willReturn(true);
        $calculator2->expects($this->once())
            ->method('getMoneyAmountAdjustCallback')
            ->willReturnCallback(function (MoneyAmount $amount, $bin, $currency) {
                return function (string $value) {
                    return MoneyAmount::formatToString((float)$value * 0.8);
                };
            });

        $this->handler->addCommissionCalculator($calculator1);
        $this->handler->addCommissionCalculator($calculator2);

        // Process the commissions
        $results = $this->handler->processCommissions($this->mockStreamer);

        // Check the results - only the second calculator should be applied
        $this->assertCount(1, $results);
        $this->assertEquals('80.0000', $results[0]);
    }

    /**
     * Helper method to generate a mock iterator from an array
     */
    private function generateMockIterator(array $data): \Generator
    {
        foreach ($data as $item) {
            yield $item;
        }
    }
}
