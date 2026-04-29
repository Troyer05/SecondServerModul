# Klasse mRootUpdate

## Zweck

`mRootUpdate` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/plugins/mroot.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static root()` | Verarbeitet die Funktion root. |
| private | `static server()` | Verarbeitet die Funktion server. |
| private | `static auth()` | Verarbeitet Auth-Aktionen. |
| private | `static normalizeVersion()` | Verarbeitet die Funktion normalize version. |
| private | `static newer()` | Verarbeitet die Funktion newer. |
| private | `static value()` | Verarbeitet die Funktion value. |
| private | `static cleanResponse()` | Verarbeitet die Funktion clean response. |
| private | `static fetchRemote()` | Verarbeitet die Funktion fetch remote. |
| private | `static cachePath()` | Verarbeitet die Funktion cache path. |
| private | `static readCache()` | Verarbeitet die Funktion read cache. |
| private | `static writeCache()` | Verarbeitet die Funktion write cache. |
| private | `static fetch()` | Verarbeitet die Funktion fetch. |
| public | `static check()` | Verarbeitet die Funktion check. |
| public | `static needUpdate()` | Verarbeitet die Funktion need update. |
| public | `static latestVersion()` | Verarbeitet die Funktion latest version. |
| public | `static changelog()` | Verarbeitet die Funktion changelog. |
| public | `static updateUrl()` | Verarbeitet die Funktion update url. |
| private | `static ensureDir()` | Verarbeitet die Funktion ensure dir. |
| private | `static rrmdir()` | Verarbeitet die Funktion rrmdir. |
| private | `static normalizePath()` | Verarbeitet die Funktion normalize path. |
| private | `static isPreserved()` | Verarbeitet die Funktion is preserved. |
| private | `static isIgnoredForBackup()` | Verarbeitet die Funktion is ignored for backup. |
| private | `static download()` | Verarbeitet die Funktion download. |
| private | `static extractZip()` | Verarbeitet die Funktion extract zip. |
| private | `static backup()` | Verarbeitet die Funktion backup. |
| private | `static detectReleaseRoot()` | Verarbeitet die Funktion detect release root. |
| private | `static copyRelease()` | Verarbeitet die Funktion copy release. |
| private | `static updateLocalVersion()` | Verarbeitet die Funktion update local version. |
| private | `static refreshUpdateCache()` | Verarbeitet die Funktion refresh update cache. |
| private | `static findSchemaFile()` | Verarbeitet die Funktion find schema file. |
| private | `static migrateGBDBSchema()` | Verarbeitet die Funktion migrate g b d b schema. |
| public | `static update()` | Verarbeitet die Funktion update. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
