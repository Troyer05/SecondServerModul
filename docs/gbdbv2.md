# GBDBv2 – Instanzen und Mandanten
## Zweck
`GBDBv2` erweitert GBDB um Instanzen. Dadurch können mehrere getrennte Datenräume auf demselben Framework laufen, ohne Tabellen/Bases manuell zu vermischen.
## Datei und Einbindung
- Klasse: `GBDBv2`
- Datei: `assets/php/inc/gbdb_framework/core/gbdb_sys_v2.php`
- Wird normalerweise über `assets/php/inc/gbdb_framework/gbdb.php` oder über `assets/php/inc/.config/_config.inc.php` geladen.

## Konstanten
| Konstante | Zweck / Wert |
|---|---|
| `SCHEMA_FILE` | `"assets/php/inc/gbdb_framework/json/schema_v2.json"` |

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
| `setInstance(string $instance)` | `void` | Setzt die aktive Instanz. |
| `instance(string $instance)` | `void` | Alias für setInstance. |
| `getInstance()` | `string` | Gibt die aktive Instanz zurück. |
| `createInstance(string $name)` | `bool` | Erstellt eine Instanz. |
| `deleteInstance(string $name, bool $force = false)` | `bool` | Löscht eine Instanz. |
| `listInstances()` | `array` | Listet alle Instanzen. |
| `createDatabase(string $name)` | `bool` | Erstellt eine Datenbank innerhalb der aktiven Instanz. |
| `deleteDatabase(string $name)` | `bool` | Löscht eine leere Datenbank. |
| `createTable(string $database, string $table, array $cols)` | `bool` | Erstellt eine Tabelle. |
| `addColumn(string $database, string $table, string $column, mixed $default = "")` | `bool` | Fügt eine Spalte hinzu. |
| `deleteTable(string $database, string $table)` | `bool` | Löscht eine Tabelle. |
| `insertData(string $database, string $table, mixed $data)` | `int` | Fügt Daten ein. |
| `deleteData(string $database, string $table, mixed $where, mixed $is)` | `bool` | Löscht Daten. |
| `editData(string $database, string $table, mixed $where, mixed $is, mixed $newData)` | `bool` | Bearbeitet Daten. |
| `getData(string $database, string $table, bool $filter = false, mixed $where = "", mixed $is = "")` | `mixed` | Holt Daten. |
| `elementExists(string $database, string $table, mixed $where, mixed $is)` | `bool` | Prüft, ob ein Element existiert. |
| `listDBs()` | `array` | Listet Datenbanken der aktiven Instanz. |
| `listTables(string $database, bool $descending = false)` | `array` | Listet Tabellen einer Datenbank. |
| `compactTable(string $database, string $table)` | `bool` | Komprimiert eine Tabelle. |
| `deleteAll(string $database)` | `bool` | Löscht eine komplette Datenbank innerhalb der aktiven Instanz. |
| `nextID(string $database, string $table)` | `int` | Gibt die nächste ID zurück. |
| `getKeys(string $database, string $table)` | `array` | Gibt die Keys einer Tabelle zurück. |
| `query(string $script, array $ctx = [], array $params = [])` | `array` | Führt eine GreenQL-Abfrage aus. |
| `runScript(string $path, array $params = [], array $ctx = [])` | `array` | Führt ein GreenQL-Script aus. |

