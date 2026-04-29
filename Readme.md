# GBDB Framework v4

GBDB ist dein JSON-/Append-basiertes Framework mit drei Ebenen, die auf derselben Datenbasis arbeiten:

1. **GBDB Core** für direkte PHP-Methoden
2. **GreenQL** als eigene Query-/Script-Sprache
3. **SecondServer Module** für Remote-Zugriffe per API

Das Ziel ist nicht SQL nachzubauen, sondern eine eigene, lesbare und schnelle Sprache für dieselbe Datenbasis bereitzustellen.

## Kernideen

- dieselben Daten lokal über `GBDB::*` und remote über `SrvP::*`
- klassische Direktmethoden **und** GreenQL parallel nutzbar
- GreenQL für einzelne Queries, Batch-Kommandos und komplette `.gql`-Scripts
- GreenQL UI mit getrenntem **UI Mode** und **Query Mode**
- Script-Parameter, Variablen und Kommentare in GreenQL

## Schnellstart

```php
<?php
include 'assets/php/inc/.config/_config.inc.php';
```

Danach stehen dir u. a. diese Klassen zur Verfügung:

- `GBDB`
- `Srv`
- `SrvP`
- `Vars`
- `Http`
- `Crypt`
- `Format`
- `Validate`
- `Route`
- `FileTool`
- `Cache`

## Zwei Wege für dieselbe Datenbasis

### Direkt per PHP

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "users", ["uid", "name", "username", "email", "password"]);

GBDB::insertData("main", "users", [
    "uid" => "u_1001",
    "name" => "Markus",
    "username" => "troyer05",
    "email" => "markus@example.com",
    "password" => "secret"
]);

$rows = GBDB::getData("main", "users");
$user = GBDB::getData("main", "users", true, "uid", "u_1001");
```

### Über GreenQL

```php
GBDB::query("GROW BASE main;");
GBDB::query("GROW TABLE users (uid, name, username, email, password) IN main;");
GBDB::query("SEED users WITH uid='u_1001', name='Markus', username='troyer05', email='markus@example.com', password='secret' IN main;");

$result = GBDB::query("PICK * FROM users IN main;");
```

## GreenQL als Scriptsprache

### Direktes Query-Running

```php
$result = GBDB::query("
ROOT main;
PICK * FROM users LIMIT 25;
");
```

### `.gql` Script aus Projektstruktur ausführen

```php
$result = GBDB::runScript("scripts/greenql/makeUser.gql", [
    "name" => $name,
    "username" => $username,
    "email" => $email,
    "password" => $password,
    "uid" => $uid
]);
```

### Remote dasselbe mit SecondServer

```php
$result = SrvP::query("PICK * FROM users IN main;");

$result = SrvP::runScript("scripts/greenql/makeUser.gql", [
    "name" => $name,
    "username" => $username,
    "email" => $email,
    "password" => $password,
    "uid" => $uid
]);
```

## GreenQL UI

`greenql_ui.php` ist die Dev-Oberfläche für GreenQL.

### UI Mode

- Basen anlegen/löschen
- Tabellen anlegen/umbauen/löschen
- Entries anlegen/bearbeiten/löschen
- Live-Preview und Schema ansehen

### Query Mode

- GreenQL direkt schreiben und ausführen
- `.gql` Datei hochladen und sofort ausführen
- dieselbe Engine wie `GBDB::query()` nutzen

## Neue Script-Features

### Kommentare

```gql
# Das ist ein Kommentar
# Ganze Zeilen oder Inline-Kommentare sind möglich
```

### Variablen

```gql
declare _status = "Aktiv";
declare _uid = param("uid");
```

`declare` und der vom Beispiel bekannte Schreibfehler `decalre` werden beide akzeptiert.

### Parameter

```gql
declare _name = param("name");
declare _email = param("email");
```

### Variablen in Commands

```gql
declare _base = "main";
declare _table = "users";
declare _uid = param("uid");

ROOT _base;
GROW TABLE _uid (firma, status) IN _base;
SEED _table WITH uid=_uid, status="Aktiv" IN _base;
```

Die Engine löst Variablen in Base-/Tabellen-Namen sowie in `WITH`- und `WHERE`-Werten auf.

## Result-Struktur von `GBDB::query()` / `GBDB::runScript()`

```php
[
    "ok" => true,
    "messages" => [
        ["ok" => true, "text" => "Base erstellt: main"]
    ],
    "results" => [
        [
            "command" => "PICK * FROM users IN main",
            "keys" => ["id", "uid", "name"],
            "rows" => [ ... ]
        ]
    ],
    "keys" => ["id", "uid", "name"],
    "rows" => [ ... ],
    "ctx" => [
        "db" => "main",
        "table" => "users"
    ],
    "vars" => [
        "_uid" => "u_1001"
    ],
    "refresh" => true
]
```

## Dokumentation

- `docs/gbdb_framework.md`
- `docs/second_server_module.md`
- `docs/gbdb_and_greenql.md`
- `docs/documentary_greenql_langunge.md`

## Beispielscript im Paket

- `scripts/greenql/makeUser.gql`

## Hinweise

- `ERASE` und `RESHAPE` unterstützen aktuell Schreibzugriffe mit `WHERE feld = wert` oder `==`
- `PICK` unterstützt zusätzlich `!=`, `>`, `<`, `>=`, `<=`, `~=`
- `PACK` kompaktet eine Tabelle
- GreenQL ist keine zweite Datenbank, sondern eine zweite Sprache über derselben GBDB-Struktur
