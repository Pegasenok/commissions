<?php

namespace Tests\Http;

use App\Http\BinlistClient;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class BinlistClientTest extends TestCase
{
    public function testLookupBinReturnsDecodedResponse(): void
    {
        // Create mock response
        $mockData = (object)[
            'scheme' => 'visa',
            'country' => (object)[
                'alpha2' => 'US',
                'name' => 'United States'
            ]
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockData))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $client = new BinlistClient(['handler' => $handlerStack]);

        $result = $client->lookupBin('45717360');

        $this->assertEquals($mockData, $result);
    }

    public function testLookupBinThrowsExceptionOnError(): void
    {
        $mockHandler = new MockHandler([
            new Response(404, [], '{"error": "Not found"}')
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $client = new BinlistClient(['handler' => $handlerStack]);

        $this->expectException(GuzzleException::class);
        $client->lookupBin('invalid');
    }
}
