# GBDB – dateibasierte Datenbank v1

## Zweck

`GBDB` ist die klassische lokale Datenbankklasse des Frameworks. Sie speichert Daten dateibasiert unter `Vars::DB_PATH()`, normalerweise also unter `assets/DB/`. Die Klasse ist für Projekte gedacht, die einfache Tabellen, schnelle CRUD-Operationen, automatische Schemapflege und robuste Dateioperationen brauchen, ohne sofort eine SQL-Datenbank einzurichten.

## Grundidee

Eine GBDB besteht aus:

- **Database/Base**: logische Datenbank, z. B. `main`, `userdb`, `tickets`.
- **Table**: Tabelle innerhalb einer Base, z. B. `users`, `settings`, `logs`.
- **Rows**: Arrays mit automatischer `id`.
- **Header Row**: interne Zeile mit `id = -1`, die die Spaltenstruktur hält.
- **Meta-Datei**: technische Informationen wie Version, Constraints, Indizes, Checksummen.
- **Append/WAL-Datei**: zwischengespeicherte Operationen für schnelle Schreibvorgänge.
- **Schema JSON**: automatische Dokumentation/Strukturablage in `schema.json`.

## Warum dateibasiert?

Die Intention ist Kontrolle und Einfachheit. Für viele interne Tools, kleine SaaS-Projekte oder Adminbereiche ist eine dateibasierte DB ausreichend und schneller aufzusetzen als SQL. Gleichzeitig bringt GBDB inzwischen Funktionen mit, die man sonst eher aus größeren Systemen kennt: Locks, Snapshots, Health Checks, Constraints, Indizes und Transaktionen.

## Speicher- und Namenslogik

Datenbank- und Tabellennamen werden intern tokenisiert. Dadurch liegen Dateien nicht direkt als `main/users.db` herum, sondern unter sicheren, generierten Namen. Das erschwert direkte Manipulationen und erlaubt eine zusätzliche Trennung zwischen Anzeigename und Speichername.

Wenn `Vars::crypt_data()` aktiv ist, werden Inhalte zusätzlich über die Framework-Kryptologik kodiert.

## Minimalbeispiel

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "users", ["uid", "username", "email", "active"]);

$id = GBDB::insertData("main", "users", [
    "uid" => "u1",
    "username" => "markus",
    "email" => "markus@example.com",
    "active" => 1
]);

