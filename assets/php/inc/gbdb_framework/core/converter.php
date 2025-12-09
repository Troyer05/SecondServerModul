<?php

class Converter {

    /**
     * Addiert bzw. multipliziert zwei Kommazahlen
     *
     * @param int|float|string $p Kommazahl (darf auch Komma enthalten)
     * @param int|float $a Multiplikator
     * @return string Formatiertes Ergebnis mit Komma als Dezimaltrennzeichen
     */
    public static function getSumme(int|float|string $p, int|float $a): string {
        // Eingaben vorbereiten
        $p = str_replace(',', '.', (string)$p);
        $result = $a * (float)$p;

        // Rückgabe als deutsche Formatierung (z. B. "123,45")
        return number_format($result, 2, ',', '');
    }

    /**
     * Konvertiert eine Kommazahl zu einer Ganzzahl (abschneiden, kein Runden)
     *
     * @param int|float|string $x Die Kommazahl
     * @return int Die abgeschnittene Ganzzahl
     */
    public static function convertToNumber(int|float|string $x): int {
        $x = str_replace(',', '.', (string)$x);
        return (int) floor((float)$x);
    }

    /**
     * Konvertiert deutsche Kommazahl zu float (z. B. "12,5" → 12.5)
     *
     * @param string|float|int $value
     * @return float
     */
    public static function toFloat(string|float|int $value): float {
        return (float) str_replace(',', '.', (string)$value);
    }
}
