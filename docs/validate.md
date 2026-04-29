# Klasse Validate

## Zweck

`Validate` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/validate.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static required()` | Verarbeitet die Funktion required. |
| public | `static email()` | Verarbeitet die Funktion email. |
| public | `static number()` | Verarbeitet die Funktion number. |
| public | `static minLength()` | Verarbeitet die Funktion min length. |
| public | `static maxLength()` | Verarbeitet die Funktion max length. |
| public | `static regex()` | Verarbeitet die Funktion regex. |
| public | `static between()` | Verarbeitet die Funktion between. |
| public | `static in()` | Verarbeitet die Funktion in. |
| public | `static string()` | Verarbeitet die Funktion string. |
| public | `static validateArray()` | Verarbeitet die Funktion validate array. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
