<?php

namespace Tests\Services\BinInfo;

use App\Exception\NoBinInfoException;
use App\Exception\ValidationException;
use App\Http\BinlistClient;
use App\Services\BinInfo\BinLookup;
use App\Validator\ValidatorInterface;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BinLookupTest extends TestCase
{
    private BinlistClient|MockObject $mockClient;
    private ValidatorInterface|MockObject $mockValidator;
    private BinLookup $binLookup;
    
    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(BinlistClient::class);
        $this->mockValidator = $this->createMock(ValidatorInterface::class);
        $this->binLookup = new BinLookup($this->mockClient, $this->mockValidator);
    }
    
    public function testGetCountryCodeByBinReturnsCountryCode(): void
    {
        $mockResponse = (object)[
            'country' => (object)[
                'alpha2' => 'US'
            ]
        ];
        
        $this->mockClient->expects($this->once())
            ->method('lookupBin')
            ->with('45717360')
            ->willReturn($mockResponse);
            
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->with($mockResponse);
            
        $result = $this->binLookup->getCountryCodeByBin('45717360');
        
        $this->assertEquals('US', $result);
    }
    
    public function testGetCountryCodeByBinThrowsExceptionOnClientError(): void
    {
        $this->mockClient->expects($this->once())
            ->method('lookupBin')
            ->willThrowException($this->createMock(GuzzleException::class));
            
        $this->expectException(NoBinInfoException::class);
        
        $this->binLookup->getCountryCodeByBin('invalid');
    }
    
    public function testGetCountryCodeByBinThrowsExceptionOnValidationError(): void
    {
        $mockResponse = (object)[
            'incomplete' => 'data'
        ];
        
        $this->mockClient->expects($this->once())
            ->method('lookupBin')
            ->willReturn($mockResponse);
            
        $this->mockValidator->expects($this->once())
            ->method('validate')
            ->willThrowException(new ValidationException('Invalid response'));
            
        $this->expectException(ValidationException::class);
        
        $this->binLookup->getCountryCodeByBin('45717360');
    }
    
    public function testGetCountryCodeByBinCachesResponses(): void
    {
        $mockResponse = (object)[
            'country' => (object)[
                'alpha2' => 'US'
            ]
        ];
        
        // Should only be called once despite multiple calls to getCountryCodeByBin
        $this->mockClient->expects($this->once())
            ->method('lookupBin')
            ->willReturn($mockResponse);
            
        $this->mockValidator->expects($this->once())
            ->method('validate');
            
        $this->binLookup->getCountryCodeByBin('45717360');
        $result = $this->binLookup->getCountryCodeByBin('45717360');
        
        $this->assertEquals('US', $result);
    }
}