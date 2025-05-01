<?php

namespace Tests\Validator;

use App\Validator\SchemaValidator;
use App\Validator\ValidatorFactory;
use App\Validator\ValidatorInterface;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorFactoryTest extends TestCase
{
    public function testGetCommissionLineValidator(): void
    {
        $validator = ValidatorFactory::getCommissionLineValidator();

        $this->assertInstanceOf(ValidatorInterface::class, $validator);
        $this->assertInstanceOf(SchemaValidator::class, $validator);

        // Test that the validator works with valid data
        $validData = [
            'bin' => '45717360',
            'amount' => '100.00',
            'currency' => 'EUR'
        ];

        // This should not throw an exception
        $validator->validate((object) $validData);
        $this->assertTrue(true);
    }

    public function testGetBinInfoValidator(): void
    {
        $validator = ValidatorFactory::getBinInfoValidator();

        $this->assertInstanceOf(ValidatorInterface::class, $validator);
        $this->assertInstanceOf(SchemaValidator::class, $validator);

        // Create a sample valid bin info response
        $validData = (object)[
            'number' => (object)[
                'length' => 16,
                'luhn' => true
            ],
            'scheme' => 'visa',
            'type' => 'debit',
            'brand' => 'Visa Classic',
            'country' => (object)[
                'numeric' => '840',
                'alpha2' => 'US',
                'name' => 'United States of America',
                'currency' => 'USD',
                'emoji' => '🇺🇸',
                'latitude' => 37.0902,
                'longitude' => -95.7129
            ],
            'bank' => (object)[
                'name' => 'JPMorgan Chase',
                'url' => 'https://www.jpmorganchase.com',
                'phone' => '+1-212-270-6000'
            ]
        ];

        // This should not throw an exception
        $validator->validate($validData);
        $this->assertTrue(true);
    }

    public function testGetExchangeRateValidator(): void
    {
        $validator = ValidatorFactory::getExchangeRateValidator();

        $this->assertInstanceOf(ValidatorInterface::class, $validator);
        $this->assertInstanceOf(SchemaValidator::class, $validator);

        // Create a sample valid exchange rate response
        $validData = (object)[
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

        // This should not throw an exception
        $validator->validate($validData);
        $this->assertTrue(true);
    }

    public function testValidatorsUseCorrectSchemaFiles(): void
    {
        // Use reflection to check the schema paths
        $commissionValidator = ValidatorFactory::getCommissionLineValidator();
        $binInfoValidator = ValidatorFactory::getBinInfoValidator();
        $exchangeRateValidator = ValidatorFactory::getExchangeRateValidator();

        $reflectionClass = new \ReflectionClass(SchemaValidator::class);
        $schemaPathProperty = $reflectionClass->getProperty('schemaPath');
        $schemaPathProperty->setAccessible(true);

        $commissionSchemaPath = $schemaPathProperty->getValue($commissionValidator);
        $binInfoSchemaPath = $schemaPathProperty->getValue($binInfoValidator);
        $exchangeRateSchemaPath = $schemaPathProperty->getValue($exchangeRateValidator);

        $this->assertStringEndsWith('/src/Resources/Schema/line_schema.json', $commissionSchemaPath);
        $this->assertStringEndsWith('/src/Resources/Schema/bin_schema.json', $binInfoSchemaPath);
        $this->assertStringEndsWith('/src/Resources/Schema/rates_schema.json', $exchangeRateSchemaPath);

        // Verify that the schema files exist
        $this->assertFileExists($commissionSchemaPath);
        $this->assertFileExists($binInfoSchemaPath);
        $this->assertFileExists($exchangeRateSchemaPath);
    }

    public function testValidatorsUseJsonSchemaValidator(): void
    {
        $validator = ValidatorFactory::getCommissionLineValidator();

        // Use reflection to check the validator instance
        $reflectionClass = new \ReflectionClass(SchemaValidator::class);
        $validatorProperty = $reflectionClass->getProperty('validator');
        $validatorProperty->setAccessible(true);

        $jsonSchemaValidator = $validatorProperty->getValue($validator);

        $this->assertInstanceOf(Validator::class, $jsonSchemaValidator);
    }

    public function testValidatorsRejectInvalidData(): void
    {
        $commissionValidator = ValidatorFactory::getCommissionLineValidator();
        $binInfoValidator = ValidatorFactory::getBinInfoValidator();
        $exchangeRateValidator = ValidatorFactory::getExchangeRateValidator();

        // Invalid commission data (missing currency)
        $invalidCommissionData = (object)[
            'bin' => '45717360',
            'amount' => '100.00'
        ];

        // Invalid bin info data (missing country)
        $invalidBinInfoData = (object)[
            'number' => (object)[
                'length' => 16,
                'luhn' => true
            ],
            'scheme' => 'visa',
            'type' => 'debit',
            'brand' => 'Visa Classic'
        ];

        // Invalid exchange rate data (missing rates)
        $invalidExchangeRateData = (object)[
            'success' => true,
            'timestamp' => 1631264999,
            'base' => 'EUR',
            'date' => '2021-09-10'
        ];

        try {
            $commissionValidator->validate($invalidCommissionData);
            $this->fail('Expected ValidationException was not thrown for invalid commission line data');
        } catch (\App\Exception\ValidationException $e) {
            $this->assertTrue(true);
        }

        try {
            $binInfoValidator->validate($invalidBinInfoData);
            $this->fail('Expected ValidationException was not thrown for invalid bin info data');
        } catch (\App\Exception\ValidationException $e) {
            $this->assertTrue(true);
        }

        try {
            $exchangeRateValidator->validate($invalidExchangeRateData);
            $this->fail('Expected ValidationException was not thrown for invalid exchange rate data');
        } catch (\App\Exception\ValidationException $e) {
            $this->assertTrue(true);
        }
    }
}
