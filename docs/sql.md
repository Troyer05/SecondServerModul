# Klasse SQL

## Zweck

`SQL` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/sql.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static connect()` | Verarbeitet die Funktion connect. |
| private | `static run()` | Verarbeitet die Funktion run. |
| public | `static select()` | Verarbeitet die Funktion select. |
| public | `static insert()` | Fügt neue Daten ein. |
| public | `static update()` | Verarbeitet die Funktion update. |
| public | `static delete()` | Löscht Daten aus der angegebenen Quelle. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