## Beispiele
```php
include 'assets/php/inc/.config/_config.inc.php';

GBDBv2::createInstance('kunde_a');
GBDBv2::setInstance('kunde_a');
GBDBv2::createDatabase('main');
GBDBv2::createTable('main', 'settings', ['key', 'value']);
GBDBv2::insertData('main', 'settings', ['key' => 'theme', 'value' => 'dark']);
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `private static rootPath() : string` – Gibt den Projekt-Root zurück.
- `private static schemaPath() : string` – Gibt den Pfad zur Schema-Datei zurück.
- `private static instanceName() : string` – Gibt die aktive Instanz bereinigt zurück.
- `private static readSchema() : array` – Liest die Schema-Datei.
- `private static writeSchema(array $schema) : bool` – Schreibt die Schema-Datei.
- `private static setSchemaTable(string $database, string $table, array $cols) : void` – Setzt eine Tabelle im Schema.
- `private static dropSchemaTable(string $database, string $table) : void` – Entfernt eine Tabelle aus dem Schema.
- `private static dropSchemaDatabase(string $database) : void` – Entfernt eine Datenbank aus dem Schema.
- `private static dropSchemaInstance(string $instance) : void` – Entfernt eine Instanz aus dem Schema.
- `private static autoCompact(string $database, string $table) : void` – Komprimiert eine Tabelle automatisch.
- `private static nameToken(string $plain, string $ns = "g") : string` – Erzeugt einen sicheren Namen.
- `private static instanceIndexFile() : string` – Gibt die globale Instanz-Index-Datei zurück.
- `private static dbIndexFileByInstanceToken(string $instanceToken) : string` – Gibt die Datenbank-Index-Datei einer Instanz zurück.
- `private static tableIndexFileByTokens(string $instanceToken, string $dbToken) : string` – Gibt die Tabellen-Index-Datei einer Datenbank zurück.
- `private static readIndex(string $file) : array` – Liest eine Index-Datei.
- `private static writeIndex(string $file, array $map) : bool` – Schreibt eine Index-Datei.
- `private static getInstanceToken(string $instancePlain, bool $ensure = false) : ?string` – Gibt den Token einer Instanz zurück.
- `private static getDbToken(string $dbPlain, bool $ensure = false) : ?string` – Gibt den Token einer Datenbank zurück.
- `private static getTableToken(string $dbPlain, string $tablePlain, bool $ensure = false) : ?string` – Gibt den Token einer Tabelle zurück.
- `private static dropTableFromIndex(string $dbPlain, string $tablePlain) : void` – Entfernt eine Tabelle aus dem Tabellen-Index.
- `private static dropDatabaseFromIndex(string $dbPlain) : void` – Entfernt eine Datenbank aus dem Datenbank-Index.
- `private static dropInstanceFromIndex(string $instancePlain) : void` – Entfernt eine Instanz aus dem Instanz-Index.
- `private static instancePath(bool $ensure = false) : string` – Gibt den Pfad der aktuellen Instanz zurück.
- `private static makePath(string $database, string $table, bool $ensure = false) : string` – Baut den Tabellenpfad.
- `private static ini(string $file) : array` – Liest eine JSON/DB-Datei.
- `private static writeTable(string $file, array $db) : bool` – Schreibt eine Tabelle atomar.
- `private static lockFileForTable(string $database, string $table, bool $ensure = false) : string` – Gibt die Lock-Datei einer Tabelle zurück.
- `private static metaFileForTable(string $database, string $table, bool $ensure = false) : string` – Gibt die Meta-Datei einer Tabelle zurück.
- `private static appendFileForTable(string $database, string $table, bool $ensure = false) : string` – Gibt die Append-Datei einer Tabelle zurück.
- `private static withTableLock(string $lockFile, callable $fn) : mixed` – Führt eine Aktion mit Tabellen-Lock aus.
- … weitere 8 interne Methoden.

## Best Practices
- Öffentliche Methoden bevorzugen und interne Dateipfade nicht hart im Anwendungscode duplizieren.
- Rückgaben immer validieren, bevor sie in HTML, API-Antworten oder weitere DB-Operationen fließen.
- Für neue Features erst Schema/Tabellen sauber anlegen und danach Daten schreiben.
- Für produktive Systeme Backups, Schreibrechte und Authentifizierung vor dem Rollout testen.

## Zusatzhinweise
Instanzen sind Mandantenräume. Eine Instanz sollte pro Kunde, Projekt oder isolierter Umgebung genutzt werden. Reservierte Namen und Rechteprüfung laufen zusätzlich in GreenQL UI Helpern.

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
