# GBDB Framework v4

Das hier ist das aktuelle GBDB Framework mit drei Ebenen, die zusammen arbeiten:

1. **GBDB Core** für direkte PHP-Aufrufe wie `GBDB::getData(...)`
2. **GreenQL** als eigene, lesbare Query-Sprache wie `GBDB::query("PICK * FROM users IN main;")`
3. **SecondServer Module API** für Remote-Zugriffe wie `SrvP::getData(...)` und `SrvP::query(...)`

Der Fokus des Frameworks liegt darauf, Datenbankarbeit ohne klassisches SQL zu ermöglichen, aber trotzdem strukturiert, reproduzierbar und schnell entwickelbar zu bleiben.

---

## Schnellstart

### Framework laden

```php
<?php
include 'assets/php/inc/.config/_config.inc.php';
```

Danach stehen dir unter anderem diese Klassen zur Verfügung:

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

---

## Zwei Arbeitsweisen für dieselbe Datenbasis

### 1) Direkte PHP-Methoden

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "users", ["name", "email", "role"]);
GBDB::insertData("main", "users", [
    "name" => "Markus",
    "email" => "markus@example.com",
    "role" => "admin"
]);

$all = GBDB::getData("main", "users");
$one = GBDB::getData("main", "users", true, "id", 1);
```

### 2) GreenQL

```php
GBDB::query("GROW BASE main;");
GBDB::query("GROW TABLE users (name, email, role) IN main;");
GBDB::query("SEED users WITH name='Markus', email='markus@example.com', role='admin' IN main;");

$result = GBDB::query("PICK * FROM users IN main;");
```

Beide Wege greifen auf denselben GBDB-Bestand zu. GreenQL ist also keine zweite Datenbank, sondern nur eine zusätzliche Sprachebene auf dem vorhandenen GBDB-System.

---

## Was `GBDB::query()` zurückgibt

`GBDB::query()` liefert ein Array zurück, damit du das Ergebnis direkt weiterverarbeiten kannst.

Beispielstruktur:

```php
[
    "ok" => true,
    "messages" => [
        ["ok" => true, "text" => "1 Treffer aus users."]
    ],
    "results" => [
        [
            "command" => "PICK * FROM users IN main",
            "keys" => ["id", "name", "email", "role"],
            "rows" => [
                ["id" => 1, "name" => "Markus", "email" => "markus@example.com", "role" => "admin"]
            ]
        ]
    ],
    "keys" => ["id", "name", "email", "role"],
    "rows" => [...],
    "ctx" => [
        "db" => "main",
        "table" => "users"
    ],
    "refresh" => true
]
```

Wichtig:

- `messages` enthält Statusmeldungen aller ausgeführten Commands.
- `results` enthält Result-Sets aller Commands mit Tabellenoutput.
- `keys` und `rows` enthalten immer das **letzte** Result-Set.
- `ctx` speichert den aktuellen GreenQL-Kontext.
- `refresh` signalisiert, dass sich Struktur oder Daten geändert haben.

---

## Kontext in GreenQL

GreenQL kennt einen aktiven Kontext für Base und Tabelle.

### Base fokussieren

```php
GBDB::query("ROOT main;");
```

### Tabelle fokussieren

```php
GBDB::query("ROOT main; BRANCH users;");
```

Danach kannst du kürzer arbeiten:

```php
GBDB::query("PICK * FROM users;");
GBDB::query("SEED users WITH name='Lea', email='lea@example.com', role='editor';");
```

Oder du gibst die Base jedes Mal explizit an:

```php
GBDB::query("PICK * FROM users IN main;");
```

---

## GreenQL UI

Die Datei `greenql_ui.php` bietet zwei getrennte Arbeitsmodi:

- **UI Mode** für Base-, Tabellen- und Entry-Verwaltung per Oberfläche
- **Query Mode** für direkte GreenQL-Befehle

Die UI nutzt intern dieselbe Logik wie `GBDB::query()`. Dadurch verhalten sich Browser-Oberfläche und Entwickler-API gleich.

---

## SecondServer / Remote-Nutzung

Neben der lokalen Klasse `GBDB` gibt es mit `SrvP` die Remote-Variante.

### Direktmethoden

```php
SrvP::getData("main", "users");
SrvP::addData("main", "users", ["name" => "Lea"]);
SrvP::editData("main", "users", "id", 1, ["role" => "editor"]);
SrvP::deleteData("main", "users", "id", 1);
```

### Remote-GreenQL

```php
SrvP::query("PICK * FROM users IN main;");
SrvP::query("SEED users WITH name='Tom', email='tom@example.com', role='support' IN main;");
```

Damit kannst du dieselbe Sprache lokal und remote verwenden.

---

## Dokumentation in diesem Paket

- `docs/gbdb_framework.md`
- `docs/second_server_module.md`
- `docs/gbdb_and_greenql.md`
- `docs/documentary_greenql_langunge.md`

Diese Dateien erklären Architektur, Feature-Set, API-Nutzung, GreenQL-Syntax und typische Workflows ausführlich.

---

## Typischer Entwicklungsflow

### Lokal

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "users", ["name", "email", "role"]);
GBDB::insertData("main", "users", ["name" => "Markus", "email" => "markus@example.com", "role" => "admin"]);
```

### Mit GreenQL

```php
GBDB::query("ROOT main;");
GBDB::query("GROW TABLE users (name, email, role);");
GBDB::query("SEED users WITH name='Markus', email='markus@example.com', role='admin';");
GBDB::query("PICK id, name, role FROM users SORT id DESC LIMIT 10;");
```

### Remote

```php
SrvP::query("PICK * FROM users IN main;");
```

---

## Hinweise

- `DROP BASE` funktioniert nur, wenn die Base leer ist.
- `ERASE` und `RESHAPE` unterstützen aktuell `WHERE feld = wert` bzw. `==` für Schreiboperationen.
- `PICK` unterstützt zusätzlich Vergleichsoperatoren wie `!=`, `>`, `<`, `>=`, `<=` und `~=`.
- `PACK` verdichtet Append-Operationen in eine frische Tabellendatei.
- Das Framework arbeitet intern append-basiert und kompaktet Tabellen bei Bedarf.

---

## Empfehlte Reihenfolge zum Einarbeiten

1. `docs/gbdb_framework.md`
2. `docs/gbdb_and_greenql.md`
3. `docs/documentary_greenql_langunge.md`
4. `docs/second_server_module.md`

Danach hast du das gesamte System von Core bis Remote-API im Blick.
   
<br><br>
Entwickelt von Markus Müller- CSS und Dokumentation von ChatGPT
<br><br>
<a href="https://mamueller.de">Zu meiner Webseite</a>
