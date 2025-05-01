<?php

namespace Tests;

use App\JsonStreamer;
use App\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;

class JsonStreamerTest extends TestCase
{
    public function testIterateYieldsLinesFromParser(): void
    {
        // Create mock parser
        $mockParser = $this->createMock(ParserInterface::class);
        
        // Sample data
        $lines = [
            ['bin' => '45717360', 'amount' => '100.00', 'currency' => 'EUR'],
            ['bin' => '41417360', 'amount' => '200.00', 'currency' => 'USD'],
            null // End of file
        ];

        // Configure mock to return sample data
        $mockParser->method('readline')
            ->willReturnOnConsecutiveCalls(...$lines);

        // Create streamer with mock parser
        $streamer = new JsonStreamer();
        $streamer->setParser($mockParser);

        // Collect results
        $results = [];
        foreach ($streamer->iterate() as $line) {
            $results[] = $line;
        }

        // Verify results
        $this->assertCount(2, $results);
        $this->assertEquals($lines[0], $results[0]);
        $this->assertEquals($lines[1], $results[1]);
    }

    public function testIterateHandlesEmptyFile(): void
    {
        // Create mock parser that returns null (empty file)
        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('readline')->willReturn(null);

        // Create streamer with mock parser
        $streamer = new JsonStreamer();
        $streamer->setParser($mockParser);

        // Verify no results are yielded
        $results = iterator_to_array($streamer->iterate());
        $this->assertEmpty($results);
    }

    public function testIterateHandlesParserExceptions(): void
    {
        // Create mock parser that throws exception
        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('readline')
            ->willThrowException(new \RuntimeException('Parser error'));

        // Create streamer with mock parser
        $streamer = new JsonStreamer();
        $streamer->setParser($mockParser);

        // Expect exception
        $this->expectException(\RuntimeException::class);
        
        // Try to iterate (should throw)
        iterator_to_array($streamer->iterate());
    }
}