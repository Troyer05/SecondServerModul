# GreenQLv2 – GreenQL mit Instanzen und erweiterten Storage-Befehlen

## Zweck

`GreenQLv2` ist die instanzfähige und erweiterte Variante der GreenQL-Engine. Sie spricht GBDBv2 an, sobald eine Instanz gesetzt ist, und ergänzt Befehle für Instanzen, Indizes, Constraints, Health Checks, Snapshots und Meta-Daten.

## Instanzbefehle

```gql
SHOW INSTANCES;
GROW INSTANCE museum_demo;
USE INSTANCE museum_demo;
ROOT INSTANCE museum_demo;
DROP INSTANCE museum_demo FORCE;
```

| Befehl | Zweck |
|---|---|
| `SHOW INSTANCES` | listet vorhandene Instanzen. |
| `GROW INSTANCE name` | erstellt eine Instanz. |
| `USE INSTANCE name` | setzt aktive Instanz für folgende Befehle. |
| `ROOT INSTANCE name` | Alias/Root-Kontext für Instanz. |
| `DROP INSTANCE name FORCE` | löscht Instanz mit Force-Option. |

## Vollständiges Setup-Beispiel

```gql
GROW INSTANCE museum_demo;
USE INSTANCE museum_demo;

GROW BASE main;
ROOT main;

GROW TABLE objects WITH oid, title, audio, text, active;
ALTER TABLE objects ADD COLUMN image DEFAULT "";
ALTER TABLE objects ADD CONSTRAINT UNIQUE oid;
INDEX objects oid;

SEED objects WITH {
    "oid":"obj_001",
    "title":"Römische Vase",
    "audio":"vase.mp3",
    "text":"Beschreibung für Besucher",
    "active":true
};

PICK * FROM objects WHERE active = TRUE SORT title ASC LIMIT 20;
```

## Erweiterte Tabellenbefehle

### Constraints

```gql
ALTER TABLE users ADD CONSTRAINT UNIQUE email;
ALTER TABLE users ADD CONSTRAINT REQUIRED username;
SHOW CONSTRAINTS FROM users;
ALTER TABLE users DROP CONSTRAINT UNIQUE email;
```

### Indizes

```gql
INDEX users email;
CREATE INDEX ON users uid;
SHOW INDEXES FROM users;
REINDEX users;
UNINDEX users email;
DROP INDEX ON users uid;
```

### Health und Repair

```gql
HEALTH users;
CHECK users;
REPAIR users;
```

### Snapshots

```gql
SNAPSHOT users;
```

### Meta anzeigen

```gql
SHOW META FROM users;
DESCRIBE users;
```

## Explain

```gql
EXPLAIN PICK * FROM users WHERE email = "demo@example.com" LIMIT 1;
```

Dieser Befehl ist für Diagnose gedacht. Er zeigt, wie die Query interpretiert wird und ob ein Index sinnvoll wäre bzw. verwendet werden kann.

## Unterschied GreenQL vs. GreenQLv2

| Bereich | GreenQL | GreenQLv2 |
|---|---|---|
| lokale DB | ja | ja |
| Instanzen | eingeschränkt/kontextabhängig | zentraler Bestandteil |
| `SHOW INSTANCES` | ja, wenn GBDBv2 vorhanden | ja |
| Constraints/Index/Health | vorhanden, abhängig vom Stand | vollständig vorgesehen |
| UI-Nutzung | möglich | bevorzugt für GreenQL UI v2 |

## Ausführung in PHP

```php
$result = GreenQLv2::runScript("scripts/greenql/classes.gql", [
    "uid" => "u1"
], [
    "instance" => "kunde_a"
]);
```

Oder über GBDBv2:

```php
GBDBv2::setInstance("kunde_a");
$result = GBDBv2::query("ROOT main; PICK * FROM users LIMIT 10;");
```

## Best Practices

- In Mandantenscripten immer am Anfang `USE INSTANCE ...` setzen.
- Für eindeutige IDs `UNIQUE` Constraints nutzen.
- Für häufige WHERE-Spalten `INDEX` anlegen.
- Vor Massenänderungen `SNAPSHOT` ausführen.
- `HEALTH` und `REPAIR` in Admin-/Wartungstools einbauen.
- In UI-Kontexten keine reservierten Systeminstanzen verwenden.

# Erweiterte Befehlsreferenz

## Struktur- und Instanzbefehle

```gql
SHOW INSTANCES;
GROW INSTANCE kunde_a;
USE INSTANCE kunde_a;
ROOT INSTANCE kunde_a;
DROP INSTANCE kunde_a FORCE;
```

## Base- und Tabellenbefehle

```gql
GROW BASE main;
DROP BASE main;
ROOT main;
SHOW BASES;
SHOW TABLES IN main;
GROW TABLE users WITH uid, username, email;
ALTER TABLE users ADD COLUMN last_login DEFAULT "";
DROP TABLE users;
```

