<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

final class NbpClient
{
    private const BASE = 'https://api.nbp.pl/api';

    public function __construct(private CacheItemPoolInterface $cache) {}

    /**
     * Średni kurs dla danej waluty na konkretny dzień.
     * Jeśli NBP zwróci 404 (weekend/święto), cofamy się max 7 dni.
     * @return array{code:string, mid:float, date:string}
     */
    public function avgForDate(string $code, ?string $date = null): array
    {
        $code = strtoupper($code);
        $day  = $date ? new \DateTimeImmutable($date) : new \DateTimeImmutable('today');

        for ($i = 0; $i < 7; $i++) {
            $d = $day->sub(new \DateInterval("P{$i}D"))->format('Y-m-d');
            $url = sprintf('%s/exchangerates/rates/A/%s/%s/?format=json', self::BASE, $code, $d);
            $json = $this->fetchCached($url, 300);
            if ($json && isset($json['rates'][0]['mid'])) {
                return [
                    'code' => $code,
                    'mid'  => (float)$json['rates'][0]['mid'],
                    'date' => $json['rates'][0]['effectiveDate'] ?? $d,
                ];
            }
        }
        throw new \RuntimeException("NBP: no data for {$code} near {$day->format('Y-m-d')}");
    }

    /**
     * 14 dni historii (kończących się na dacie) — rosnąco po dacie.
     * @return array<int, array{date:string, mid:float}>
     */
    public function avgHistory14(string $code, ?string $date = null): array
    {
        $code = strtoupper($code);

        // końcowa data (jak wyżej — jeśli to weekend, avgForDate cofnie do poprzedniego dnia roboczego)
        $end = $this->avgForDate($code, $date)['date'];
        $endDt = new \DateTimeImmutable($end);
        $start = $endDt->sub(new \DateInterval('P13D'))->format('Y-m-d');

        $url = sprintf('%s/exchangerates/rates/A/%s/%s/%s/?format=json', self::BASE, $code, $start, $end);
        $json = $this->fetchCached($url, 300);

        $out = [];
        foreach ($json['rates'] ?? [] as $r) {
            if (!isset($r['mid'], $r['effectiveDate'])) continue;
            $out[] = [
                'date' => $r['effectiveDate'],
                'mid'  => (float)$r['mid'],
            ];
        }

        // NBP zwraca rosnąco, ale posortujmy dla pewności
        usort($out, fn($a, $b) => strcmp($a['date'], $b['date']));
        return $out;
    }

    /** prościutki cache na 300s */
    private function fetchCached(string $url, int $ttl)
    {
        $key = 'nbp_' . md5($url);
        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        $ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "Accept: application/json\r\n"]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null; // pozwalamy wyżej obsłużyć „cofanie się” lub błąd
        }

        $data = json_decode($raw, true);
        $item->set($data);
        $item->expiresAfter($ttl);
        $this->cache->save($item);

        return $data;
    }
}
