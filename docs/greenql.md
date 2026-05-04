# GreenQL – eigene Script- und Query-Sprache

## Zweck

GreenQL ist die eigene Sprache des Frameworks. Sie verbindet Datenbankbefehle mit Script-Features. Dadurch können Setup-Scripte, Wartungsjobs, Imports, Tests und Admin-Aktionen geschrieben werden, ohne direkt PHP-Code zu ändern.

GreenQL wird über `GreenQL`, `GBDB::query()` oder `GBDB::runScript()` ausgeführt.

## Grundprinzip

Ein GreenQL-Script besteht aus Befehlen. Befehle enden mit `;`, außer Blockbefehle mit `{ ... }` werden als kompletter Block erkannt.

```gql
GROW BASE main;
ROOT main;
GROW TABLE users WITH uid, username, email, active;
SEED users WITH {"uid":"u1","username":"admin","email":"admin@example.com","active":true};
PICK * FROM users;
```

## Kommentare

```gql
# Kommentar
// Kommentar
-- Kommentar
```

Kommentare innerhalb von Strings bleiben erhalten.

## Variablen und Konstanten

Normale Variablen beginnen mit `_`. Konstanten beginnen mit `$` und sollen nicht überschrieben werden.

```gql
DECLARE _name = "MuseumQR";
DECLARE _version = "1.0.0";
DECLARE $APP = "greenbucket";
```

Der Parser akzeptiert historisch auch `DECALRE` als Tippfehler-Alias.

## Parameter aus PHP

```php
$result = GBDB::query('DECLARE _uid = param("uid"); OUTPUT _uid;', [], [
    "uid" => "u123"
]);
```

In GreenQL:

```gql
DECLARE _uid = param("uid");
PICK * FROM users WHERE uid = _uid LIMIT 1;
```

## Werte und Literale

Unterstützt werden Strings, Zahlen, `TRUE`, `FALSE`, `NULL`, Arrays und JSON-Objekte.

```gql
DECLARE _active = TRUE;
DECLARE _price = 49.90;
DECLARE _data = {"title":"Demo","active":true};
DECLARE _list = ["a","b","c"];
```

## Datenbankstruktur

### Base auswählen

```gql
ROOT main;
```

### Tabelle auswählen

```gql
BRANCH users;
```

### Bases und Tabellen anzeigen

```gql
SHOW BASES;
SHOW TABLES;
SHOW TABLES IN main;
```

### Base und Tabelle erstellen

```gql
GROW BASE main;
GROW TABLE users WITH uid, username, email, active;
```

Alternative Schreibweise:

```gql
GROW TABLE users(uid, username, email, active);
```

### Spalte hinzufügen

```gql
ALTER TABLE users ADD COLUMN last_login DEFAULT "";
EDIT TABLE users ADD active DEFAULT 1;
```

### Tabelle oder Base löschen

```gql
DROP TABLE users;
DROP BASE old_demo;
```

## Daten lesen

```gql
PICK * FROM users;
PICK uid, username FROM users WHERE active = 1;
PICK * FROM users WHERE username ~= "mark" SORT username ASC LIMIT 10;
```

Operatoren:

| Operator | Bedeutung |
|---|---|
| `=` / `==` | ist gleich |
| `!=` | ist ungleich |
| `>` / `<` | größer / kleiner |
| `>=` / `<=` | größer/gleich bzw. kleiner/gleich |
| `~=` | enthält / unscharfer Stringvergleich |

## Daten einfügen

```gql
SEED users WITH {"uid":"u1","username":"admin","email":"admin@example.com"};
```

Mit Variable:

```gql
DECLARE _user = {"uid":"u2","username":"demo","email":"demo@example.com"};
SEED users WITH _user;
```

## Daten ändern

```gql
RESHAPE users WITH {"active":0} WHERE uid = "u2";
```

## Daten löschen

```gql
ERASE FROM users WHERE uid = "u2";
DELETE FROM users WHERE active = 0;
```

## Existenzprüfungen

```gql
EXISTS BASE main;
EXISTS TABLE users IN main;
EXISTS DATA users WHERE uid = "u1";
```

## Ausgabe

`OUTPUT` schreibt einen Eintrag in das Ergebnisarray. Wichtig: Outputs werden nacheinander gesammelt und teilen sich nicht ein einziges Feld.

