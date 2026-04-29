# GBDB und GreenQL zusammen

## Grundsatz

GBDB und GreenQL sind kein Entweder-oder. Sie gehören zusammen.

- **GBDB** ist die Methoden-API
- **GreenQL** ist die Sprach-API
- beide greifen auf dieselbe Datenbasis zu

Du kannst also innerhalb eines Projekts völlig gemischt arbeiten.

## Typische Mischformen

### 1. Struktur per PHP, Abfragen per GreenQL

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "users", ["uid", "name", "role"]);

$result = GBDB::query("PICK uid, name FROM users IN main WHERE role = 'admin';");
```

### 2. CRUD per PHP, Batch-Operationen per GreenQL

```php
GBDB::insertData("main", "users", [
    "uid" => "u_1001",
    "name" => "Markus",
    "role" => "admin"
]);

GBDB::query("
ROOT main;
SHOW TABLES;
PICK * FROM users SORT id DESC LIMIT 20;
");
```

### 3. Prozesslogik in `.gql`, Business-Trigger in PHP

```php
if ($registerOk) {
    GBDB::runScript("scripts/greenql/makeUser.gql", [
        "uid" => $uid,
        "name" => $name,
        "username" => $username,
        "email" => $email,
        "password" => $password
    ]);
}
```

## Warum GreenQL zusätzlich sinnvoll ist

Direkte Methoden sind präzise. GreenQL ist aber deutlich stärker bei:

- wiederverwendbaren Workflows
- lesbaren Setup-Scripts
- Serienaktionen in einer Datei
- Dev-Tools / Admin-Oberflächen
- Remote-Ausführung per SecondServer

## GreenQL UI als Bindeglied

`greenql_ui.php` verbindet beide Welten.

### UI Mode

hier geht es um klassische Oberfläche:

- Base erstellen/löschen
- Tabelle erstellen/umbauen/löschen
- Entry erstellen/editieren/löschen

### Query Mode

hier geht es um die Sprachseite:

- Query manuell schreiben
- mehrere Kommandos in Folge ausführen
- `.gql` Datei hochladen und direkt ausführen

Beide Modi aktualisieren dieselbe Preview und dieselbe Strukturansicht.

## Kontextsystem

GreenQL kennt einen aktiven Kontext.

```gql
ROOT main;
BRANCH users;
PICK * FROM users;
```

`ROOT` setzt die aktive Base, `BRANCH` die aktive Tabelle. Dadurch musst du `IN main` nicht dauernd wiederholen.

## Scriptsystem

Mit `runScript()` kannst du GreenQL-Dateien wie kleine Module behandeln.

### Beispielaufbau

```txt
/scripts
    /greenql
        makeUser.gql
        createTicket.gql
        setupDemo.gql
```

### Vorteil

Die Fachlogik bleibt lesbar, versionierbar und vom PHP-Code entkoppelt.

## Parameterisierte Scripts

```gql
declare _uid = param("uid");
declare _role = param("role");
SEED users WITH uid=_uid, role=_role IN main;
```

```php
GBDB::runScript("scripts/greenql/makeUser.gql", [
    "uid" => "u_1001",
    "role" => "admin"
]);
```

## Variablen in GreenQL

```gql
declare _base = "main";
declare _table = "users";
declare _state = "Aktiv";

GROW BASE _base;
SEED _table WITH status=_state IN _base;
```

Unterstützt werden aktuell Variablen in:

- Base-Namen
- Tabellennamen
- `WITH`-Werten
- `WHERE`-Werten
- `SORT`-Feldern und Spaltenlisten, wenn dort Variable als Token genutzt wird

## Kommentare

```gql
# Einzeiliger Kommentar
SEED users WITH name="Markus" IN main; # Inline-Kommentar
```

## Best Practice für dein Framework

Für dein System ist die sinnvollste Arbeitsweise meistens:

- einfache CRUD-Operationen direkt in PHP
- Setup-/Seed-/Serienlogik als `.gql`
- Dev-Analyse und Testläufe über GreenQL UI
- Remote-Wiederverwendung über `SrvP::query()` oder `SrvP::runScript()`
