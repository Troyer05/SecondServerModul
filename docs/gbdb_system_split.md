# GBDB System Split

Die früher sehr langen GBDB-/GreenQL-Systemdateien wurden in eine klarere Ordnerstruktur unter `assets/php/inc/gbdb_framework/core/gbdb_system/` zerlegt.

## Ziel

Die Aufteilung soll Wartung, Debugging und Erweiterbarkeit verbessern. GBDB, GBDBv2, GreenQL und GreenQLv2 haben jeweils eigene Unterordner. Die eigentlichen Klassen bleiben weiterhin über die bekannten Wrapper erreichbar:

```php
GBDB::getData("main", "users");
GBDBv2::setInstance("kunde_a");
GreenQL::run($script);
GreenQLv2::run($script);
```

## Neue Struktur

```text
assets/php/inc/gbdb_framework/core/
├── gbdb_sys.php
├── gbdb_sys_v2.php
├── greenql_engine.php
├── greenql_engine_v2.php
└── gbdb_system/
    ├── gbdb/
    ├── gbdb_v2/
    ├── greenql/
    └── greenql_v2/
```

## Wrapper-Dateien

### `gbdb_sys.php`

Lädt:

```php
require_once __DIR__ . '/gbdb_system/gbdb/gbdb_schema.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/gbdb_transaction.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/gbdb_index.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/gbdb_storage.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/gbdb_crud.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/gbdb_maintenance.trait.php';
```

### `gbdb_sys_v2.php`

Lädt die instanzfähigen GBDBv2-Traits aus `gbdb_system/gbdb_v2/`.

### `greenql_engine.php`

Lädt Parser, Runtime, IO, Row-Helfer und Execution für GreenQL v1 aus `gbdb_system/greenql/`.

### `greenql_engine_v2.php`

Lädt die GreenQLv2-Gegenstücke aus `gbdb_system/greenql_v2/`.

## Warum nicht rekursiv im Loader?

Der globale Loader `gbdb.php` lädt bewusst nur direkte Core-Dateien. Dadurch bleibt die Lade-Reihenfolge stabil und kontrollierbar. Die Wrapper-Dateien sind der saubere Ort, um die interne Aufteilung zu verwalten.

## Behobene Punkte im Zuge der Aufteilung

- Die Schema-Pfade werden nicht mehr über fragile feste `dirname(__DIR__, n)`-Tiefen bestimmt.
- `FILE.INCLUDE`, `FILE.RUN` und `SET_LOGFILE` finden den Projekt-Root jetzt dynamisch.
- `DELACE` wird neben `DECLARE` und `DECALRE` als Legacy-/Highlighting-kompatibler Alias auch von der Engine akzeptiert.
- `ALTER TABLE ... ADD CONSTRAINT ...` wird nicht mehr fälschlich als `ADD COLUMN CONSTRAINT` interpretiert.
- Klassische `FOR (_i = 0; _i < n; _i++)`-Syntax wird neben der alten Komma-Schreibweise unterstützt.
- UI-Outputs werden nacheinander als eigener Output-Stream gerendert und zeigen zusätzlich den auslösenden `OUTPUT`-Befehl.

## Ausführbares Showcase

Das neue Script `scripts/greenql/greenql_full_showcase.gql` deckt die wichtigsten Sprachfeatures in einem ausführbaren Beispiel ab. Es kann in der GreenQL UI v2 oder per PHP gestartet werden:

```php
require "assets/php/inc/.config/_config.inc.php";

$result = GreenQLv2::run(
    file_get_contents("scripts/greenql/greenql_full_showcase.gql"),
    [],
    [
        "demo_name" => "Ada",
        "demo_password" => "secret"
    ]
);

print_r($result);
```
