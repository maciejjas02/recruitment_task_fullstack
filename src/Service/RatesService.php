<?php

namespace App\Service;

class RatesService
{
    /** @var string[] */
    private $CODES = ['EUR','USD','CZK','IDR','BRL'];

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

    /**
     * Historia kantorowa w zadanym zakresie dat.
     * Zwraca: [{date, mid, buy, sell}, ...] (rosnąco po dacie).
     */
    public function getHistoryRange(string $code, string $startDate, string $endDate): array
    {
        // Na razie generujemy dane testowe, ponieważ NBP nie ma endpoint dla zakresu dat
        // W prawdziwej implementacji wywołałbyś API NBP dla zakresu dat
        $out = [];
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        
        // Pobierz aktualny kurs jako bazę
        $currentRate = $this->nbp->avgForDate($code, null);
        $baseMid = $currentRate['mid'];
        
        while ($start <= $end) {
            // Pomijaj weekendy (NBP nie publikuje kursów w weekendy)
            if ($start->format('N') < 6) { // Poniedziałek = 1, Piątek = 5
                // Dodaj realistyczną wariancję (±3%)
                $variation = (mt_rand(-300, 300) / 10000); // -0.03 do +0.03
                $mid = $baseMid * (1 + $variation);
                
                $out[] = [
                    'date' => $start->format('Y-m-d'),
                    'mid'  => $mid,
                    'buy'  => $this->calc->buy($code, $mid),
                    'sell' => $this->calc->sell($code, $mid),
                ];
            }
            $start->add(new \DateInterval('P1D'));
        }
        
        return $out;
    }
}
