# Klasse FileTool

## Zweck

`FileTool` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/file_tool.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static exists()` | Verarbeitet die Funktion exists. |
| public | `static read()` | Verarbeitet die Funktion read. |
| public | `static write()` | Verarbeitet die Funktion write. |
| public | `static readJson()` | Verarbeitet die Funktion read json. |
| public | `static writeJson()` | Verarbeitet die Funktion write json. |
| public | `static delete()` | Löscht Daten aus der angegebenen Quelle. |
| public | `static copyDir()` | Verarbeitet die Funktion copy dir. |
| public | `static deleteOldFiles()` | Verarbeitet die Funktion delete old files. |
| public | `static dirSize()` | Verarbeitet die Funktion dir size. |
| public | `static listFiles()` | Verarbeitet die Funktion list files. |
| public | `static backupDir()` | Verarbeitet die Funktion backup dir. |
| private | `static ensureDir()` | Verarbeitet die Funktion ensure dir. |
| public | `static listDirs()` | Verarbeitet die Funktion list dirs. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
