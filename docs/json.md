# Klasse Json

## Zweck

`Json` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/json.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static decode()` | Verarbeitet die Funktion decode. |
| public | `static encode()` | Verarbeitet die Funktion encode. |
| public | `static isJson()` | Verarbeitet die Funktion is json. |
| public | `static loop()` | Verarbeitet die Funktion loop. |
| public | `static elementExists()` | Verarbeitet die Funktion element exists. |
| public | `static getElement()` | Verarbeitet die Funktion get element. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
