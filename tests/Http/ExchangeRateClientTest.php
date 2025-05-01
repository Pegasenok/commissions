<?php

namespace Tests\Http;

use App\Exception\NoExchangeRateException;
use App\Http\ExchangeRateClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ExchangeRateClientTest extends TestCase
{
    private array $container = [];
    private MockHandler $mockHandler;
    private HandlerStack $handlerStack;

    protected function setUp(): void
    {
        $this->container = [];
        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);

        // Add history middleware to track requests
        $history = Middleware::history($this->container);
        $this->handlerStack->push($history);
    }

    public function testConstructorThrowsExceptionWithoutApiKey(): void
    {
        $this->expectException(NoExchangeRateException::class);
        $this->expectExceptionMessage('Exchange rate service api key is required.');

        new ExchangeRateClient(['handler' => $this->handlerStack]);
    }

    public function testGetRatesReturnsDecodedResponse(): void
    {
        // Sample response data
        $responseData = [
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10',
            'rates' => [
                'USD' => 1.18,
                'GBP' => 0.85,
                'JPY' => 130.21
            ]
        ];

        // Queue a response
        $this->mockHandler->append(
            new Response(200, [], json_encode($responseData))
        );

        // Create client with API key
        $client = new ExchangeRateClient([
            'handler' => $this->handlerStack,
            'base_uri' => 'https://api.exchangerate.host/'
        ], 'test_api_key');

        // Get rates
        $result = $client->getRates();

        // Verify the result is decoded correctly
        $this->assertIsObject($result);
        $this->assertTrue($result->success);
        $this->assertEquals('EUR', $result->base);
        $this->assertIsObject($result->rates);
        $this->assertEquals(1.18, $result->rates->USD);
        $this->assertEquals(0.85, $result->rates->GBP);
        $this->assertEquals(130.21, $result->rates->JPY);

        // Verify the request was made correctly
        $this->assertCount(1, $this->container);
        $transaction = $this->container[0];
        $this->assertInstanceOf(Request::class, $transaction['request']);
        $this->assertEquals('GET', $transaction['request']->getMethod());

        // Verify the API key was included in the query
        $uri = $transaction['request']->getUri();
        $this->assertStringContainsString('access_key=test_api_key', $uri->getQuery());
    }

    public function testGetRatesPassesErrorResponseDirectly(): void
    {
        // Sample error response
        $errorResponse = [
            'success' => false,
            'error' => [
                'code' => 101,
                'type' => 'invalid_access_key',
                'info' => 'You have not supplied a valid API Access Key.'
            ]
        ];

        // Queue an error response
        $this->mockHandler->append(
            new Response(401, [], json_encode($errorResponse))
        );

        // Create client with API key
        $client = new ExchangeRateClient([
            'handler' => $this->handlerStack,
            'base_uri' => 'https://api.exchangerate.host/'
        ], 'invalid_api_key');


        $this->expectException(GuzzleException::class);
        $client->getRates();
    }

    public function testGetRatesThrowsExceptionOnNetworkError(): void
    {
        // Create a request exception (network error)
        $request = new Request('GET', 'latest');
        $this->mockHandler->append(
            new RequestException('Network error', $request)
        );

        // Create client with API key
        $client = new ExchangeRateClient([
            'handler' => $this->handlerStack
        ], 'test_api_key');

        // Expect the exception to be passed through
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Network error');

        $client->getRates();
    }

    public function testGetRatesUsesCorrectBaseUri(): void
    {
        // Queue a response
        $this->mockHandler->append(
            new Response(200, [], '{"success":true}')
        );

        // Create client with API key but no base_uri
        $client = new ExchangeRateClient([
            'handler' => $this->handlerStack
        ], 'test_api_key');

        // Get rates
        $client->getRates();

        // Verify the request was made to the default base URI
        $this->assertCount(1, $this->container);
        $transaction = $this->container[0];
        $uri = $transaction['request']->getUri();
        $this->assertEquals('api.exchangeratesapi.io', $uri->getHost());

        // Reset container
        $this->container = [];
        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $history = Middleware::history($this->container);
        $this->handlerStack->push($history);

        // Queue another response
        $this->mockHandler->append(
            new Response(200, [], '{"success":true}')
        );

        // Create client with custom base_uri
        $client = new ExchangeRateClient([
            'handler' => $this->handlerStack,
            'base_uri' => 'https://custom.exchange.api/'
        ], 'test_api_key');

        // Get rates
        $client->getRates();

        // Verify the request was made to the custom base URI
        $this->assertCount(1, $this->container);
        $transaction = $this->container[0];
        $uri = $transaction['request']->getUri();
        $this->assertEquals('custom.exchange.api', $uri->getHost());
    }

    public function testClientExtendsGuzzleClient(): void
    {
        $client = new ExchangeRateClient([
            'handler' => $this->handlerStack
        ], 'test_api_key');

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGetRatesWithMalformedJsonResponse(): void
    {
        // Queue a response with malformed JSON
        $this->mockHandler->append(
            new Response(200, [], '{invalid json')
        );

        // Create client with API key
        $client = new ExchangeRateClient([
            'handler' => $this->handlerStack
        ], 'test_api_key');

        // Expect a JSON decode error
        $this->expectException(\TypeError::class);

        $client->getRates();
    }
}
