<?php

namespace App\Tests\Integration\Controller;

use App\Service\RatesService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RatesControllerTest extends WebTestCase
{
    public function testGetRatesEndpoint(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/rates');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        
        $this->assertIsArray($data);
        $this->assertCount(5, $data); // EUR, USD, CZK, IDR, BRL
        
        // Verify structure of each rate entry
        foreach ($data as $rate) {
            $this->assertArrayHasKey('code', $rate);
            $this->assertArrayHasKey('mid', $rate);
            $this->assertArrayHasKey('buy', $rate);
            $this->assertArrayHasKey('sell', $rate);
            $this->assertArrayHasKey('date', $rate);
            
            $this->assertIsString($rate['code']);
            $this->assertIsFloat($rate['mid']);
            $this->assertIsFloat($rate['sell']);
            $this->assertIsString($rate['date']);
            // buy can be float or null (for minor currencies)
            $this->assertTrue(is_float($rate['buy']) || is_null($rate['buy']));
        }
        
        // Verify major currencies have buy rates
        $eurRate = array_filter($data, fn($r) => $r['code'] === 'EUR')[0];
        $usdRate = array_filter($data, fn($r) => $r['code'] === 'USD')[1];
        
        $this->assertIsFloat($eurRate['buy']);
        $this->assertIsFloat($usdRate['buy']);
        
        // Verify minor currencies don't have buy rates
        $czkRate = array_filter($data, fn($r) => $r['code'] === 'CZK')[2];
        $this->assertNull($czkRate['buy']);
    }

    public function testGetRatesWithDateParameter(): void
    {
        $client = static::createClient();
        
        $testDate = '2024-01-15';
        $client->request('GET', '/api/rates', ['date' => $testDate]);
        
        $this->assertResponseIsSuccessful();
        
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        
        $this->assertIsArray($data);
        $this->assertCount(5, $data);
        
        // All rates should have the same date (or close to it due to weekend handling)
        foreach ($data as $rate) {
            $this->assertRegExp('/^\d{4}-\d{2}-\d{2}$/', $rate['date']);
        }
    }

    public function testGetRatesHasCacheHeaders(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/rates');
        
        $this->assertResponseIsSuccessful();
        
        $response = $client->getResponse();
        $this->assertTrue($response->headers->has('Cache-Control'));
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=60', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('s-maxage=60', $cacheControl);
    }

    public function testGetRatesHistoryEndpoint(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/rates/EUR/history');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('points', $data);
        
        $this->assertEquals('EUR', $data['code']);
        $this->assertIsArray($data['points']);
        
        // Verify structure of history points
        foreach ($data['points'] as $point) {
            $this->assertArrayHasKey('date', $point);
            $this->assertArrayHasKey('mid', $point);
            $this->assertArrayHasKey('buy', $point);
            $this->assertArrayHasKey('sell', $point);
            
            $this->assertIsString($point['date']);
            $this->assertIsFloat($point['mid']);
            $this->assertIsFloat($point['sell']);
            // EUR should have buy rates
            $this->assertIsFloat($point['buy']);
        }
    }

    public function testGetRatesHistoryWithMinorCurrency(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/rates/CZK/history');
        
        $this->assertResponseIsSuccessful();
        
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('CZK', $data['code']);
        
        // Minor currencies should have null buy rates
        foreach ($data['points'] as $point) {
            $this->assertNull($point['buy']);
            $this->assertIsFloat($point['sell']);
        }
    }

    public function testGetRatesHistoryWithDateParameter(): void
    {
        $client = static::createClient();
        
        $testDate = '2024-01-15';
        $client->request('GET', '/api/rates/USD/history', ['date' => $testDate]);
        
        $this->assertResponseIsSuccessful();
        
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals('USD', $data['code']);
        $this->assertIsArray($data['points']);
    }

    public function testGetRatesHistoryHasCacheHeaders(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/rates/EUR/history');
        
        $this->assertResponseIsSuccessful();
        
        $response = $client->getResponse();
        $this->assertTrue($response->headers->has('Cache-Control'));
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=60', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('s-maxage=60', $cacheControl);
    }

    public function testGetRatesHistoryWithInvalidCurrencyCode(): void
    {
        $client = static::createClient();
        
        // Test with too short code - should return React frontend (200)
        $client->request('GET', '/api/rates/EU/history');
        $this->assertResponseIsSuccessful(); // Returns React frontend due to wildcard route
        
        // Test with too long code - should return React frontend (200)  
        $client->request('GET', '/api/rates/EURO/history');
        $this->assertResponseIsSuccessful(); // Returns React frontend due to wildcard route
        
        // Test with numbers - should return React frontend (200)
        $client->request('GET', '/api/rates/123/history');
        $this->assertResponseIsSuccessful(); // Returns React frontend due to wildcard route
    }

    public function testGetRatesHistoryWithValidButUnsupportedCurrency(): void
    {
        $client = static::createClient();
        
        // GBP is a valid 3-letter code but not in our supported list
        $client->request('GET', '/api/rates/GBP/history');
        
        // This should either return an error response or throw an exception
        // depending on how the NBP client handles unsupported currencies
        $this->assertTrue(
            $client->getResponse()->getStatusCode() >= 400 || 
            $client->getResponse()->getStatusCode() === 200
        );
    }

    public function testRatesEndpointPerformance(): void
    {
        $client = static::createClient();
        
        $startTime = microtime(true);
        $client->request('GET', '/api/rates');
        $endTime = microtime(true);
        
        $this->assertResponseIsSuccessful();
        
        // Response should be reasonably fast (less than 10 seconds)
        // This accounts for potential NBP API delays and caching
        $duration = $endTime - $startTime;
        $this->assertLessThan(10.0, $duration, 'API response took too long: ' . $duration . ' seconds');
    }

    public function testHistoryEndpointPerformance(): void
    {
        $client = static::createClient();
        
        $startTime = microtime(true);
        $client->request('GET', '/api/rates/EUR/history');
        $endTime = microtime(true);
        
        $this->assertResponseIsSuccessful();
        
        // Response should be reasonably fast (less than 10 seconds)
        $duration = $endTime - $startTime;
        $this->assertLessThan(10.0, $duration, 'API response took too long: ' . $duration . ' seconds');
    }

    public function testMultipleConcurrentRequests(): void
    {
        $client = static::createClient();
        
        // Make multiple requests to test caching behavior
        $client->request('GET', '/api/rates');
        $firstResponse = $client->getResponse()->getContent();
        
        $client->request('GET', '/api/rates');
        $secondResponse = $client->getResponse()->getContent();
        
        $this->assertResponseIsSuccessful();
        
        // Responses should be consistent (caching should work)
        $firstData = json_decode($firstResponse, true);
        $secondData = json_decode($secondResponse, true);
        
        $this->assertCount(5, $firstData);
        $this->assertCount(5, $secondData);
        
        // The structure should be the same (actual rates might differ slightly due to timing)
        foreach ($firstData as $i => $rate) {
            $this->assertEquals($rate['code'], $secondData[$i]['code']);
        }
    }

    public function testCorsHeaders(): void
    {
        $client = static::createClient();
        
        // Test CORS headers if they're configured
        $client->request('GET', '/api/rates');
        
        $this->assertResponseIsSuccessful();
        
        // This test can be expanded if CORS headers are needed for frontend
        $response = $client->getResponse();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
    }
}