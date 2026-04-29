# Klasse DatabaseBridge

## Zweck

`DatabaseBridge` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/database_bridge.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static isSQL()` | Verarbeitet die Funktion is s q l. |
| private | `static isGBDB()` | Verarbeitet die Funktion is g b d b. |
| private | `static ensureSQL()` | Verarbeitet die Funktion ensure s q l. |
| public | `static get()` | Liest Daten aus der angegebenen Quelle. |
| public | `static insert()` | Fügt neue Daten ein. |
| public | `static delete()` | Löscht Daten aus der angegebenen Quelle. |
| public | `static update()` | Verarbeitet die Funktion update. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
