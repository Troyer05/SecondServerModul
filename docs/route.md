# Klasse Route

## Zweck

`Route` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/route.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static middleware()` | Verarbeitet die Funktion middleware. |
| public | `static get()` | Liest Daten aus der angegebenen Quelle. |
| public | `static post()` | Verarbeitet die Funktion post. |
| public | `static put()` | Verarbeitet die Funktion put. |
| public | `static delete()` | Löscht Daten aus der angegebenen Quelle. |
| public | `static patch()` | Verarbeitet die Funktion patch. |
| public | `static notFound()` | Verarbeitet die Funktion not found. |
| public | `static methodNotAllowed()` | Verarbeitet die Funktion method not allowed. |
| private | `static addRoute()` | Verarbeitet die Funktion add route. |
| public | `static dispatch()` | Verarbeitet die Funktion dispatch. |
| private | `static makePattern()` | Verarbeitet die Funktion make pattern. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
