<?php

namespace Tests\Commission\Calculator;

use App\Commission\Calculator\UtilsCalculatorTrait;
use PHPUnit\Framework\TestCase;

class UtilsCalculatorTraitTest extends TestCase
{
    private object $traitObject;
    
    protected function setUp(): void
    {
        // Create a test class that uses the trait
        $this->traitObject = new class {
            use UtilsCalculatorTrait;
        };
    }
    
    public function testFormatToFloat(): void
    {
        $this->assertEquals(100.0, $this->traitObject->formatToFloat('100'));
        $this->assertEquals(100.5, $this->traitObject->formatToFloat('100.5'));
        $this->assertEquals(100.56, $this->traitObject->formatToFloat('100.56'));
        $this->assertEquals(0.0, $this->traitObject->formatToFloat('0'));
        $this->assertEquals(0.0, $this->traitObject->formatToFloat('0.0'));
    }
    
    public function testFormatToString(): void
    {
        $this->assertEquals('100.0000', $this->traitObject->formatToString(100));
        $this->assertEquals('100.5000', $this->traitObject->formatToString(100.5));
        $this->assertEquals('100.5600', $this->traitObject->formatToString(100.56));
        $this->assertEquals('0.0000', $this->traitObject->formatToString(0));
    }
    
    public function testFormatToStringRoundsCorrectly(): void
    {
        $this->assertEquals('100.5678', $this->traitObject->formatToString(100.56781));
        $this->assertEquals('100.5679', $this->traitObject->formatToString(100.56789));
        $this->assertEquals('0.0001', $this->traitObject->formatToString(0.00011));
        $this->assertEquals('0.0000', $this->traitObject->formatToString(0.00001));
    }
}