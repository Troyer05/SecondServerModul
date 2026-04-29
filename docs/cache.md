# Klasse Cache

## Zweck

`Cache` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/cache.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| protected | `static ensureSession()` | Verarbeitet die Funktion ensure session. |
| public | `static load()` | Verarbeitet die Funktion load. |
| public | `static update()` | Verarbeitet die Funktion update. |
| public | `static clear()` | Verarbeitet die Funktion clear. |
| public | `static flush()` | Verarbeitet die Funktion flush. |
| public | `static exists()` | Verarbeitet die Funktion exists. |
| public | `static all()` | Verarbeitet die Funktion all. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
