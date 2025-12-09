<?php

class Format {
    /**
     * Formatiert ein Datum korrekt für ein HTML Input Feld des Typen date
     * @param mixed $date das zu formatierende Datum
     * @return mixed das korrekt formatierte Datum
     */
    public static function dateForInput(mixed $date): mixed {
        return date('Y-m-d', strtotime($date));
    }

    /**
     * Formatiert eine Uhrzeit korrekt für ein HTML Input Feld des Typen time
     * @param mixed $time die zu formatierende Zeit
     * @return mixed die korrekt formatierte Zeit
     */
    public static function timeForInput(mixed $time): mixed {
        return date('H:i:s', strtotime($time));
    }

    /**
     * Formatiert ein Datum korrekt zum Anzeigen für einen Nutzer
     * @param mixed $date das zu formatierende Datum
     * @return mixed das korrekt formatierte Datum
     */
    public static function dateToView(mixed $date) {
        return date('d.m.Y', strtotime($date));
    }

    /**
     * Schneidet einen String ab
     * @param string $string der ab zu schneidende String
     * @param int $width (Optional, standart 14) wie lang soll der String maximal sein
     * @param int $shortBy (Optional, standart 14) Ab welchem Charackter soll der String abgeschnitten werden
     * @return string der abgeschnittene String
     */
    public static function shortString(string $string, int $width = 14, int $shortBy = 14): string {
        if (strlen($string) <= $width) {
            return $string;
        } else {
            $shortString = substr($string, 0, $shortBy) . '....';
            return $shortString;
        }
    }

    /**
     * Entfernt alle Nichtalphabetische- und nichtnumerische Charackter aus einem String
     * @param string $string der zu ändernde String
     * @return string der modifizierte String
     */
    public static function cleanString(string $string): string {
        return preg_replace("/[^a-zA-Z0-9]/", "", $string);
    }

    /**
     * Konvertiert neue Zeilen Encodes von HTML zu INPUT und umgekehrt
     * @param string $string der Text zum Konvertieren
     * @param bool $forHTML Input zu HTML (true) | HTML zu Input (false)
     * @return string der korrekt formatierte Text
    */
    public static function newLineCode(string $string, bool $forHtml = true): string {
        if ($forHtml) {
            return str_replace("\r\n", "<br>", $string);
        }

        return str_replace("<br>", "\r\n", $string);
    }
}
