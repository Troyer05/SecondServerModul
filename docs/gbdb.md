# Klasse GBDB

## Zweck

`GBDB` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/gbdb_sys.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static rootPath()` | Verarbeitet die Funktion root path. |
| private | `static schemaPath()` | Verarbeitet die Funktion schema path. |
| private | `static readSchema()` | Verarbeitet die Funktion read schema. |
| private | `static writeSchema()` | Verarbeitet die Funktion write schema. |
| private | `static setSchemaTable()` | Verarbeitet die Funktion set schema table. |
| private | `static dropSchemaTable()` | Verarbeitet die Funktion drop schema table. |
| private | `static dropSchemaDatabase()` | Verarbeitet die Funktion drop schema database. |
| private | `static autoCompact()` | Verarbeitet die Funktion auto compact. |
| private | `static nameToken()` | Verarbeitet die Funktion name token. |
| private | `static dbIndexFile()` | Verarbeitet die Funktion db index file. |
| private | `static tableIndexFileByDbToken()` | Verarbeitet die Funktion table index file by db token. |
| private | `static readIndex()` | Verarbeitet die Funktion read index. |
| private | `static writeIndex()` | Verarbeitet die Funktion write index. |
| private | `static getDbToken()` | Verarbeitet die Funktion get db token. |
| private | `static getTableToken()` | Verarbeitet die Funktion get table token. |
| private | `static dropTableFromIndex()` | Verarbeitet die Funktion drop table from index. |
| private | `static removeTableIndexIfExists()` | Verarbeitet die Funktion remove table index if exists. |
| private | `static makePath()` | Verarbeitet die Funktion make path. |
| private | `static ini()` | Verarbeitet die Funktion ini. |
| private | `static writeTable()` | Verarbeitet die Funktion write table. |
| private | `static lockFileForTable()` | Verarbeitet die Funktion lock file for table. |
| private | `static metaFileForTable()` | Verarbeitet die Funktion meta file for table. |
| private | `static appendFileForTable()` | Verarbeitet die Funktion append file for table. |
| private | `static withTableLock()` | Verarbeitet die Funktion with table lock. |
| private | `static readMeta()` | Verarbeitet die Funktion read meta. |
| private | `static writeMeta()` | Verarbeitet die Funktion write meta. |
| private | `static isHeaderRow()` | Verarbeitet die Funktion is header row. |
| private | `static ensureHeader()` | Verarbeitet die Funktion ensure header. |
| private | `static buildRowFromHeader()` | Verarbeitet die Funktion build row from header. |
| private | `static appendOp()` | Verarbeitet die Funktion append op. |
| private | `static readAppendOps()` | Verarbeitet die Funktion read append ops. |
| private | `static applyOps()` | Verarbeitet die Funktion apply ops. |
| public | `static createDatabase()` | Verarbeitet die Funktion create database. |
| public | `static deleteDatabase()` | Verarbeitet die Funktion delete database. |
| public | `static createTable()` | Verarbeitet die Funktion create table. |
| public | `file_exists()` | Verarbeitet die Funktion file_exists. |
| public | `static addColumn()` | Verarbeitet die Funktion add column. |
| public | `file_exists()` | Verarbeitet die Funktion file_exists. |
| public | `static deleteTable()` | Verarbeitet die Funktion delete table. |
| public | `file_exists()` | Verarbeitet die Funktion file_exists. |
| public | `static insertData()` | Verarbeitet die Funktion insert data. |
| public | `self()` | Verarbeitet die Funktion self. |
| public | `static deleteData()` | Verarbeitet die Funktion delete data. |
| public | `self()` | Verarbeitet die Funktion self. |
| public | `static editData()` | Verarbeitet die Funktion edit data. |
| public | `self()` | Verarbeitet die Funktion self. |
| public | `static getData()` | Verarbeitet die Funktion get data. |
| public | `static elementExists()` | Verarbeitet die Funktion element exists. |
| public | `static listDBs()` | Verarbeitet die Funktion list d bs. |
| public | `is_dir()` | Verarbeitet die Funktion is_dir. |
| public | `static listTables()` | Verarbeitet die Funktion list tables. |
| public | `static compactTable()` | Verarbeitet die Funktion compact table. |
| public | `self()` | Verarbeitet die Funktion self. |
| public | `static deleteAll()` | Verarbeitet die Funktion delete all. |
| public | `static nextID()` | Verarbeitet die Funktion next i d. |
| public | `self()` | Verarbeitet die Funktion self. |
| public | `static getKeys()` | Verarbeitet die Funktion get keys. |
| public | `static query()` | Führt eine GreenQL-Abfrage aus. |
| public | `static runScript()` | Führt ein Script aus und gibt das Ergebnis zurück. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
