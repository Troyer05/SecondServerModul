# GBDB
## Zweck
`GBDB` ist die dateibasierte JSON-Datenbank des Frameworks. Sie verwaltet Bases, Tabellen, Spalten, IDs, Append-Logs, Locks, Meta-Dateien und Schema-Synchronisierung ohne externen SQL-Server.
## Datei und Einbindung
- Klasse: `GBDB`
- Datei: `assets/php/inc/gbdb_framework/core/gbdb_sys.php`
- Wird normalerweise über `assets/php/inc/gbdb_framework/gbdb.php` oder über `assets/php/inc/.config/_config.inc.php` geladen.

## Konstanten
| Konstante | Zweck / Wert |
|---|---|
| `SCHEMA_FILE` | `"assets/php/inc/gbdb_framework/json/schema.json"` |

## Arbeitsweise
Die Klasse wird überwiegend statisch genutzt. Öffentliche Methoden sind die stabile API für Projektcode. Private/protected Methoden sind interne Bausteine und sollten nicht direkt aus Anwendungen heraus verwendet werden.

Typische Aufrufkette:

1. Framework-Konfiguration laden.
2. Optional benötigte Initialisierung ausführen.
3. Öffentliche Methode der Klasse nutzen.
4. Rückgabewert auf Fehler/Leere prüfen.

## Öffentliche API
| Methode | Rückgabe | Beschreibung |
|---|---:|---|
| `createDatabase(string $name)` | `bool` | Erstellt eine neue Ressource. |
| `deleteDatabase(string $name)` | `bool` | Löscht einen Eintrag oder entfernt eine Ressource. |
| `createTable(string $database, string $table, array $cols)` | `bool` | Erstellt eine neue Ressource. |
| `addColumn(string $database, string $table, string $column, mixed $default = "")` | `bool` | Kapselt die Fachlogik für `addColumn()` innerhalb dieser Klasse. |
| `deleteTable(string $database, string $table)` | `bool` | Löscht einen Eintrag oder entfernt eine Ressource. |
| `insertData(string $database, string $table, mixed $data)` | `int` | Fügt neue Daten ein und gibt je nach Modul eine ID oder Erfolgsstatus zurück. |
| `deleteData(string $database, string $table, mixed $where, mixed $is)` | `bool` | Löscht einen Eintrag oder entfernt eine Ressource. |
| `editData(string $database, string $table, mixed $where, mixed $is, mixed $newData)` | `bool` | Aktualisiert vorhandene Daten anhand eines Suchkriteriums. |
| `getData(string $database, string $table, bool $filter = false, mixed $where = "", mixed $is = "")` | `mixed` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `elementExists(string $database, string $table, mixed $where, mixed $is)` | `bool` | Kapselt die Fachlogik für `elementExists()` innerhalb dieser Klasse. |
| `listDBs()` | `array` | Listet vorhandene Ressourcen auf. |
| `listTables(string $database, bool $descending = false)` | `array` | Listet vorhandene Ressourcen auf. |
| `compactTable(string $database, string $table)` | `bool` | Kapselt die Fachlogik für `compactTable()` innerhalb dieser Klasse. |
| `deleteAll(string $database)` | `bool` | Löscht einen Eintrag oder entfernt eine Ressource. |
| `nextID(string $database, string $table)` | `int` | Kapselt die Fachlogik für `nextID()` innerhalb dieser Klasse. |
| `getKeys(string $database, string $table)` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `query(string $script, array $ctx = [], array $params = [])` | `array` | Führt eine GreenQL-Abfrage aus. |
| `runScript(string $path, array $params = [], array $ctx = [])` | `array` | Führt ein Script aus und gibt das Ergebnis zurück. |

