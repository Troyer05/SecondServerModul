# Klasse GreenQL

## Zweck

`GreenQL` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/greenql_engine.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static cleanName()` | Verarbeitet die Funktion clean name. |
| public | `static unquote()` | Verarbeitet die Funktion unquote. |
| public | `static stripComments()` | Verarbeitet die Funktion strip comments. |
| public | `static splitCommands()` | Verarbeitet die Funktion split commands. |
| public | `static evaluateValue()` | Verarbeitet die Funktion evaluate value. |
| public | `static resolveNameToken()` | Verarbeitet die Funktion resolve name token. |
| public | `static parseList()` | Verarbeitet die Funktion parse list. |
| public | `static parseAssignments()` | Verarbeitet die Funktion parse assignments. |
| public | `static parseWhere()` | Verarbeitet die Funktion parse where. |
| public | `static rowMatch()` | Verarbeitet die Funktion row match. |
| public | `static sortRows()` | Verarbeitet die Funktion sort rows. |
| public | `is_numeric()` | Verarbeitet die Funktion is_numeric. |
| public | `static getRows()` | Verarbeitet die Funktion get rows. |
| public | `static getTableKeys()` | Verarbeitet die Funktion get table keys. |
| public | `static selectRows()` | Verarbeitet die Funktion select rows. |
| public | `is_array()` | Verarbeitet die Funktion is_array. |
| public | `stats()` | Verarbeitet die Funktion stats. |
| public | `static stats()` | Verarbeitet die Funktion stats. |
| public | `static command()` | Verarbeitet die Funktion command. |
| public | `static run()` | Verarbeitet die Funktion run. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
