# GBDB und GreenQL

## Idee

GBDB ist der Datenspeicher.
GreenQL ist die Sprache darüber.

Das Ziel ist, dass Entwickler frei entscheiden können, ob sie direkt mit PHP-Methoden oder mit einer lesbaren, eigenen Query-Sprache arbeiten wollen.

Beides greift auf dasselbe System zu.

---

## Vergleich der beiden Arbeitsweisen

### Direkte GBDB-Methoden

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "users", ["name", "email", "role"]);
GBDB::insertData("main", "users", ["name" => "Markus"]);
$rows = GBDB::getData("main", "users");
```

### GreenQL

```php
GBDB::query("GROW BASE main;");
GBDB::query("GROW TABLE users (name, email, role) IN main;");
GBDB::query("SEED users WITH name='Markus' IN main;");
$rows = GBDB::query("PICK * FROM users IN main;");
```

---

## Warum GreenQL sinnvoll ist

GreenQL ist besonders stark für:

- Admin-Oberflächen
- Devtools
- Migrationen
- Seeds
- Testdaten
- Diagnosebefehle
- lesbare strukturierte Query-Skripte

Statt viele Methodenaufrufe in Serie zu schreiben, kannst du einen zusammenhängenden Script-Block ausführen.

Beispiel:

```php
GBDB::query("
ROOT main;
GROW TABLE users (name, email, role);
SEED users WITH name='Markus', email='markus@example.com', role='admin';
SEED users WITH name='Lea', email='lea@example.com', role='editor';
PICK id, name, role FROM users SORT id ASC LIMIT 50;
");
```

---

## Wie die Verbindung technisch funktioniert

`GBDB::query()` ruft intern die GreenQL-Engine auf.
Die GreenQL-Engine ruft danach wieder normale `GBDB`-Methoden auf.

Das heißt:

- GreenQL speichert keine Daten separat
- GreenQL ersetzt GBDB nicht
- GreenQL ist eine alternative Bedienoberfläche für GBDB

---

## Kontextsystem

GreenQL kennt einen optionalen Kontext:

- `db`
- `table`

Der Kontext kann durch Commands verändert werden:

```php
GBDB::query("ROOT main;");
GBDB::query("BRANCH users;", ["db" => "main"]);
```

Oder direkt beim Aufruf übergeben werden:

```php
GBDB::query("PICK * FROM users;", [
    "db" => "main",
    "table" => "users"
]);
```

---

## Rückgabestruktur von GreenQL

Jede Query liefert ein strukturiertes Array.

Wichtige Schlüssel:

- `ok`
- `messages`
- `results`
- `keys`
- `rows`
- `ctx`
- `refresh`

### `ok`

`true`, wenn alle Commands erfolgreich liefen.

### `messages`

Statusmeldungen aller Einzelbefehle.

### `results`

Alle Result-Sets, die tabellarische Ausgabe erzeugt haben.

### `keys` und `rows`

Das letzte Result-Set in vereinfachter Form.

### `ctx`

Der Kontext nach dem letzten erfolgreichen Command.

### `refresh`

Zeigt an, ob Daten oder Struktur geändert wurden.

---

## Schreibweise und Stil

GreenQL ist absichtlich nicht an SQL angelehnt, auch wenn einige Konzepte ähnlich aussehen. Die Sprache verwendet eigene Begriffe wie:

- `ROOT`
- `BRANCH`
- `GROW`
- `PICK`
- `PEEK`
- `SEED`
- `RESHAPE`
- `ERASE`
- `PACK`

Das passt besser zur GBDB-Idee und zu einem devfreundlichen, klar lesbaren Workflow.

---

## Typische Mappings

### Tabelle anlegen

```php
GBDB::createTable("main", "users", ["name", "email"]);
```

entspricht ungefähr:

```php
GBDB::query("GROW TABLE users (name, email) IN main;");
```

### Datensatz einfügen

```php
GBDB::insertData("main", "users", ["name" => "Markus"]);
```

entspricht:

```php
GBDB::query("SEED users WITH name='Markus' IN main;");
```

### Datensatz lesen

```php
GBDB::getData("main", "users");
```

entspricht:

```php
GBDB::query("PICK * FROM users IN main;");
```

### Datensatz bearbeiten

```php
GBDB::editData("main", "users", "id", 1, ["role" => "admin"]);
```

entspricht:

```php
GBDB::query("RESHAPE users WITH role='admin' WHERE id = 1 IN main;");
```

### Datensatz löschen

```php
GBDB::deleteData("main", "users", "id", 1);
```

entspricht:

```php
GBDB::query("ERASE FROM users WHERE id = 1 IN main;");
```

---

## Wann du was einsetzen solltest

### Direkte GBDB-Methoden sind ideal für

- produktive Business-Logik
- Services
- Controller
- klare CRUD-Abläufe
- Stellen mit starker Typ- oder Ablaufkontrolle

### GreenQL ist ideal für

- Admin-Tools
- Dev-Seiten
- Setup-Skripte
- Seeds
- Diagnose
- Migrationen
- Remote-Abfragen via `SrvP::query()`

---

## Beispiel: Komplettes Setup per GreenQL

```php
$result = GBDB::query("
GROW BASE main;
ROOT main;
GROW TABLE users (name, email, role);
SEED users WITH name='Markus', email='markus@example.com', role='admin';
SEED users WITH name='Lea', email='lea@example.com', role='editor';
PICK id, name, role FROM users SORT id ASC LIMIT 20;
");
```

Danach kannst du mit dem Result direkt weiterarbeiten:

```php
if ($result["ok"]) {
    foreach ($result["rows"] as $row) {
        echo $row["name"];
    }
}
```

---

## GreenQL in der UI und im SecondServer

Dieselbe Engine wird an drei Stellen genutzt:

1. `GBDB::query()` lokal im PHP-Code
2. `greenql_ui.php` im Query Mode
3. `SrvP::query()` remote über `api.php`

Dadurch ist das Verhalten überall konsistent.

---

## Fazit

GBDB und GreenQL sind nicht zwei konkurrierende Systeme.
Sie sind zwei Zugriffsarten auf dieselbe Datenlogik.

Das macht das Framework flexibel:

- direkt und technisch über PHP
- lesbar und kompakt über GreenQL
- lokal oder remote über SecondServer
