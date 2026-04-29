# Klasse Format

## Zweck

`Format` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/format.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static validDate()` | Verarbeitet die Funktion valid date. |
| public | `static dateForInput()` | Verarbeitet die Funktion date for input. |
| public | `static timeForInput()` | Verarbeitet die Funktion time for input. |
| public | `static dateToView()` | Verarbeitet die Funktion date to view. |
| public | `static shortString()` | Verarbeitet die Funktion short string. |
| public | `static cleanString()` | Verarbeitet die Funktion clean string. |
| public | `static newLineCode()` | Verarbeitet die Funktion new line code. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
