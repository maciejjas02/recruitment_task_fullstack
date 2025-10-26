<?php

namespace App\Service;

class RateCalculator
{
    
    private const MAJORS = ['EUR', 'USD'];

    private function isMajor(string $code): bool
    {
        return \in_array(\strtoupper($code), self::MAJORS, true);
    }

    /**
     * Kurs kupna:
     *  - EUR, USD: mid - 0.15
     *  - pozostałe: brak (kantor nie skupuje) => null
     */
    public function buy(string $code, float $mid): ?float
    {
        if (!$this->isMajor($code)) {
            return null;
        }

        return \round($mid - 0.15, 4);
    }

    /**
     * Kurs sprzedaży:
     *  - EUR, USD: mid + 0.11
     *  - pozostałe: mid + 0.20
     */
    public function sell(string $code, float $mid): float
    {
        $margin = $this->isMajor($code) ? 0.11 : 0.20;

        return \round($mid + $margin, 4);
    }
}