```gql
OUTPUT "Start";
OUTPUT _user;
OUTPUT {"status":"done"};
```

## Logging

GreenQL unterstützt eigene Logbefehle.

```gql
SET_LOGFILE("assets/php/srv_logs/import.log");
LOG("Import gestartet");
LOG({"step":"users","count":10});
CLEAR_LOG();
DELETE_LOG_FILE();
```

| Befehl | Zweck |
|---|---|
| `SET_LOGFILE("path")` | setzt die Logdatei für folgende `LOG()`-Aufrufe. |
| `LOG(value)` | schreibt Wert/Text/Objekt als Logeintrag. |
| `CLEAR_LOG()` | leert die aktuelle Logdatei. |
| `DELETE_LOG_FILE()` | löscht die aktuelle Logdatei. |

## Kontrollfluss

```gql
DECLARE _active = TRUE;

IF (_active == TRUE) {
    OUTPUT "aktiv";
} ELSE {
    OUTPUT "inaktiv";
}
```

## Schleifen

```gql
DECLARE _items = ["a", "b", "c"];

FOR (_i; _item FROM _items) {
    OUTPUT _item;
}
```

Klassische Schleife:

```gql
FOR (_i = 0, _i < 5; _i++) {
    OUTPUT _i;
}
```

## Objekt-Mapping

```gql
DECLARE _obj = {"a":1,"b":2};

MAP_OBJECT (_obj AS _key, _value) {
    OUTPUT {"key": _key, "value": _value};
}
```

## Funktionen

```gql
F makeTitle(_name) {
    BACK "Hallo " + _name;
}

DECLARE _title = CALL makeTitle("Markus");
OUTPUT _title;
```

`BACK` gibt einen Wert aus der Funktion zurück.

## Klassen-/Objektlogik

GreenQL unterstützt einfache Klassen-/Namespace-artige Blöcke.

```gql
CLASS UserTools {
    PUB F label(_name) {
        BACK "User: " + _name;
    }
}

OUTPUT CLASS UserTools/label("Admin");
```

## Datei-Ausführung

```gql
FILE.INCLUDE "scripts/greenql/shared.gql";
FILE.RUN "scripts/greenql/makeUser.gql" {"username":"admin"};
```

`FILE.BACK` gibt einen Wert aus einer per `FILE.RUN` ausgeführten Datei zurück.

## Hash- und Helper-Funktionen

```gql
DECLARE _hash = hash_sha256("secret");
DECLARE _len = len("greenql");
```

Unterstützt werden u. a. `hash_sha256`, `hash_sha512`, `hash_md5`, `hash_adler32`, `hash_crc32`, `hash(algo, value)` und `len(value)`.

## Transaktionen

```gql
BEGIN;
SEED users WITH {"uid":"u3","username":"temp"};
SHOW TRANSACTION;
COMMIT;
```

Oder bei Fehler:

```gql
ROLLBACK;
```

## Fehler und Stop

```gql
ERROR MSG "Import fehlgeschlagen";
END_PROC;
```

`END_PROC` beendet die Script-Ausführung erfolgreich an dieser Stelle.

## Ausführung in PHP

```php
$result = GBDB::query($script);
```

```php
$result = GBDB::runScript("scripts/greenql/setup.gql", [
    "username" => "admin"
]);
```

## Best Practices

- Variablen konsequent mit `_` schreiben.
- Konstanten mit `$` nur für Werte nutzen, die nicht geändert werden sollen.
- Für Setup-Scripte `GROW BASE` und `GROW TABLE` am Anfang setzen.
- Für Imports `SET_LOGFILE()` und `LOG()` nutzen.
- Bei kritischen Änderungen `BEGIN`/`COMMIT` oder Snapshots nutzen.
- Dateipfade nicht aus ungeprüftem Userinput bauen.

# Vollständigeres Befehls- und Sprachmodell

## Befehlsgruppen

