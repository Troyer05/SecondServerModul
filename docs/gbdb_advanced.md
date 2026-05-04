# GBDB Advanced Engine Features

Diese Datei dokumentiert die neuen Engine-Bausteine, die GBDB und GBDBv2 als stärker belastbare dateibasierte Datenbank erweitern. Die Features sind bewusst als zusätzliche Public-Methoden umgesetzt, damit bestehende Projekte nicht brechen.

## Struktur

Die Hauptklassen bleiben schlank:

- `core/gbdb_sys.php` lädt `gbdb_system/gbdb/*`
- `core/gbdb_sys_v2.php` lädt `gbdb_system/gbdb_v2/*`
- `core/greenql_engine.php` lädt `gbdb_system/greenql/*`
- `core/greenql_engine_v2.php` lädt `gbdb_system/greenql_v2/*`

Neue Advanced-Traits:

- `gbdb/gbdb_advanced.trait.php`
- `gbdb_v2/gbdbv2_advanced.trait.php`

## Indexe

Bereits vorhandene Indexe bleiben über `createIndex`, `dropIndex`, `rebuildIndexes` und `listIndexes` verfügbar. Zusätzlich liefert `queryPlan()` nun eine klare Einschätzung, ob ein Zugriff per Index oder per Replay/Scan läuft.

```php
GBDB::createIndex("main", "users", "email");
$plan = GBDB::queryPlan("main", "users", "email", "demo@example.test");
```

## Partitionierung

Partitionen sind logisch getrennte physische Tabellen nach dem Schema `<table>__p_<partition>`.

```php
GBDB::insertPartitioned("main", "events", "2026_05", [
    "title" => "Demo",
    "created_at" => time()
]);

$may = GBDB::getPartition("main", "events", "2026_05");
```

Das ist bewusst einfach gehalten: schnell, transparent und kompatibel mit dem bestehenden Dateisystem.

## Transaktionen

Transaktionen sind weiterhin über `begin`, `commit`, `rollback` und `transactionStatus` verfügbar. `bulkInsert()` kann optional automatisch eine Transaktion kapseln.

```php
$result = GBDB::bulkInsert("main", "users", [
    ["name" => "Ada"],
    ["name" => "Grace"]
]);
```

## WAL / Recovery

Append-Operationen werden weiterhin journalisiert. Mit `recoverTable()` kann eine Tabelle gezielt aus committed WAL-Einträgen replayed werden.

```php
$status = GBDB::recoverTable("main", "users");
```

## Volltextsuche

`fulltext_search()` durchsucht wahlweise alle skalaren Felder oder nur übergebene Spalten. Treffer enthalten einen Score.

```php
$hits = GBDB::fulltext_search("main", "articles", "museum audio guide", ["title", "body"], 20);
```

## ACL / Rechtesystem

ACLs liegen in den Tabellen-Metadaten. Das Framework erzwingt sie nicht automatisch in jedem Projekt, sondern stellt eine saubere Engine-Basis bereit, die UIs/API-Gates abfragen können.

```php
GBDB::grantAcl("main", "users", "admin", "read");
GBDB::grantAcl("main", "users", "admin", "write");

if (GBDB::checkAcl("main", "users", "admin", "write")) {
    // Zugriff erlauben
}
```

## Snapshots / Backups

Snapshots bleiben über `snapshot()` und `restoreSnapshot()` verfügbar. Sie sichern Daten-, Meta- und Append-Dateien tabellenbezogen.

```php
$id = GBDB::snapshot("main", "users", "before_import");
GBDB::restoreSnapshot("main", "users", $id);
```

## Monitoring

`monitor()` fasst Metadaten, Dateigrößen, Append-Status, Indexe, Constraints und Health-Check zusammen.

```php
$state = GBDB::monitor("main", "users");
```

## Streaming / Bulk

Für große Tabellen gibt es `streamRows()` und `bulkInsert()`.

```php
GBDB::streamRows("main", "logs", function(array $row, int $i) {
    // z.B. Export schreiben
}, 1000);
```

## Query Planner

`queryPlan()` zeigt die geplante Strategie.

```php
$plan = GBDB::queryPlan("main", "users", "email", "demo@example.test");
```

Typische Strategien:

