<?php

namespace Tests\Parser;

use App\Parser\NdjsonParser;
use PHPUnit\Framework\TestCase;
use Sunaoka\Ndjson\NDJSON;

class NdjsonParserTest extends TestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        // Create a temporary test file with NDJSON content
        $this->testFilePath = sys_get_temp_dir() . '/test_ndjson_' . uniqid() . '.ndjson';

        $testData = [
            '{"bin":"45717360","amount":"100.00","currency":"EUR"}',
            '{"bin":"41417360","amount":"200.00","currency":"USD"}',
            '{"bin":"4745030","amount":"300.00","currency":"GBP"}'
        ];

        file_put_contents($this->testFilePath, implode("\n", $testData) . "\n");
    }

    protected function tearDown(): void
    {
        // Clean up the test file
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function testReadlineReturnsExpectedData(): void
    {
        $ndjson = new NDJSON($this->testFilePath);
        $parser = new NdjsonParser($ndjson);

        // First line
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('45717360', $line['bin']);
        $this->assertEquals('100.00', $line['amount']);
        $this->assertEquals('EUR', $line['currency']);

        // Second line
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('41417360', $line['bin']);
        $this->assertEquals('200.00', $line['amount']);
        $this->assertEquals('USD', $line['currency']);

        // Third line
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('4745030', $line['bin']);
        $this->assertEquals('300.00', $line['amount']);
        $this->assertEquals('GBP', $line['currency']);

        // End of file
        $line = $parser->readline();
        $this->assertNull($line);
    }

    public function testFileWithoutEmptyLineAtEnd(): void
    {
        // Create a file without an empty line at the end
        $filePath = sys_get_temp_dir() . '/no_empty_line_' . uniqid() . '.ndjson';

        $testData = [
            '{"bin":"45717360","amount":"100.00","currency":"EUR"}',
            '{"bin":"41417360","amount":"200.00","currency":"USD"}'
        ];

        // Write data without adding an extra newline at the end
        file_put_contents($filePath, implode("\n", $testData));

        $ndjson = new NDJSON($filePath);
        $parser = new NdjsonParser($ndjson);

        // First line
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('45717360', $line['bin']);

        // Second line
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('41417360', $line['bin']);

        // End of file - should return null even without an empty line at the end
        $line = $parser->readline();
        $this->assertNull($line);

        unlink($filePath);
    }

    public function testConstructorThrowsExceptionForInvalidParser(): void
    {
        $invalidParser = new \stdClass(); // Object without readline method

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Parser does not have readline method');

        new NdjsonParser($invalidParser);
    }

    public function testParserWithCustomObject(): void
    {
        // Create a custom object that implements readline
        $customParser = new class {
            private $data = [
                ['bin' => '12345678', 'amount' => '50.00', 'currency' => 'JPY'],
                ['bin' => '87654321', 'amount' => '75.00', 'currency' => 'CAD'],
            ];
            private $index = 0;

            public function readline(): ?array
            {
                if ($this->index >= count($this->data)) {
                    return null;
                }

                return $this->data[$this->index++];
            }
        };

        $parser = new NdjsonParser($customParser);

        // First line
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('12345678', $line['bin']);
        $this->assertEquals('50.00', $line['amount']);
        $this->assertEquals('JPY', $line['currency']);

        // Second line
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('87654321', $line['bin']);
        $this->assertEquals('75.00', $line['amount']);
        $this->assertEquals('CAD', $line['currency']);

        // End of data
        $line = $parser->readline();
        $this->assertNull($line);
    }

    public function testEmptyFile(): void
    {
        $emptyFilePath = sys_get_temp_dir() . '/empty_ndjson_' . uniqid() . '.ndjson';
        file_put_contents($emptyFilePath, '');

        $ndjson = new NDJSON($emptyFilePath);
        $parser = new NdjsonParser($ndjson);

        $line = $parser->readline();
        $this->assertNull($line);

        unlink($emptyFilePath);
    }

    public function testMalformedJson(): void
    {
        $malformedFilePath = sys_get_temp_dir() . '/malformed_ndjson_' . uniqid() . '.ndjson';
        file_put_contents($malformedFilePath, '{"bin":"45717360","amount":"100.00","currency":"EUR"}' . "\n" . '{"malformed":json}');

        $ndjson = new NDJSON($malformedFilePath);
        $parser = new NdjsonParser($ndjson);

        // First line should be valid
        $line = $parser->readline();
        $this->assertIsArray($line);
        $this->assertEquals('45717360', $line['bin']);

        // Second line should cause an error in the NDJSON library
        $line = $parser->readline();
        $this->assertNull($line);

        unlink($malformedFilePath);
    }
}
