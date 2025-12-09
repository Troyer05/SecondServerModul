<?php

class Time {
    /**
     * Gibt wieder, wie lange ein Datum mit/oder Uhrzeit her ist
     * @param mixed $timestamp der TimeStamp
     * @return string Ausgabe wielange es her ist
     */
    public static function timeAgo(mixed $timestamp): string {
        $currentTime = time();
        $uploadedTime = strtotime($timestamp);
    
        $timeDifference = $currentTime - $uploadedTime;
    
        $seconds = $timeDifference;
        $minutes = round($seconds / 60);
        $hours   = round($seconds / 3600);
        $days    = round($seconds / 86400);
        $weeks   = round($seconds / 604800);
        $months  = round($seconds / 2629440);
        $years   = round($seconds / 31553280);
    
        if ($seconds <= 60) {
            return "vor $seconds Sekunden";
        } elseif ($minutes <= 60) {
            if ($minutes == 1) {
                return "vor einer Minute";
            } else {
                return "vor $minutes Minuten";
            }
        } elseif ($hours <= 24) {
            if ($hours == 1) {
                return "vor einer Stunde";
            } else {
                return "vor $hours Stunden";
            }
        } elseif ($days <= 7) {
            if ($days == 1) {
                return "vor einem Tag";
            } else {
                return "vor $days Tagen";
            }
        } elseif ($weeks <= 4.3) {  // 4.3 == 30/7
            if ($weeks == 1) {
                return "vor einer Woche";
            } else {
                return "vor $weeks Wochen";
            }
        } elseif ($months <= 12) {
            if ($months == 1) {
                return "vor einem Monat";
            } else {
                return "vor $months Monaten";
            }
        } else {
            if ($years == 1) {
                return "vor einem Jahr";
            } else {
                return "vor $years Jahren";
            }
        }
    }
}
