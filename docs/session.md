# Klasse Session

## Zweck

`Session` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/session.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static handler()` | Verarbeitet die Funktion handler. |
| private | `static autoRegenerate()` | Verarbeitet die Funktion auto regenerate. |
| public | `static init()` | Initialisiert die Klasse und legt benötigte Strukturen an. |
| public | `static get()` | Liest Daten aus der angegebenen Quelle. |
| public | `static set()` | Speichert einen Wert in der angegebenen Quelle. |
| public | `static exists()` | Verarbeitet die Funktion exists. |
| public | `static delete()` | Löscht Daten aus der angegebenen Quelle. |
| public | `static destroy()` | Verarbeitet die Funktion destroy. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
