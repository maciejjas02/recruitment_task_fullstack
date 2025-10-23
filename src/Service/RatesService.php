<?php

namespace App\Service;

final class RatesService
{
    /** @var string[] */
    private array $CODES = ['EUR','USD','CZK','IDR','BRL'];

    public function __construct(
        private NbpClient $nbp,
        private RateCalculator $calc
    ) {}

    /**
     * Snapshot kursów kantorowych na daną datę (null => dziś).
     * Zwraca: [{code, mid, buy, sell, date}, ...]
     */
    public function getRates(?string $date = null): array
    {
        $out = [];
        foreach ($this->CODES as $code) {
            // średni kurs z NBP (z obsługą weekendów po stronie klienta)
            $midRow = $this->nbp->avgForDate($code, $date); // ['code','mid','date']

            $buy  = $this->calc->buy($code, $midRow['mid']);
            $sell = $this->calc->sell($code, $midRow['mid']);

            $out[] = [
                'code' => $code,
                'mid'  => $midRow['mid'],
                'buy'  => $buy,
                'sell' => $sell,
                'date' => $midRow['date'],
            ];
        }
        return $out;
    }

    /**
     * 14-dniowa historia kantorowa kończąca się na dacie (null => dziś).
     * Zwraca: [{date, mid, buy, sell}, ...] (rosnąco po dacie).
     */
    public function getHistory(string $code, ?string $date = null): array
    {
        $avgPoints = $this->nbp->avgHistory14($code, $date); // [{date, mid}, ...]
        $out = [];
        foreach ($avgPoints as $p) {
            $out[] = [
                'date' => $p['date'],
                'mid'  => $p['mid'],
                'buy'  => $this->calc->buy($code, $p['mid']),
                'sell' => $this->calc->sell($code, $p['mid']),
            ];
        }
        return $out;
    }
}
