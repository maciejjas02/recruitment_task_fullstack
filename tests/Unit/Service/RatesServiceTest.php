<?php

namespace App\Tests\Unit\Service;

use App\Service\NbpClient;
use App\Service\RateCalculator;
use App\Service\RatesService;
use PHPUnit\Framework\TestCase;

class RatesServiceTest extends TestCase
{
    /** @var RatesService */
    private $service;
    
    /** @var NbpClient */
    private $nbpClient;
    
    /** @var RateCalculator */
    private $calculator;

    protected function setUp(): void
    {
        $this->nbpClient = $this->createMock(NbpClient::class);
        $this->calculator = $this->createMock(RateCalculator::class);
        $this->service = new RatesService($this->nbpClient, $this->calculator);
    }

    public function testGetRatesReturnsAllCurrencies(): void
    {
        // Mock odpowiedzi klienta NBP dla wszystkich walut
        $this->nbpClient->method('avgForDate')
            ->willReturnCallback(function($code, $date) {
                $rates = [
                    'EUR' => ['code' => 'EUR', 'mid' => 4.3404, 'date' => '2024-10-24'],
                    'USD' => ['code' => 'USD', 'mid' => 4.0123, 'date' => '2024-10-24'],
                    'CZK' => ['code' => 'CZK', 'mid' => 0.1789, 'date' => '2024-10-24'],
                    'IDR' => ['code' => 'IDR', 'mid' => 0.0003, 'date' => '2024-10-24'],
                    'BRL' => ['code' => 'BRL', 'mid' => 0.7234, 'date' => '2024-10-24'],
                ];
                return $rates[$code] ?? null;
            });

        // Mock odpowiedzi kalkulatora
        $this->calculator->method('buy')
            ->willReturnCallback(function($code, $mid) {
                // Główne waluty zwracają kurs kupna, pozostałe null
                return in_array($code, ['EUR', 'USD']) ? $mid - 0.15 : null;
            });

        $this->calculator->method('sell')
            ->willReturnCallback(function($code, $mid) {
                // Główne waluty +0.11, pozostałe +0.20
                $margin = in_array($code, ['EUR', 'USD']) ? 0.11 : 0.20;
                return $mid + $margin;
            });

        $result = $this->service->getRates('2024-10-24');

        // Powinno zwrócić dane dla wszystkich 5 walut
        $this->assertCount(5, $result);

        // Weryfikacja struktury pierwszego elementu (EUR)
        $eur = $result[0];
        $this->assertEquals('EUR', $eur['code']);
        $this->assertEquals(4.3404, $eur['mid']);
        $this->assertEqualsWithDelta(4.1904, $eur['buy'], 0.0001); // 4.3404 - 0.15
        $this->assertEqualsWithDelta(4.4504, $eur['sell'], 0.0001); // 4.3404 + 0.11
        $this->assertEquals('2024-10-24', $eur['date']);

        // Weryfikacja waluty pobocznej (CZK) ma null kurs kupna
        $czk = array_filter($result, fn($r) => $r['code'] === 'CZK')[2];
        $this->assertEquals('CZK', $czk['code']);
        $this->assertEquals(0.1789, $czk['mid']);
        $this->assertNull($czk['buy']);
        $this->assertEqualsWithDelta(0.3789, $czk['sell'], 0.0001); // 0.1789 + 0.20
    }

    public function testGetRatesWithNullDate(): void
    {
        // Test że null data jest przekazywana do klienta NBP
        $this->nbpClient->expects($this->exactly(5))
            ->method('avgForDate')
            ->with($this->anything(), null)
            ->willReturn(['code' => 'EUR', 'mid' => 4.0, 'date' => '2024-10-24']);

        $this->calculator->method('buy')->willReturn(null);
        $this->calculator->method('sell')->willReturn(1.0);

        $this->service->getRates(null);
    }

    public function testGetRatesWithSpecificDate(): void
    {
        $testDate = '2024-01-15';
        
        // Test że konkretna data jest przekazywana do klienta NBP
        $this->nbpClient->expects($this->exactly(5))
            ->method('avgForDate')
            ->with($this->anything(), $testDate)
            ->willReturn(['code' => 'EUR', 'mid' => 4.0, 'date' => $testDate]);

        $this->calculator->method('buy')->willReturn(null);
        $this->calculator->method('sell')->willReturn(1.0);

        $this->service->getRates($testDate);
    }

    public function testGetHistoryReturnsCorrectStructure(): void
    {
        $testCode = 'EUR';
        $testDate = '2024-10-24';
        
        $mockHistoryData = [
            ['date' => '2024-10-22', 'mid' => 4.3400],
            ['date' => '2024-10-23', 'mid' => 4.3402],
            ['date' => '2024-10-24', 'mid' => 4.3404],
        ];

        $this->nbpClient->expects($this->once())
            ->method('avgHistory14')
            ->with($testCode, $testDate)
            ->willReturn($mockHistoryData);

        $this->calculator->method('buy')
            ->willReturnCallback(function($code, $mid) {
                return $code === 'EUR' ? $mid - 0.15 : null;
            });

        $this->calculator->method('sell')
            ->willReturnCallback(function($code, $mid) {
                return $mid + 0.11; // Marża EUR
            });

        $result = $this->service->getHistory($testCode, $testDate);

        $this->assertCount(3, $result);

        // Weryfikacja struktury każdego punktu
        foreach ($result as $i => $point) {
            $this->assertArrayHasKey('date', $point);
            $this->assertArrayHasKey('mid', $point);
            $this->assertArrayHasKey('buy', $point);
            $this->assertArrayHasKey('sell', $point);

            $expectedMid = $mockHistoryData[$i]['mid'];
            $this->assertEquals($mockHistoryData[$i]['date'], $point['date']);
            $this->assertEquals($expectedMid, $point['mid']);
            $this->assertEquals($expectedMid - 0.15, $point['buy']);
            $this->assertEquals($expectedMid + 0.11, $point['sell']);
        }
    }