## Storage- und Diagnosebefehle

```gql
SHOW META FROM users;
SHOW INDEXES FROM users;
SHOW CONSTRAINTS FROM users;
HEALTH users;
REPAIR users;
SNAPSHOT users;
PACK users;
```

`PACK` führt eine Kompaktierung aus. Das ist sinnvoll, wenn viele Append-/WAL-Operationen aufgelaufen sind.

## Query-Diagnose

```gql
EXPLAIN PICK * FROM users WHERE email = "demo@example.com" LIMIT 1;
```

`EXPLAIN` ist hilfreich, wenn eine Abfrage langsamer ist als erwartet oder wenn geprüft werden soll, ob ein Index für eine WHERE-Spalte sinnvoll ist.

## Produktives Mandanten-Setup

```gql
GROW INSTANCE kunde_a;
USE INSTANCE kunde_a;
GROW BASE main;
ROOT main;

GROW TABLE settings WITH key, value, type;
ALTER TABLE settings ADD CONSTRAINT UNIQUE key;
INDEX settings key;

SEED settings WITH {"key":"site_name","value":"Demo Museum","type":"string"};
SNAPSHOT settings;
HEALTH settings;
```

## Warum GreenQLv2 für UI und Remote bevorzugt ist

GreenQLv2 kann den Instanzkontext direkt im Script ausdrücken. Dadurch ist ein Script selbstbeschreibender:

```gql
USE INSTANCE kunde_a;
ROOT main;
PICK * FROM objects;
```

Ohne diese Zeile müsste der PHP-/Remote-Kontext korrekt gesetzt sein. Mit `USE INSTANCE` ist das Script robuster und leichter testbar.

## Sicherheitsregeln

- `DROP INSTANCE ... FORCE` nur in Admin-/Wartungskontexten erlauben.
- In der UI reservierte Instanzen blockieren.
- Userinput nicht direkt als Instanz-, Base- oder Tabellenname verwenden.
- Bei Public APIs GreenQLv2 nur mit klaren Gates aktivieren.
- Vor `RESHAPE`, `ERASE`, `DROP` oder Massenoperationen Snapshots erzeugen.

## Runtime-Funktionen für Advanced-Engine-Features

GreenQL kann die neuen Engine-Funktionen nicht nur als Befehl, sondern auch als Runtime-Ausdruck verwenden. Das ist praktisch, wenn Ergebnisse in Variablen gespeichert, geprüft oder weiterverarbeitet werden sollen.

```gql
DECLARE _hasInstance = instance_exists("default");
DECLARE _hasBase = base_exists("main");
DECLARE _hasUsers = table_exists("main", "users");
DECLARE _hasMax = data_exists("main", "users", ["email": "max@example.de"]);

DECLARE _monitor = monitor("main", "users");
DECLARE _recovery = recover("main", "users");
DECLARE _page = page("main", "users", 1, 50);
DECLARE _cursor = cursor("main", "users", 100);
DECLARE _hits = fulltext_search("main", "users", "Max Muster", ["username", "email"], 25);

OUTPUT(_monitor);
OUTPUT(_hits);
```

Die Funktionen unterstützen bei GBDBv2 zusätzlich Instanzargumente, wenn diese vorne übergeben werden:

```gql
DECLARE _hasBase = base_exists("kunde1", "main");
DECLARE _hasUsers = table_exists("kunde1", "main", "users");
DECLARE _page = page("kunde1", "main", "users", 1, 50);
DECLARE _hits = fulltext_search("kunde1", "main", "users", "Max", ["username"], 10);
```

## GreenQL ENV-Werte

Sensible Script-Werte können über `ENV("key")` gelesen werden. Die Werte liegen nicht in einer normalen Text-`.env`, sondern in einer PHP-Datei:

```php
// scripts/greenql/.ENV/.env.php
<?php

$GREENQL_ENV = [
    "api_auth" => "dein-geheimer-api-token",
];

return $GREENQL_ENV;
```

Verwendung im GreenQL-Script:

```gql
DECLARE _api_auth = ENV("api_auth");
```

Der ENV-Key darf Buchstaben, Zahlen, `_`, `-` und `.` enthalten. Fehlt der Key, gibt `ENV()` `null` zurück. Die Datei wird serverseitig per PHP geladen und ist damit nicht als Klartext-Datei für den Browser gedacht. Unterstützt werden `return [...]`, `$GREENQL_ENV`, `$GQL_ENV`, `$ENV` oder einfache PHP-Variablen wie `$api_auth`. Zusätzlich liegt in `scripts/greenql/.ENV/` eine `.htaccess`, die direkten Zugriff bei Apache blockiert.
