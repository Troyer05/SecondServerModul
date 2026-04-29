# Klasse Http

## Zweck

`Http` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/http.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static get()` | Liest Daten aus der angegebenen Quelle. |
| public | `static post()` | Verarbeitet die Funktion post. |
| public | `static sendMail()` | Verarbeitet die Funktion send mail. |
| public | `static jsonResponse()` | Verarbeitet die Funktion json response. |
| public | `static redirect()` | Verarbeitet die Funktion redirect. |
| public | `static getHeaders()` | Verarbeitet die Funktion get headers. |
| public | `static method()` | Verarbeitet die Funktion method. |
| public | `static isJson()` | Verarbeitet die Funktion is json. |
| public | `static jsonInput()` | Verarbeitet die Funktion json input. |
| private | `static formatHeaders()` | Verarbeitet die Funktion format headers. |
| private | `static implodeHeaders()` | Verarbeitet die Funktion implode headers. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