    public function testGetHistoryWithMinorCurrency(): void
    {
        $testCode = 'CZK';
        $testDate = '2024-10-24';
        
        $mockHistoryData = [
            ['date' => '2024-10-22', 'mid' => 0.1800],
            ['date' => '2024-10-23', 'mid' => 0.1802],
        ];

        $this->nbpClient->expects($this->once())
            ->method('avgHistory14')
            ->with($testCode, $testDate)
            ->willReturn($mockHistoryData);

        $this->calculator->method('buy')
            ->willReturn(null); // Waluty poboczne nie mają kursów kupna

        $this->calculator->method('sell')
            ->willReturnCallback(function($code, $mid) {
                return $mid + 0.20; // Marża waluty pobocznej
            });

        $result = $this->service->getHistory($testCode, $testDate);

        $this->assertCount(2, $result);

        // Weryfikacja waluty pobocznej ma null kursy kupna
        foreach ($result as $point) {
            $this->assertNull($point['buy']);
            $this->assertIsFloat($point['sell']);
        }
    }

    public function testGetHistoryWithNullDate(): void
    {
        $testCode = 'USD';
        
        // Test że null data jest przekazywana do klienta NBP
        $this->nbpClient->expects($this->once())
            ->method('avgHistory14')
            ->with($testCode, null);

        $this->calculator->method('buy')->willReturn(3.85);
        $this->calculator->method('sell')->willReturn(4.11);

        $this->nbpClient->method('avgHistory14')
            ->willReturn([['date' => '2024-10-24', 'mid' => 4.0]]);

        $this->service->getHistory($testCode, null);
    }

    public function testGetRatesCallsCalculatorWithCorrectParameters(): void
    {
        $mockNbpResponse = ['code' => 'EUR', 'mid' => 4.5678, 'date' => '2024-10-24'];
        
        $this->nbpClient->method('avgForDate')
            ->willReturn($mockNbpResponse);

        // Weryfikacja kalkulator jest wywoływany z prawidłowymi parametrami
        $this->calculator->expects($this->exactly(5))
            ->method('buy')
            ->with($this->callback(function($code) {
                return in_array($code, ['EUR', 'USD', 'CZK', 'IDR', 'BRL']);
            }), 4.5678);

        $this->calculator->expects($this->exactly(5))
            ->method('sell')
            ->with($this->callback(function($code) {
                return in_array($code, ['EUR', 'USD', 'CZK', 'IDR', 'BRL']);
            }), 4.5678);

        $this->calculator->method('buy')->willReturn(4.4178);
        $this->calculator->method('sell')->willReturn(4.6778);

        $this->service->getRates('2024-10-24');
    }

    public function testServiceHandlesAllDefinedCurrencies(): void
    {
        $expectedCurrencies = ['EUR', 'USD', 'CZK', 'IDR', 'BRL'];
        
        $this->nbpClient->method('avgForDate')
            ->willReturn(['code' => 'TEST', 'mid' => 1.0, 'date' => '2024-10-24']);
        
        $this->calculator->method('buy')->willReturn(0.85);
        $this->calculator->method('sell')->willReturn(1.11);

        $result = $this->service->getRates();

        // Weryfikacja wszystkich oczekiwanych walut jest obecnych
        $returnedCodes = array_column($result, 'code');
        $this->assertEquals($expectedCurrencies, $returnedCodes);
    }

    public function testGetHistoryPreservesDateOrder(): void
    {
        // Test że historia zachowuje kolejność zwróconą przez klienta NBP
        $testCode = 'EUR';
        
        $mockHistoryData = [
            ['date' => '2024-10-20', 'mid' => 4.3400],
            ['date' => '2024-10-21', 'mid' => 4.3402],
            ['date' => '2024-10-22', 'mid' => 4.3404],
            ['date' => '2024-10-23', 'mid' => 4.3406],
            ['date' => '2024-10-24', 'mid' => 4.3408],
        ];

        $this->nbpClient->method('avgHistory14')
            ->willReturn($mockHistoryData);

        $this->calculator->method('buy')->willReturn(4.0);
        $this->calculator->method('sell')->willReturn(4.5);

        $result = $this->service->getHistory($testCode);

        // Weryfikacja daty są w tej samej kolejności
        $resultDates = array_column($result, 'date');
        $expectedDates = array_column($mockHistoryData, 'date');
        $this->assertEquals($expectedDates, $resultDates);
    }
}