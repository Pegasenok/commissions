<?php

namespace Tests\Http;

use App\Http\CachedBinlistClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CachedBinlistClientTest extends TestCase
{
    private string $cacheDir = 'var/tests/binlist_cache';
    private string $testBin = '45717360';
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = $this->cacheDir . '/' . $this->testBin . '.json';
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testLookupBinCreatesCache(): void
    {
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
        $client = new CachedBinlistClient(['handler' => $handlerStack, 'cacheDir' => $this->cacheDir]);

        $result = $client->lookupBin($this->testBin);

        $this->assertEquals($mockData, $result);
        $this->assertFileExists($this->cacheFile);

        $cachedContent = json_decode(file_get_contents($this->cacheFile));
        $this->assertEquals($mockData, $cachedContent);
    }

    public function testLookupBinUsesCacheWhenAvailable(): void
    {
        // Create cache file
        $mockData = (object)[
            'scheme' => 'visa',
            'country' => (object)[
                'alpha2' => 'US',
                'name' => 'United States'
            ]
        ];

        file_put_contents($this->cacheFile, json_encode($mockData));

        // Create client with mock that would return different data
        // This mock should never be called if cache is working
        $mockHandler = new MockHandler([
            new Response(200, [], '{"different":"data"}')
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $client = new CachedBinlistClient(['handler' => $handlerStack, 'cacheDir' => $this->cacheDir]);

        $result = $client->lookupBin($this->testBin);

        $this->assertEquals($mockData, $result);
    }

    public function testLookupBinHitsApiWhenCacheExpired(): void
    {
        // Create expired cache file
        $expiredData = (object)[
            'scheme' => 'visa',
            'country' => (object)[
                'alpha2' => 'UK',
                'name' => 'United Kingdom'
            ]
        ];

        file_put_contents($this->cacheFile, json_encode($expiredData));

        // Set file modification time to past the TTL
        touch($this->cacheFile, time() - 86500); // Older than 24 hours

        // New data that should be returned and cached
        $newData = (object)[
            'scheme' => 'visa',
            'country' => (object)[
                'alpha2' => 'US',
                'name' => 'United States'
            ]
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($newData))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $client = new CachedBinlistClient(['handler' => $handlerStack, 'cacheDir' => $this->cacheDir]);

        $result = $client->lookupBin($this->testBin);

        // Should return new data, not expired data
        $this->assertEquals($newData, $result);

        // Cache should be updated with new data
        $cachedContent = json_decode(file_get_contents($this->cacheFile));
        $this->assertEquals($newData, $cachedContent);
    }

    public function testLookupBinUsesStaleDataWhenApiErrors(): void
    {
        // Create expired cache file
        $staleData = (object)[
            'scheme' => 'visa',
            'country' => (object)[
                'alpha2' => 'UK',
                'name' => 'United Kingdom'
            ]
        ];

        file_put_contents($this->cacheFile, json_encode($staleData));

        // Set file modification time to past the TTL
        touch($this->cacheFile, time() - 86500); // Older than 24 hours

        // Mock an API error
        $mockHandler = new MockHandler([
            new RequestException('Error communicating with server', new Request('GET', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $client = new CachedBinlistClient(['handler' => $handlerStack, 'cacheDir' => $this->cacheDir]);

        $result = $client->lookupBin($this->testBin);

        // Should fall back to stale data
        $this->assertEquals($staleData, $result);
    }
}
