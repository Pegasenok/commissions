<?php

namespace Tests\Validator;

use App\Exception\ValidationException;
use App\Validator\SchemaValidator;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class SchemaValidatorTest extends TestCase
{
    private string $schemaPath;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        // Create a temporary schema file
        $this->schemaPath = sys_get_temp_dir() . '/test_schema_' . uniqid() . '.json';
        $this->tempFiles[] = $this->schemaPath;

        $schema = [
            'type' => 'object',
            'properties' => [
                'bin' => ['type' => 'string', 'minLength' => 6],
                'amount' => ['type' => 'string', 'pattern' => '^[0-9]+(\.[0-9]{1,2})?$'],
                'currency' => ['type' => 'string', 'enum' => ['EUR', 'USD', 'GBP']]
            ],
            'required' => ['bin', 'amount', 'currency'],
            'additionalProperties' => false
        ];

        file_put_contents($this->schemaPath, json_encode($schema));
    }

    protected function tearDown(): void
    {
        // Clean up all temporary files
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testValidateWithValidObject(): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        $validObject = new \stdClass();
        $validObject->bin = '123456';
        $validObject->amount = '100.00';
        $validObject->currency = 'EUR';

        // This should not throw an exception
        $validator->validate($validObject);

        $this->assertTrue(true);
    }

    public function testValidateWithMissingRequiredFieldInObject(): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        $invalidObject = new \stdClass();
        $invalidObject->bin = '123456';
        $invalidObject->amount = '100.00';
        // Missing currency field

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/currency/');

        $validator->validate($invalidObject);
    }

    public function testValidateWithInvalidFieldTypeInObject(): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        $invalidObject = new \stdClass();
        $invalidObject->bin = '123456';
        $invalidObject->amount = 100.00; // Should be a string
        $invalidObject->currency = 'EUR';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/amount/');

        $validator->validate($invalidObject);
    }

    public function testValidateWithInvalidFieldValueInObject(): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        $invalidObject = new \stdClass();
        $invalidObject->bin = '123456';
        $invalidObject->amount = '100.00';
        $invalidObject->currency = 'JPY'; // Not in enum

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/currency/');

        $validator->validate($invalidObject);
    }

    public function testValidateWithAdditionalPropertyInObject(): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        $invalidObject = new \stdClass();
        $invalidObject->bin = '123456';
        $invalidObject->amount = '100.00';
        $invalidObject->currency = 'EUR';
        $invalidObject->extra = 'field'; // Not allowed

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/extra/');

        $validator->validate($invalidObject);
    }

    public function testValidateWithInvalidPatternInObject(): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        $invalidObject = new \stdClass();
        $invalidObject->bin = '123456';
        $invalidObject->amount = '100.000'; // Too many decimal places
        $invalidObject->currency = 'EUR';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/amount/');

        $validator->validate($invalidObject);
    }

    public function testConstructorThrowsExceptionWithInvalidSchema(): void
    {
        $invalidSchemaPath = sys_get_temp_dir() . '/invalid_schema_' . uniqid() . '.json';
        $this->tempFiles[] = $invalidSchemaPath;

        // Create an invalid JSON file
        file_put_contents($invalidSchemaPath, '{invalid json');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Schema not found');

        new SchemaValidator(new Validator(), $invalidSchemaPath);
    }

    public function testConstructorThrowsExceptionWithNonexistentSchema(): void
    {
        $nonexistentPath = '/path/to/nonexistent/schema.json';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Schema not found');

        new SchemaValidator(new Validator(), $nonexistentPath);
    }

    public function testErrorMessageContainsPropertyAndMessageForObject(): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        $invalidObject = new \stdClass();
        $invalidObject->bin = '12345'; // Too short
        $invalidObject->amount = '100.00';
        $invalidObject->currency = 'EUR';

        try {
            $validator->validate($invalidObject);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('bin', $e->getMessage());
            $this->assertStringContainsString('Must be at least 6 characters long', $e->getMessage());
        }
    }

    #[TestWith([
        ["bin" => "123456", "amount" => "100.00", "currency" => "EUR"],
        ["bin" => "654321", "amount" => "50.00", "currency" => "USD"],
        ["bin" => "987654", "amount" => "75.50", "currency" => "GBP"]
    ])] public function testValidateWithDataProvider(array $validData): void
    {
        $validator = new SchemaValidator(new Validator(), $this->schemaPath);

        // This should not throw an exception
        $validator->validate((object) $validData);

        $this->assertTrue(true);
    }
}
