<?php

namespace Tests\Services\ExchangeRate;

use App\Exception\NoExchangeRateException;
use App\Exception\ValidationException;
use App\Http\ExchangeRateClient;
use App\Services\ExchangeRate\ExchangeRate;
use App\Validator\ValidatorInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ExchangeRateTest extends TestCase
{
    private ExchangeRateClient|MockObject $mockClient;
    private ValidatorInterface|MockObject $mockValidator;
    private ExchangeRate $exchangeRate;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(ExchangeRateClient::class);
        $this->mockValidator = $this->createMock(ValidatorInterface::class);
        $this->exchangeRate = new ExchangeRate($this->mockClient, $this->mockValidator);
    }

    public function testGetRateThrowsExceptionOnJsonParseError(): void
    {
        // Configure the mock client to throw a JSON exception
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willThrowException(new \TypeError('Syntax error, malformed JSON'));

        // Expect a NoExchangeRateException when JSON parsing fails
        $this->expectException(NoExchangeRateException::class);
        $this->expectExceptionMessage('Exchange rate service unavailable.');

        $this->exchangeRate->getRate('USD');
    }

    public function testGetRateReturnsCorrectRate(): void
    {
        // Create a sample response with rates
        $mockResponse = (object)[
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10',
            'rates' => (object)[
                'USD' => 1.18,
                'GBP' => 0.85,
                'JPY' => 130.21
            ]
        ];

        // Configure the mock client to return our sample response
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willReturn($mockResponse);

        // Configure the mock validator to pass validation
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse);

        // Get the rate for USD
        $rate = $this->exchangeRate->getRate('USD');

        // Assert that the correct rate is returned
        $this->assertEquals(1.18, $rate);
    }

    public function testGetRateThrowsExceptionWhenCurrencyNotFound(): void
    {
        // Create a sample response with rates
        $mockResponse = (object)[
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10',
            'rates' => (object)[
                'USD' => 1.18,
                'GBP' => 0.85,
                'JPY' => 130.21
            ]
        ];

        // Configure the mock client to return our sample response
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willReturn($mockResponse);

        // Configure the mock validator to pass validation
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse);

        // Expect an exception when requesting a non-existent currency
        $this->expectException(NoExchangeRateException::class);
        $this->expectExceptionMessage('Exchange rate for XYZ not found');

        $this->exchangeRate->getRate('XYZ');
    }

    public function testGetRateThrowsExceptionOnValidationFailure(): void
    {
        // Create an invalid response
        $mockResponse = (object)[
            'success' => false,
            'error' => 'Invalid API key'
        ];

        // Configure the mock client to return our invalid response
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willReturn($mockResponse);

        // Configure the mock validator to fail validation
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse)
            ->willThrowException(new ValidationException('Invalid response format'));

        // Expect a NoExchangeRateException when validation fails
        $this->expectException(NoExchangeRateException::class);
        $this->expectExceptionMessage('Invalid response format');

        $this->exchangeRate->getRate('USD');
    }

    public function testGetRateOnlySendsOneRequestForMultipleCalls(): void
    {
        // Create a sample response with rates
        $mockResponse = (object)[
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10',
            'rates' => (object)[
                'USD' => 1.18,
                'GBP' => 0.85,
                'JPY' => 130.21
            ]
        ];

        // Configure the mock client to return our sample response
        // The important part is that it should only be called ONCE
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willReturn($mockResponse);

        // Configure the mock validator to pass validation
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse);

        // Make multiple calls to getRate
        $usdRate = $this->exchangeRate->getRate('USD');
        $gbpRate = $this->exchangeRate->getRate('GBP');
        $jpyRate = $this->exchangeRate->getRate('JPY');

        // Assert that the correct rates are returned
        $this->assertEquals(1.18, $usdRate);
        $this->assertEquals(0.85, $gbpRate);
        $this->assertEquals(130.21, $jpyRate);
    }

    public function testGetRateHandlesNumericRates(): void
    {
        // Create a sample response with numeric rates
        $mockResponse = (object)[
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10',
            'rates' => (object)[
                'USD' => 1.18,
                'GBP' => 0.85,
                'JPY' => 130
            ]
        ];

        // Configure the mock client to return our sample response
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willReturn($mockResponse);

        // Configure the mock validator to pass validation
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse);

        // Get the rate for JPY which is an integer
        $rate = $this->exchangeRate->getRate('JPY');

        // Assert that the correct rate is returned
        $this->assertEquals(130, $rate);
        $this->assertIsInt($rate);
    }

    public function testGetRateHandlesStringRates(): void
    {
        // Create a sample response with string rates
        $mockResponse = (object)[
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10',
            'rates' => (object)[
                'USD' => '1.18',
                'GBP' => '0.85',
                'JPY' => '130.21'
            ]
        ];

        // Configure the mock client to return our sample response
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willReturn($mockResponse);

        // Configure the mock validator to pass validation
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse);

        // Get the rate for USD which is a string
        $rate = $this->exchangeRate->getRate('USD');

        // Assert that the correct rate is returned
        $this->assertEquals('1.18', $rate);
        $this->assertIsString($rate);
    }

    public function testGetRateWithEmptyRatesObject(): void
    {
        // Create a sample response with empty rates
        $mockResponse = (object)[
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10',
            'rates' => (object)[]
        ];

        // Configure the mock client to return our sample response
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willReturn($mockResponse);

        // Configure the mock validator to pass validation
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse);

        // Expect an exception when requesting any currency
        $this->expectException(NoExchangeRateException::class);
        $this->expectExceptionMessage('Exchange rate for USD not found');

        $this->exchangeRate->getRate('USD');
    }

    public function testGetRateThrowsExceptionOnGuzzleTimeout(): void
    {
        // Create a mock request for the ConnectException
        $request = new Request('GET', 'latest');

        // Create a ConnectException that simulates a timeout
        $timeoutException = new ConnectException(
            'cURL error 28: Operation timed out after 5000 milliseconds with 0 bytes received',
            $request
        );

        // Configure the mock client to throw the timeout exception
        $this->mockClient->expects($this->once())
            ->method('getRates')
            ->willThrowException($timeoutException);

        // Expect a NoExchangeRateException when a timeout occurs
        $this->expectException(NoExchangeRateException::class);
        $this->expectExceptionMessage('Exchange rate service unavailable.');

        $this->exchangeRate->getRate('USD');
    }
}
