<?php

namespace App\Tests\Unit\Service;

use App\Service\RateCalculator;
use PHPUnit\Framework\TestCase;

class RateCalculatorTest extends TestCase
{
    /** @var RateCalculator */
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RateCalculator();
    }

    /**
     * @dataProvider buyRateDataProvider
     */
    public function testBuyRates(string $code, float $mid, ?float $expected): void
    {
        $result = $this->calculator->buy($code, $mid);
        $this->assertEquals($expected, $result);
    }

    public function buyRateDataProvider(): array
    {
        return [
            
            ['EUR', 4.5000, 4.3500],
            ['USD', 4.0000, 3.8500],
            ['eur', 4.2500, 4.1000], 
            ['usd', 3.7500, 3.6000], 
            
            
            ['CZK', 0.1800, null],
            ['IDR', 0.0003, null],
            ['BRL', 0.8500, null],
            ['czk', 0.1800, null], 
            
            // Przypadki brzegowe
            ['EUR', 0.0000, -0.1500], 
            ['USD', 0.1500, 0.0000], 
        ];
    }

    /**
     * @dataProvider sellRateDataProvider
     */
    public function testSellRates(string $code, float $mid, float $expected): void
    {
        $result = $this->calculator->sell($code, $mid);
        $this->assertEquals($expected, $result);
    }

    public function sellRateDataProvider(): array
    {
        return [
            ['EUR', 4.5000, 4.6100],
            ['USD', 4.0000, 4.1100],
            ['eur', 4.2500, 4.3600], 
            ['usd', 3.7500, 3.8600], 
            
            ['CZK', 0.1800, 0.3800],
            ['IDR', 0.0003, 0.2003],
            ['BRL', 0.8500, 1.0500],
            ['czk', 0.1800, 0.3800], 
            
            
            ['EUR', 0.0000, 0.1100], 
            ['CZK', 0.0000, 0.2000], 
        ];
    }

    public function testRoundingPrecision(): void
    {
        
        $buyResult = $this->calculator->buy('EUR', 4.123456789);
        $this->assertEquals(3.9735, $buyResult); 

        $sellResult = $this->calculator->sell('EUR', 4.123456789);
        $this->assertEquals(4.2335, $sellResult); 

        $sellMinorResult = $this->calculator->sell('CZK', 0.123456789);
        $this->assertEquals(0.3235, $sellMinorResult); 
    }

    public function testMajorCurrencyDetection(): void
    {
        $this->assertNotNull($this->calculator->buy('EUR', 4.0));
        $this->assertNotNull($this->calculator->buy('USD', 4.0));
        
        $this->assertNull($this->calculator->buy('CZK', 4.0));
        $this->assertNull($this->calculator->buy('IDR', 4.0));
        $this->assertNull($this->calculator->buy('BRL', 4.0));
        $this->assertNull($this->calculator->buy('GBP', 4.0)); 
        $this->assertNull($this->calculator->buy('CHF', 4.0)); 
    }

    public function testCaseInsensitivity(): void
    {
        
        $this->assertEquals(
            $this->calculator->buy('EUR', 4.0),
            $this->calculator->buy('eur', 4.0)
        );
        
        $this->assertEquals(
            $this->calculator->sell('USD', 4.0),
            $this->calculator->sell('usd', 4.0)
        );
        
        $this->assertEquals(
            $this->calculator->sell('CZK', 4.0),
            $this->calculator->sell('czk', 4.0)
        );
    }
}