$user = GBDB::getData("main", "users", true, "uid", "u1");
print_r($user);
```

## CRUD-Operationen

### Datenbank erstellen

```php
GBDB::createDatabase("main");
```

Erstellt eine Base und aktualisiert interne Indexdateien.

### Tabelle erstellen

```php
GBDB::createTable("main", "products", ["sku", "name", "price", "active"]);
```

Die Spalten werden im Header und in `schema.json` hinterlegt.

### Daten einfügen

```php
$id = GBDB::insertData("main", "products", [
    "sku" => "P-001",
    "name" => "Audio Guide Basic",
    "price" => "49.00",
    "active" => 1
]);
```

Rückgabe ist die neue numerische Row-ID.

### Daten lesen

```php
$all = GBDB::getData("main", "products");
$one = GBDB::getData("main", "products", true, "sku", "P-001");
```

Mit `$filter = true` wird nach `$where == $is` gefiltert.

### Daten ändern

```php
GBDB::editData("main", "products", "sku", "P-001", [
    "price" => "59.00"
]);
```

Nur übergebene Felder werden geändert. Nicht übergebene Spalten bleiben erhalten.

### Daten löschen

```php
GBDB::deleteData("main", "products", "sku", "P-001");
```

## Schema-Automatik

`createTable`, `addColumn`, `deleteTable` und `deleteDatabase` pflegen `schema.json`. Dadurch bleibt eine technische Strukturübersicht erhalten, die für Updates, Migrationen und Dokumentation genutzt werden kann.

```php
GBDB::addColumn("main", "products", "description", "");
```

## Indizes

Indizes beschleunigen Lookups auf häufig genutzten Spalten.

```php
GBDB::createIndex("main", "products", "sku");
$indexes = GBDB::listIndexes("main", "products");
GBDB::dropIndex("main", "products", "sku");
```

Indizes werden aus den vorhandenen Rows aufgebaut und bei Bedarf neu erzeugt.

## Constraints

Constraints schützen einfache Datenregeln.

```php
GBDB::addConstraint("main", "users", "email", "unique");
GBDB::addConstraint("main", "users", "username", "required");
```

Unterstützte Typen:

| Constraint | Bedeutung |
|---|---|
| `unique` | Wert darf in der Spalte nur einmal vorkommen. |
| `required` | Wert darf nicht leer sein. |

## Snapshots und Restore

Snapshots sichern den Tabellenzustand inklusive technischer Begleitdateien.

```php
$snapshotId = GBDB::snapshot("main", "products", "before_update");
GBDB::restoreSnapshot("main", "products", $snapshotId);
```

Das ist besonders hilfreich vor Migrations- oder Update-Schritten.

## Health und Repair

```php
$health = GBDB::health("main", "products");
GBDB::repairTable("main", "products");
```

`health()` prüft Struktur, Header, Meta, Append/WAL und weitere Konsistenzpunkte. `repairTable()` versucht, wieder einen konsistenten Tabellenzustand herzustellen.

## Transaktionen

GBDB unterstützt einfache Transaktionen über Snapshots.

```php
GBDB::begin();
try {
    GBDB::insertData("main", "logs", ["msg" => "start"]);
    GBDB::editData("main", "settings", "key", "mode", ["value" => "live"]);
    GBDB::commit();
} catch (Throwable $e) {
    GBDB::rollback();
}
```

Intention: Vor mehreren zusammenhängenden Änderungen wird ein Sicherungszustand erzeugt. Bei `rollback()` wird dieser wiederhergestellt.

## GreenQL-Ausführung über GBDB

```php
$result = GBDB::query('
    ROOT main;
    PICK * FROM users LIMIT 10;
');
```

Oder aus Datei:

```php
$result = GBDB::runScript("scripts/greenql/makeUser.gql", [
    "username" => "admin"
]);
```

## Öffentliche Methoden

| Methode | Zweck |
|---|---|
| `createDatabase($name)` | Legt eine Base an. |
| `deleteDatabase($name)` | Entfernt eine Base inklusive Tabellen. |
| `createTable($database, $table, $cols)` | Legt Tabelle mit Spalten an. |
| `addColumn($database, $table, $column, $default)` | Fügt Spalte hinzu und pflegt Schema. |
| `deleteTable($database, $table)` | Entfernt Tabelle und technische Artefakte. |
| `insertData($database, $table, $data)` | Fügt eine Row ein. |
| `getData($database, $table, $filter, $where, $is)` | Liest alle oder gefilterte Rows. |
| `editData($database, $table, $where, $is, $newData)` | Aktualisiert passende Rows. |
| `deleteData($database, $table, $where, $is)` | Löscht passende Rows. |
| `elementExists($database, $table, $where, $is)` | Prüft Existenz. |
| `listDBs()` | Listet Bases. |
| `listTables($database)` | Listet Tabellen. |
| `compactTable($database, $table)` | Schreibt Append-Operationen in Hauptdatei zurück. |
| `nextID($database, $table)` | Gibt nächste Row-ID zurück. |
| `getKeys($database, $table)` | Gibt Tabellenspalten zurück. |
| `createIndex/dropIndex/listIndexes/rebuildIndexes` | Indexverwaltung. |
| `addConstraint/dropConstraint/listConstraints` | Constraint-Verwaltung. |
| `snapshot/restoreSnapshot` | Sicherung und Wiederherstellung. |
| `health/repairTable/meta` | Diagnose und Metadaten. |
| `begin/commit/rollback/transactionStatus` | Transaktionssteuerung. |
| `query/runScript` | GreenQL ausführen. |

## Best Practices

- Nutze sprechende, einfache Namen: `main`, `users`, `settings`.
- Lege Tabellen früh mit vollständiger Spaltenliste an.
- Nutze `addColumn()` statt manuell Dateien zu editieren.
- Vor größeren Updates `snapshot()` ausführen.
- Für häufige Suche nach `uid`, `email`, `slug` oder `rfid` Indizes anlegen.
- Für Login-Daten `unique` und `required` Constraints nutzen.
- Niemals Tabellen-Dateien direkt bearbeiten, wenn die App läuft.

# Erweiterte Entwicklernotizen

## Interne Tabellenstruktur genauer erklärt

GBDB speichert eine Tabelle nicht als einfache Liste von Nutzdaten, sondern als technischen Tabellenzustand. Die erste Strukturzeile ist die Header-Zeile. Sie besitzt intern `id = -1` und beschreibt die Spalten. Das hat zwei Vorteile: Erstens kann eine Tabelle auch ohne separate SQL-DDL gelesen werden. Zweitens kann GBDB beim Einfügen automatisch fehlende Felder mit leeren Standardwerten ergänzen.

Ein konzeptioneller Tabellenzustand sieht so aus:

```json
[
  {"id":-1,"uid":"uid","username":"username","email":"email"},
  {"id":1,"uid":"u1","username":"admin","email":"admin@example.com"},
  {"id":2,"uid":"u2","username":"demo","email":"demo@example.com"}
]
```

Die tatsächliche Datei kann je nach Konfiguration verschlüsselt/obfuskiert und tokenisiert sein. Entwickler sollen deshalb nie auf Dateinamen oder Rohdaten vertrauen, sondern immer die Klassenmethoden nutzen.

## Warum `getData()` manchmal Array und manchmal einzelne Row liefert

`getData()` ist bewusst flexibel gehalten. Ohne Filter liefert sie eine Liste von Rows. Mit Filter kann sie die passenden Daten nach Spalte/Wert suchen. Je nach Implementationsstand und Trefferlage sollte Code defensiv prüfen, ob eine einzelne Row oder eine Liste zurückkommt.

```php
$data = GBDB::getData("main", "users", true, "uid", "u1");

if (isset($data["uid"])) {
    // einzelner Datensatz
}

if (isset($data[0])) {
    // Liste von Datensätzen
}
```

## Empfohlene Tabellenanlage

Für stabile Anwendungen sollte jede Tabelle eine technische ID-Spalte besitzen, auch wenn GBDB selbst numerische `id`s vergibt. Beispiel:

```php
GBDB::createTable("main", "users", [
    "uid",
    "username",
    "email",
    "password",
    "active",
    "role",
    "created_at",
    "updated_at"
]);
```

Der Grund: Die interne `id` ist praktisch, aber eine eigene `uid` ist portabler, besser für APIs, stabiler für Importe und leichter mit externen Systemen zu verknüpfen.

## Import-Muster

```php
GBDB::begin();

try {
    foreach ($items as $item) {
        if (!GBDB::elementExists("main", "products", "sku", $item["sku"])) {
            GBDB::insertData("main", "products", $item);
            continue;
        }

        GBDB::editData("main", "products", "sku", $item["sku"], $item);
    }

    GBDB::commit();
} catch (Throwable $e) {
    GBDB::rollback();
    throw $e;
}
```

## Wartungsmuster

```php
foreach (GBDB::listTables("main") as $table) {
    $health = GBDB::health("main", $table);

    if (($health["ok"] ?? false) !== true) {
        GBDB::repairTable("main", $table);
    }

    GBDB::compactTable("main", $table);
}
```

## Wann GBDB nicht ideal ist

GBDB ist stark für kleine bis mittlere Projekte, interne Tools und kontrollierte SaaS-Instanzen. Für sehr große Datenmengen, hochparallele Schreiblast, komplexe Joins oder analytische Abfragen ist SQL besser geeignet. Das Framework bietet dafür `SQL` und `DatabaseBridge` als Ausweich-/Integrationsschicht.

## Migrationsstrategie

Bei neuen Versionen sollte man nicht direkt alte Dateien manipulieren. Besser:

1. neue Spalten über `addColumn()` ergänzen,
2. Defaults setzen,
3. optional Constraints/Indizes anlegen,
4. Snapshot erzeugen,
5. Daten transformieren,
6. Health prüfen.

```php
GBDB::addColumn("main", "users", "last_login", "");
GBDB::createIndex("main", "users", "uid");
GBDB::snapshot("main", "users", "after_schema_update");
```