- `index_lookup`: Index kann verwendet werden.
- `append_replay_scan`: Append-Log muss zuerst angewendet werden.

## Cache

`getCachedData()` bietet einen kleinen Runtime-Cache pro Request/Prozess. Schreiboperationen invalidieren den Runtime-Cache der betroffenen Tabelle.

```php
$rows = GBDB::getCachedData("main", "settings", false, "", "", 10);
GBDB::clearRuntimeCache("main", "settings");
```

## Migrationssystem

`migrate()` führt eine Migration pro Tabelle genau einmal aus und merkt die ID in den Metadaten.

```php
GBDB::migrate("main", "users", "2026_05_add_display_name", function($db, $table) {
    return GBDB::addColumn($db, $table, "display_name", "");
});
```

## Audit / DSGVO

Audit-Logs werden in `_gbdb_audit/audit_log` gespeichert. Für DSGVO gibt es Export und Redaction.

```php
$export = GBDB::gdprExport("main", "users", "email", "demo@example.test");
GBDB::gdprRedact("main", "users", "email", "demo@example.test", ["email", "name"]);
```

## Shards

Shards sind physische Tabellen nach dem Schema `<table>__s_<slot>`. Die Slot-Auswahl basiert auf einem stabilen CRC32-Hash.

```php
GBDB::insertSharded("main", "messages", "user-123", [
    "uid" => "user-123",
    "body" => "Hallo"
], 32);

$rows = GBDB::getShard("main", "messages", "user-123", 32);
```

## Pages und Cursor

Für UI-Listen gibt es `page()`. Für API- oder Sync-Flows gibt es `cursor()` mit Cursor-Token.

```php
$page = GBDB::page("main", "users", 1, 50);
$first = GBDB::cursor("main", "users", 100);
$next = GBDB::cursor("main", "users", 100, $first["cursor"]);
```

## Append Logs

`appendLog()` liefert die letzten Append-Operationen einer Tabelle.

```php
$log = GBDB::appendLog("main", "users", 25);
```

## GreenQL-Erweiterungen

Neue GreenQL-Kommandos:

```gql
MONITOR users;
RECOVER users;
PAGE users PAGE 1 LIMIT 25;
CURSOR users LIMIT 25;
FULLTEXT "ada lovelace" FROM users COLUMNS name,note LIMIT 10;
GRANT admin read ON users;
REVOKE admin read ON users;
```

Diese Kommandos funktionieren in GreenQL und GreenQLv2. GBDBv2 nutzt dabei automatisch die gesetzte Instance.

## migrateGBDB(fromPath, toPath)

`GBDB::migrateGBDB(string $fromPath, string $toPath): array` migriert ältere GBDB-Dateistrukturen in eine aktuelle GBDB-Struktur.

Unterstützte Quellformen:

- direkter DB-Ordner
- Projektwurzel mit `DB/`
- Projektwurzel mit `assets/DB/`
- Projektwurzel mit `assets/DB/GBDB/`

Beispiel:

```php
$report = GBDB::migrateGBDB(__DIR__ . '/old_db', __DIR__ . '/assets/DB/GBDB');
```

Wenn der Zielpfad dem aktuellen `Vars::DB_PATH()` entspricht, importiert die Funktion über die normale GBDB-API. Dadurch bleiben aktuelle Sicherheitsmechanismen wie Tokenisierung/Verschlüsselung erhalten.

Wenn ein anderer Zielpfad verwendet wird, schreibt die Funktion eine portable, unverschlüsselte Zielstruktur mit Daten-, Meta- und Append-Dateien. Das eignet sich besonders für Backups, Offline-Migrationen und manuelle Prüfung alter Datenstände.

Rückgabe:

```php
[
    'ok' => true,
    'from' => '/quelle/DB',
    'to' => '/ziel/GBDB/',
    'live_api' => false,
    'databases' => 1,
    'tables' => 3,
    'rows' => 120,
    'skipped' => [],
    'errors' => []
]
```

Hinweis: Bei sehr alten verschlüsselten Daten kann die Funktion Inhalte lesen, wenn `Crypt::decode()` mit dem aktuellen Schlüssel kompatibel ist. Verschlüsselte alte Datei- oder Ordnernamen ohne Index können technisch nicht sicher in Klartextnamen zurückübersetzt werden.
