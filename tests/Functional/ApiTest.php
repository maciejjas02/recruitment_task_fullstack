<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTest extends WebTestCase
{
    public function testCompleteApiWorkflow(): void
    {
        $client = static::createClient();
        
        // 1. Test endpoint sprawdzenia konfiguracji
        $client->request('GET', '/api/setup-check');
        $this->assertResponseIsSuccessful();
        
        $setupData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('ok', $setupData);
        $this->assertTrue($setupData['ok']);
        
        // 2. Test endpoint kursów
        $client->request('GET', '/api/rates');
        $this->assertResponseIsSuccessful();
        
        $ratesData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($ratesData);
        $this->assertCount(5, $ratesData);
        
        // 3. Test historii dla każdej waluty z kursów
        foreach ($ratesData as $rate) {
            $code = $rate['code'];
            $date = $rate['date'];
            
            $client->request('GET', "/api/rates/{$code}/history", ['date' => $date]);
            $this->assertResponseIsSuccessful();
            
            $historyData = json_decode($client->getResponse()->getContent(), true);
            $this->assertEquals($code, $historyData['code']);
            $this->assertIsArray($historyData['points']);
            
            // Weryfikacja spójności między aktualnym kursem a najnowszym punktem historii
            if (!empty($historyData['points'])) {
                $latestPoint = end($historyData['points']);
                
                $this->assertEquals($rate['date'], $latestPoint['date']);
                $this->assertEquals($rate['mid'], $latestPoint['mid']);
                $this->assertEquals($rate['buy'], $latestPoint['buy']);
                $this->assertEquals($rate['sell'], $latestPoint['sell']);
            }
        }
    }

    public function testApiResponseTimes(): void
    {
        $client = static::createClient();
        
        $endpoints = [
            '/api/setup-check',
            '/api/rates',
            '/api/rates/EUR/history',
            '/api/rates/USD/history',
            '/api/rates/CZK/history'
        ];
        
        foreach ($endpoints as $endpoint) {
            $startTime = microtime(true);
            $client->request('GET', $endpoint);
            $endTime = microtime(true);
            
            $this->assertResponseIsSuccessful();
            
            $duration = $endTime - $startTime;
            $this->assertLessThan(15.0, $duration, "Endpoint {$endpoint} trwał zbyt długo: {$duration}s");
        }
    }

    public function testBusinessLogicConsistency(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/rates');
        $this->assertResponseIsSuccessful();
        
        $rates = json_decode($client->getResponse()->getContent(), true);
        
        foreach ($rates as $rate) {
            $code = $rate['code'];
            $mid = $rate['mid'];
            $buy = $rate['buy'];
            $sell = $rate['sell'];
            
            // Weryfikacja logiki biznesowej
            if (in_array($code, ['EUR', 'USD'])) {
                // Główne waluty powinny mieć kursy kupna
                $this->assertIsFloat($buy);
                $this->assertEquals(round($mid - 0.15, 4), $buy, "Nieprawidłowy kurs kupna dla {$code}");
                $this->assertEquals(round($mid + 0.11, 4), $sell, "Nieprawidłowy kurs sprzedaży dla {$code}");
            } else {
                // Pozostałe waluty nie powinny mieć kursów kupna
                $this->assertNull($buy, "Waluta {$code} nie powinna mieć kursu kupna");
                $this->assertEquals(round($mid + 0.20, 4), $sell, "Nieprawidłowy kurs sprzedaży dla waluty {$code}");
            }
            
            // Sprzedaż zawsze wyższa od mid
            $this->assertGreaterThan($mid, $sell);
            
            // Jeśli kupno istnieje, powinno być niższe od mid
            if ($buy !== null) {
                $this->assertLessThan($mid, $buy);
            }
        }
    }

    public function testHistoryDataConsistency(): void
    {
        $client = static::createClient();
        
        $currencies = ['EUR', 'USD', 'CZK'];
        
        foreach ($currencies as $code) {
            $client->request('GET', "/api/rates/{$code}/history");
            $this->assertResponseIsSuccessful();
            
            $historyData = json_decode($client->getResponse()->getContent(), true);
            $points = $historyData['points'];
            
            $this->assertNotEmpty($points, "Historia nie powinna być pusta dla {$code}");
            
            // Weryfikacja dat w porządku rosnącym
            $prevDate = null;
            foreach ($points as $point) {
                if ($prevDate !== null) {
                    $this->assertGreaterThanOrEqual($prevDate, $point['date'], 
                        "Daty historii powinny być w porządku rosnącym dla {$code}");
                }
                $prevDate = $point['date'];
                
                // Weryfikacja logiki biznesowej dla każdego punktu
                $mid = $point['mid'];
                $buy = $point['buy'];
                $sell = $point['sell'];
                
                if (in_array($code, ['EUR', 'USD'])) {
                    $this->assertIsFloat($buy);
                    $this->assertEquals(round($mid - 0.15, 4), $buy);
                    $this->assertEquals(round($mid + 0.11, 4), $sell);
                } else {
                    $this->assertNull($buy);
                    $this->assertEquals(round($mid + 0.20, 4), $sell);
                }
            }
        }
    }

    public function testErrorHandling(): void
    {
        $client = static::createClient();
        
        $invalidRoutes = [
            '/api/rates/INVALID/history',
            '/api/rates/12/history', 
            '/api/rates/TOOLONG/history',
            '/api/nonexistent'
        ];
        
        foreach ($invalidRoutes as $route) {
            $client->request('GET', $route);
            $this->assertResponseIsSuccessful("Route {$route} powinien zwrócić frontend React");
        }
    }

    public function testCacheEffectiveness(): void
    {
        $client = static::createClient();
        
        $startTime1 = microtime(true);
        $client->request('GET', '/api/rates');
        $endTime1 = microtime(true);
        $firstDuration = $endTime1 - $startTime1;
        
        $this->assertResponseIsSuccessful();
        $firstResponse = $client->getResponse()->getContent();
        
        $startTime2 = microtime(true);
        $client->request('GET', '/api/rates');
        $endTime2 = microtime(true);
        $secondDuration = $endTime2 - $startTime2;
        
        $this->assertResponseIsSuccessful();
        $secondResponse = $client->getResponse()->getContent();
        
        $this->assertLessThanOrEqual($firstDuration * 2, $secondDuration, 
            "Żądanie z cache nie powinno być znacznie wolniejsze");
        
        $firstData = json_decode($firstResponse, true);
        $secondData = json_decode($secondResponse, true);
        
        $this->assertCount(5, $firstData);
        $this->assertCount(5, $secondData);
    }

    public function testDateParameterHandling(): void
    {
        $client = static::createClient();
        
        $testDates = [
            '2024-01-15',
            '2024-06-15', 
            '2024-12-31',
        ];
        
        foreach ($testDates as $date) {
            $client->request('GET', '/api/rates', ['date' => $date]);
            $this->assertResponseIsSuccessful();
            
            $ratesData = json_decode($client->getResponse()->getContent(), true);
            $this->assertCount(5, $ratesData);
            
            $client->request('GET', '/api/rates/EUR/history', ['date' => $date]);
            $this->assertResponseIsSuccessful();
            
            $historyData = json_decode($client->getResponse()->getContent(), true);
            $this->assertEquals('EUR', $historyData['code']);
            $this->assertIsArray($historyData['points']);
        }
    }

    public function testConcurrentRequests(): void
    {
        $client = static::createClient();
        
        $endpoints = [
            '/api/rates',
            '/api/rates/EUR/history',
            '/api/rates/USD/history',
            '/api/rates',
            '/api/rates/CZK/history'
        ];
        
        $responses = [];
        
        foreach ($endpoints as $endpoint) {
            $client->request('GET', $endpoint);
            $this->assertResponseIsSuccessful();
            $responses[] = [
                'endpoint' => $endpoint,
                'data' => json_decode($client->getResponse()->getContent(), true)
            ];
        }
        
        foreach ($responses as $response) {
            $this->assertIsArray($response['data']);
            $this->assertNotEmpty($response['data']);
        }
        
        $ratesResponses = array_filter($responses, fn($r) => $r['endpoint'] === '/api/rates');
        if (count($ratesResponses) > 1) {
            $firstRates = array_values($ratesResponses)[0]['data'];
            $secondRates = array_values($ratesResponses)[1]['data'];
            
            $this->assertCount(count($firstRates), $secondRates);
        }
    }

    public function testApiDocumentationCompliance(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/rates');
        $this->assertResponseIsSuccessful();
        
        $rates = json_decode($client->getResponse()->getContent(), true);
        
        $expectedCurrencies = ['EUR', 'USD', 'CZK', 'IDR', 'BRL'];
        $actualCurrencies = array_column($rates, 'code');
        
        $this->assertEquals($expectedCurrencies, $actualCurrencies);
        
        $client->request('GET', '/api/rates/EUR/history');
        $this->assertResponseIsSuccessful();
        
        $history = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('code', $history);
        $this->assertArrayHasKey('points', $history);
        $this->assertEquals('EUR', $history['code']);
        $this->assertIsArray($history['points']);
        
        if (!empty($history['points'])) {
            $point = $history['points'][0];
            $this->assertArrayHasKey('date', $point);
            $this->assertArrayHasKey('mid', $point);
            $this->assertArrayHasKey('buy', $point);
            $this->assertArrayHasKey('sell', $point);
        }
    }
}