| Gruppe | Befehle |
|---|---|
| Variablen | `DECLARE`, `DECALRE`, Zuweisung mit `=` |
| Ausgabe | `OUTPUT`, `MSG`, `ERROR MSG` |
| Datenbankstruktur | `ROOT`, `BRANCH`, `SHOW BASES`, `SHOW TABLES`, `GROW BASE`, `DROP BASE`, `GROW TABLE`, `ALTER TABLE`, `DROP TABLE`, `DESCRIBE` |
| Daten | `PICK`, `SEED`, `RESHAPE`, `ERASE`, `DELETE FROM`, `PEEK` |
| Kontrollfluss | `IF`, `ELSE`, `FOR`, `MAP_OBJECT` |
| Funktionen | `F`, `CALL`, `BACK`, `END_PROC`, `this_f.restart()` |
| Dateien | `FILE.INCLUDE`, `FILE.RUN`, `FILE.BACK` |
| Logging | `SET_LOGFILE`, `LOG`, `CLEAR_LOG`, `DELETE_LOG_FILE` |
| Transaktionen | `BEGIN`, `COMMIT`, `ROLLBACK`, `SHOW TRANSACTION` |
| Utilities | `param()`, `len()`, `hash_*()`, `EXISTS` |

## Variablenkonvention

Im aktuellen Sprachstil sollen normale Variablen mit `_` beginnen. Das macht Variablen optisch sofort erkennbar und trennt sie von Befehlen, Spaltennamen und Literalen.

```gql
DECLARE _username = "admin";
OUTPUT _username;
```

Konstanten beginnen mit `$`:

```gql
DECLARE $APP_NAME = "MuseumQR";
```

## Datenbank-Workflow als Script

```gql
# 1. Base erzeugen und auswählen
GROW BASE main;
ROOT main;

# 2. Tabelle erzeugen
GROW TABLE users WITH uid, username, email, active;

# 3. Beispieldaten einfügen
DECLARE _user = {
    "uid":"u1",
    "username":"admin",
    "email":"admin@example.com",
    "active":true
};

SEED users WITH _user;

# 4. Prüfen und ausgeben
DECLARE _exists = EXISTS DATA users WHERE uid = "u1";
OUTPUT _exists;
PICK * FROM users LIMIT 10;
```

## Kontrollfluss-Beispiel mit Datenbank

```gql
DECLARE _uid = param("uid");
DECLARE _exists = EXISTS DATA users WHERE uid = _uid;

IF (_exists == TRUE) {
    RESHAPE users WITH {"active":true} WHERE uid = _uid;
    OUTPUT "user updated";
} ELSE {
    SEED users WITH {"uid":_uid,"username":"new_user","active":true};
    OUTPUT "user created";
}
```

## Logging bei Imports

```gql
SET_LOGFILE("assets/php/srv_logs/user_import.log");
LOG("Import startet");

DECLARE _users = param("users");

FOR (_i; _user FROM _users) {
    LOG({"import_user": _user});
    SEED users WITH _user;
}

LOG("Import fertig");
OUTPUT "done";
```

## Datei-Scripte modularisieren

Große GreenQL-Prozesse sollten aufgeteilt werden:

```gql
FILE.INCLUDE "scripts/greenql/shared/helpers.gql";
FILE.RUN "scripts/greenql/users/create_admin.gql" {"username":"admin"};
```

So kann man wiederkehrende Setup-Schritte in eigene Dateien legen.

## Ergebnisstruktur verstehen

GreenQL gibt intern eine Ergebnisliste zurück. Jeder Befehl kann eine Message, Daten oder Statusinformationen erzeugen. Deshalb sollte PHP-Code nicht nur auf ein einzelnes Feld vertrauen.

```php
$result = GBDB::query($script);

foreach ($result as $entry) {
    // je nach Engine: msg/data/status prüfen
}
```

## Sprachintention

GreenQL soll nicht SQL kopieren. Die Sprache verwendet bewusst eigene Begriffe:

- `GROW` statt `CREATE`, weil Strukturen „wachsen“.
- `SEED` statt `INSERT`, weil Daten „gesät“ werden.
- `RESHAPE` statt `UPDATE`, weil Datensätze umgeformt werden.
- `ERASE` statt `DELETE`, als eigener Sprachstil.
- `ROOT` und `BRANCH`, weil Bases und Tabellen wie eine Baumstruktur gedacht werden können.

Diese Eigenheiten sind Teil der Identität der Sprache und sollten auch in Highlighting, UI und Doku erhalten bleiben.

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
