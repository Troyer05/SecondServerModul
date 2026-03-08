# GBDB Framework

## Überblick

GBDB ist die direkte PHP-Schicht des Frameworks. Sie arbeitet append-basiert auf Dateiebene und bildet die technische Grundlage für GreenQL und das SecondServer Module.

Die Kernidee ist:

- Basen = Datenbank-Ordner
- Tabellen = Dateistrukturen innerhalb einer Base
- Einträge = Datensätze mit Auto-ID
- GreenQL = alternative Sprachschicht auf denselben Methoden

## Typischer Include

```php
<?php
include 'assets/php/inc/.config/_config.inc.php';
```

## Wichtige Direktmethoden

### Basen

```php
GBDB::createDatabase("main");
GBDB::deleteDatabase("main");
GBDB::listDBs();
```

### Tabellen

```php
GBDB::createTable("main", "users", ["uid", "name", "email"]);
GBDB::deleteTable("main", "users");
GBDB::listTables("main");
GBDB::getKeys("main", "users");
GBDB::compactTable("main", "users");
```

### Datensätze

```php
GBDB::insertData("main", "users", [
    "uid" => "u_1001",
    "name" => "Markus",
    "email" => "markus@example.com"
]);

GBDB::getData("main", "users");
GBDB::getData("main", "users", true, "uid", "u_1001");

GBDB::editData("main", "users", "uid", "u_1001", [
    "name" => "Markus Müller"
]);

GBDB::deleteData("main", "users", "uid", "u_1001");
```

## GreenQL aus dem Core heraus

Neben den Direktmethoden gibt es zwei High-Level-Zugänge:

```php
GBDB::query("PICK * FROM users IN main;");
GBDB::runScript("scripts/greenql/makeUser.gql", ["uid" => "u_1001"]);
```

### `GBDB::query()`

Signatur:

```php
GBDB::query(string $script, array $ctx = [], array $params = []): array
```

Parameter:

- `script`: GreenQL-Text
- `ctx`: Startkontext wie `db` und `table`
- `params`: Script-Parameter für `param("...")`

### `GBDB::runScript()`

Signatur:

```php
GBDB::runScript(string $path, array $params = [], array $ctx = []): array
```

Verhalten:

- liest eine `.gql` Datei aus der Projektstruktur
- führt sie mit derselben Engine wie `GBDB::query()` aus
- akzeptiert Script-Parameter
- gibt dieselbe Result-Struktur zurück wie `query()`

## Beispiel: User-Script kapseln

### PHP

```php
GBDB::runScript("scripts/greenql/makeUser.gql", [
    "uid" => $uid,
    "name" => $name,
    "username" => $username,
    "email" => $email,
    "password" => $password
]);
```

### `.gql`

```gql
# Parameter lesen
declare _uid = param("uid");
declare _name = param("name");
declare _username = param("username");
declare _email = param("email");
declare _password = param("password");

SEED users WITH uid=_uid, name=_name, username=_username, email=_email, password=_password IN main;
```

## Was direkt und was über GreenQL?

### Direktmethoden sind stark, wenn ...

- du exakt in PHP bleiben willst
- du IDE-Autocomplete magst
- du sehr gezielt CRUD aufrufst

### GreenQL ist stark, wenn ...

- du mehrere Schritte zusammenfassen willst
- du Abläufe in Scripts auslagern willst
- du wiederverwendbare Seeder, Setups oder Admin-Makros bauen willst
- du dieselbe Sprache lokal und remote verwenden willst

## Empfohlene Struktur für GreenQL-Scripts

```txt
/scripts
    /greenql
        makeUser.gql
        setupMain.gql
        resetDemo.gql
        seedMuseum.gql
```

## Hinweise zur Datenstruktur

- `id` wird intern als Auto-ID geführt
- `id` sollte in `WITH` nicht manuell gesetzt werden
- beim Tabellenumbau sollten nur fachliche Spalten angegeben werden, nicht `id`
- `compactTable()` bzw. `PACK` sollte nach vielen Änderungen sinnvoll eingesetzt werden

## Best Practice

- Projekt-Setup als `.gql` Script ablegen
- wiederkehrende Create-/Seed-Abläufe als `runScript()` kapseln
- komplexe Massenaktionen lieber über GreenQL als über viele einzelne PHP-Aufrufe abbilden
- fachlich stabile Prozesse in eigene Scriptdateien auslagern
