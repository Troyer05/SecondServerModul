# Klasse FS

## Zweck

`FS` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/fs.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static createFolder()` | Verarbeitet die Funktion create folder. |
| public | `static write()` | Verarbeitet die Funktion write. |
| public | `read()` | Verarbeitet die Funktion read. |
| public | `static deleteDirectory()` | Verarbeitet die Funktion delete directory. |
| public | `static getFolderSize()` | Verarbeitet die Funktion get folder size. |
| public | `static deleteFiles()` | Verarbeitet die Funktion delete files. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