## Beispiele
```php
include 'assets/php/inc/.config/_config.inc.php';

GBDB::createDatabase('main');
GBDB::createTable('main', 'users', ['uid', 'username', 'email']);

$id = GBDB::insertData('main', 'users', [
    'uid' => 'u001',
    'username' => 'markus',
    'email' => 'markus@example.test'
]);

$user = GBDB::getData('main', 'users', true, 'uid', 'u001');
GBDB::editData('main', 'users', 'uid', 'u001', ['username' => 'Markus']);
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `private static rootPath() : string` – Kapselt die Fachlogik für `rootPath()` innerhalb dieser Klasse.
- `private static schemaPath() : string` – Kapselt die Fachlogik für `schemaPath()` innerhalb dieser Klasse.
- `private static readSchema() : array` – Liest Inhalt aus Datei, Request oder Speicher.
- `private static writeSchema(array $schema) : bool` – Schreibt Inhalt in Datei, Datenbank oder Speicher.
- `private static setSchemaTable(string $database, string $table, array $cols) : void` – Setzt einen Wert oder Zustand in der jeweiligen Komponente.
- `private static dropSchemaTable(string $database, string $table) : void` – Kapselt die Fachlogik für `dropSchemaTable()` innerhalb dieser Klasse.
- `private static dropSchemaDatabase(string $database) : void` – Kapselt die Fachlogik für `dropSchemaDatabase()` innerhalb dieser Klasse.
- `private static autoCompact(string $database, string $table) : void` – Kapselt die Fachlogik für `autoCompact()` innerhalb dieser Klasse.
- `private static nameToken(string $plain, string $ns = 'g') : string` – Kapselt die Fachlogik für `nameToken()` innerhalb dieser Klasse.
- `private static dbIndexFile() : string` – Kapselt die Fachlogik für `dbIndexFile()` innerhalb dieser Klasse.
- `private static tableIndexFileByDbToken(string $dbToken) : string` – Kapselt die Fachlogik für `tableIndexFileByDbToken()` innerhalb dieser Klasse.
- `private static readIndex(string $file) : array` – Liest Inhalt aus Datei, Request oder Speicher.
- `private static writeIndex(string $file, array $map) : bool` – Schreibt Inhalt in Datei, Datenbank oder Speicher.
- `private static getDbToken(string $dbPlain, bool $ensure = false) : ?string` – Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück.
- `private static getTableToken(string $dbPlain, string $tablePlain, bool $ensure = false) : ?string` – Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück.
- `private static dropTableFromIndex(string $dbPlain, string $tablePlain) : void` – Kapselt die Fachlogik für `dropTableFromIndex()` innerhalb dieser Klasse.
- `private static removeTableIndexIfExists(string $dbPlain) : void` – Kapselt die Fachlogik für `removeTableIndexIfExists()` innerhalb dieser Klasse.
- `private static makePath(string $database, string $table, bool $ensure = false) : string` – Kapselt die Fachlogik für `makePath()` innerhalb dieser Klasse.
- `private static ini(string $file) : array` – Kapselt die Fachlogik für `ini()` innerhalb dieser Klasse.
- `private static writeTable(string $file, array $db) : bool` – Schreibt Inhalt in Datei, Datenbank oder Speicher.
- `private static lockFileForTable(string $database, string $table, bool $ensure = false) : string` – Kapselt die Fachlogik für `lockFileForTable()` innerhalb dieser Klasse.
- `private static metaFileForTable(string $database, string $table, bool $ensure = false) : string` – Meta-Datei pro Tabelle! - plain:  __meta__<table>.json - crypt:  token('__meta__|<tblToken>').db
- `private static appendFileForTable(string $database, string $table, bool $ensure = false) : string` – Append-Datei pro Tabelle! - plain: __append__<table>.json (optional, aber wir halten es konsistent) - crypt: token('__append__|<tblToken>').db
- `private static withTableLock(string $lockFile, callable $fn) : mixed` – Kapselt die Fachlogik für `withTableLock()` innerhalb dieser Klasse.
- `private static readMeta(string $metaFile) : array` – Liest Inhalt aus Datei, Request oder Speicher.
- `private static writeMeta(string $metaFile, array $meta) : bool` – Schreibt Inhalt in Datei, Datenbank oder Speicher.
- `private static isHeaderRow(array $row) : bool` – Kapselt die Fachlogik für `isHeaderRow()` innerhalb dieser Klasse.
- `private static ensureHeader(array &$tableData, array $cols) : void` – Kapselt die Fachlogik für `ensureHeader()` innerhalb dieser Klasse.
- `private static buildRowFromHeader(array $header, array $data, int $id) : array` – Kapselt die Fachlogik für `buildRowFromHeader()` innerhalb dieser Klasse.
- `private static appendOp(string $appendFile, array $op) : bool` – Kapselt die Fachlogik für `appendOp()` innerhalb dieser Klasse.
- … weitere 2 interne Methoden.

## Best Practices
- Öffentliche Methoden bevorzugen und interne Dateipfade nicht hart im Anwendungscode duplizieren.
- Rückgaben immer validieren, bevor sie in HTML, API-Antworten oder weitere DB-Operationen fließen.
- Für neue Features erst Schema/Tabellen sauber anlegen und danach Daten schreiben.
- Für produktive Systeme Backups, Schreibrechte und Authentifizierung vor dem Rollout testen.

## Zusatzhinweise
GBDB speichert Tabellen als JSON-Dateien mit Header-Zeile (`id=-1`), Meta-Datei, Append-Datei und Lock-Datei. Das System ist für kleine bis mittlere Webapps gedacht, bei sehr großen Datenmengen sollte SQL geprüft werden.

## Integration in eigene Projekte

Beim Einbau in neue Projekte sollte diese Komponente nicht isoliert betrachtet werden. Fast alle Framework-Klassen hängen indirekt an der zentralen Konfiguration `Vars` und an der gemeinsamen Einbindung über `_config.inc.php`. Dadurch bleibt der Anwendungscode kurz, aber Konfigurationsfehler fallen oft erst zur Laufzeit auf. Für saubere Projekte empfiehlt es sich deshalb, zuerst eine kleine Setup- oder Healthcheck-Seite anzulegen, die prüft, ob die Klasse geladen ist, ob die benötigten Pfade existieren und ob Schreib-/Leserechte stimmen.

Ein typischer Integrationsablauf sieht so aus:

1. `_config.inc.php` laden.
2. Benötigte Konstanten und `Vars`-Werte prüfen.
3. Falls nötig Initialisierung ausführen.
4. Einen einfachen Leseaufruf testen.
5. Einen einfachen Schreibaufruf testen.
6. Fehlerfälle testen, nicht nur den Erfolgsfall.

## Test-Checkliste

- Läuft der Code lokal und auf dem Server mit derselben PHP-Version?
- Sind alle benötigten Core-Dateien wirklich geladen?
- Sind Rückgaben dokumentiert und werden sie im Anwendungscode geprüft?
- Gibt es einen Test mit leerer Eingabe, ungültiger Eingabe und gültiger Eingabe?
- Sind Dateipfade relativ zum Projekt-Root und nicht zum aktuellen Browserpfad gedacht?
- Sind produktive Secrets aus Logs, Fehlermeldungen und Screenshots entfernt?
- Funktioniert der Ablauf nach einem frischen Upload ohne manuelles Nachbessern der Rechte?

## Wartung und Erweiterung

Wenn diese Klasse erweitert wird, sollte jede neue öffentliche Methode sofort in dieser Dokumentation auftauchen. Bei Klassen, die mit GBDB arbeiten, muss außerdem geprüft werden, ob neue Tabellen oder Spalten in `schema.json` bzw. `schema_v2.json` berücksichtigt werden müssen. Bei Klassen, die Remote-Requests ausführen, sollten Fehlermeldungen immer so formuliert werden, dass Entwickler das Problem finden können, ohne dabei Auth-Tokens oder API-Keys offenzulegen.

## Praktische Hinweise für andere Entwickler

Dieses Framework folgt bewusst einem sehr direkten PHP-Stil. Viele Methoden sind statisch und dadurch einfach aufzurufen. Der Nachteil ist, dass falsche globale Konfigurationen schneller Auswirkungen auf mehrere Klassen haben. Andere Entwickler sollten deshalb nicht nur die einzelne Methode lesen, sondern auch die umgebenden Dateien `ENV.php`, `_config.inc.php` und bei Remote-Funktionen `backend.php` prüfen.
