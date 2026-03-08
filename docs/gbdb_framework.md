# GBDB Framework

## Überblick

GBDB ist ein dateibasiertes Datenbanksystem innerhalb des Frameworks. Es arbeitet nicht mit klassischem SQL-Backend, sondern verwaltet Daten in strukturierten Dateien unter dem durch `Vars::DB_PATH()` definierten Pfad.

Das System ist so gebaut, dass du als Entwickler zwei gleichwertige Wege hast:

- direkte PHP-Methoden auf `GBDB`
- GreenQL als eigene Query-Sprache

Beides greift auf dieselben Tabellen und Datensätze zu.

---

## Hauptklasse: `GBDB`

Die Klasse `GBDB` ist die zentrale Datenbank-API.

### Basen

```php
GBDB::createDatabase("main");
GBDB::deleteDatabase("main");
GBDB::listDBs();
GBDB::deleteAll("main");
```

### Tabellen

```php
GBDB::createTable("main", "users", ["name", "email", "role"]);
GBDB::deleteTable("main", "users");
GBDB::listTables("main");
GBDB::getKeys("main", "users");
GBDB::compactTable("main", "users");
GBDB::nextID("main", "users");
```

### Datensätze

```php
GBDB::insertData("main", "users", ["name" => "Markus"]);
GBDB::getData("main", "users");
GBDB::getData("main", "users", true, "id", 1);
GBDB::editData("main", "users", "id", 1, ["role" => "admin"]);
GBDB::deleteData("main", "users", "id", 1);
GBDB::elementExists("main", "users", "email", "markus@example.com");
```

### GreenQL

```php
GBDB::query("PICK * FROM users IN main;");
```

---

## Datenmodell

Eine GBDB-Base entspricht logisch einer Datenbank.
Eine GBDB-Tabelle entspricht logisch einer Tabelle.
Ein Datensatz ist ein assoziatives Array.

Beispiel:

```php
[
    "id" => 1,
    "name" => "Markus",
    "email" => "markus@example.com",
    "role" => "admin"
]
```

Die Spalte `id` wird vom System als Auto-ID geführt.

---

## Interne Arbeitsweise

GBDB schreibt nicht jeden Zustand immer komplett neu weg. Das System arbeitet mit:

- einer Basisdatei pro Tabelle
- Meta-Datei
- Append-Datei
- optionalen Index-Mappings bei aktivierter Datenverschlüsselung

### Das bringt folgende Vorteile

- weniger komplette Rewrites
- bessere Nachvollziehbarkeit von Änderungen
- kompaktes Insert-/Update-/Delete-Modell
- gezielte Verdichtung per `compactTable()` oder GreenQL `PACK`

---

## Wichtige Methoden im Detail

### `createDatabase(string $name): bool`

Legt eine neue Base an.

```php
GBDB::createDatabase("main");
```

Liefert `true`, wenn die Base erfolgreich erstellt wurde.

---

### `deleteDatabase(string $name): bool`

Löscht eine Base nur dann, wenn sie leer ist. Wenn noch Tabellen enthalten sind, schlägt der Aufruf fehl.

```php
GBDB::deleteDatabase("main");
```

Wenn du alles löschen willst, nutze vorher `deleteAll("main")`.

---

### `createTable(string $database, string $table, array $cols): bool`

Legt eine Tabelle an. Die `id`-Spalte wird intern automatisch berücksichtigt.

```php
GBDB::createTable("main", "users", ["name", "email", "role"]);
```

---

### `insertData(string $database, string $table, mixed $data): int`

Fügt einen neuen Datensatz ein und liefert die neue ID zurück.

```php
$id = GBDB::insertData("main", "users", [
    "name" => "Lea",
    "email" => "lea@example.com",
    "role" => "editor"
]);
```

Wenn der Insert fehlschlägt, kommt `-1` zurück.

---

### `getData(...)`

#### Alle Datensätze

```php
$rows = GBDB::getData("main", "users");
```

#### Einzelnen Datensatz per Filter

```php
$user = GBDB::getData("main", "users", true, "id", 1);
```

Wichtig: Im Filtermodus wird der **erste passende Datensatz** zurückgegeben.

---

### `editData(...)`

```php
GBDB::editData("main", "users", "id", 1, [
    "role" => "admin"
]);
```

Alle Datensätze, die auf `where == is` passen, werden aktualisiert.

---

### `deleteData(...)`

```php
GBDB::deleteData("main", "users", "id", 1);
```

Alle Datensätze, die auf `where == is` passen, werden gelöscht.

---

### `compactTable(...)`

Verdichtet die Tabelle. Vorherige Append-Einträge werden in eine neue Basistabelle eingerechnet.

```php
GBDB::compactTable("main", "users");
```

Das ist vor allem bei vielen Änderungen oder langen Laufzeiten sinnvoll.

---

## GreenQL innerhalb von GBDB

Mit `GBDB::query()` kannst du dieselbe Tabelle per Sprachsyntax ansprechen.

```php
GBDB::query("ROOT main;");
GBDB::query("GROW TABLE users (name, email, role);");
GBDB::query("SEED users WITH name='Markus', email='markus@example.com', role='admin';");
GBDB::query("PICK * FROM users;");
```

Die Query-Engine arbeitet direkt auf der `GBDB`-API. Es gibt also keinen Umweg über eine zweite Schicht mit eigenem Speicherformat.

---

## Rückgabewerte und Verhalten

### Schreibmethoden

- `createDatabase()` -> `bool`
- `deleteDatabase()` -> `bool`
- `createTable()` -> `bool`
- `deleteTable()` -> `bool`
- `insertData()` -> `int`
- `editData()` -> `bool`
- `deleteData()` -> `bool`
- `compactTable()` -> `bool`

### Lesemethoden

- `getData()` -> Array mit Datensätzen oder einzelnes Array bei Filtermodus
- `listDBs()` -> Array der Basen
- `listTables()` -> Array der Tabellen
- `getKeys()` -> Array der Feldnamen
- `nextID()` -> `int`

### Querymethode

- `query()` -> strukturiertes Ergebnis-Array

---

## Best Practices

### 1. Strukturen klar halten

Lege pro fachlichem Bereich eine eindeutige Base-Struktur an, z. B.:

- `main`
- `museumqr`
- `tickets`
- `logs`

### 2. Tabellen sauber benennen

Empfohlen:

- `users`
- `tickets`
- `orders`
- `modules`
- `srv_jobs`

### 3. Felder konsistent halten

Gute Beispiele:

- `created_at`
- `updated_at`
- `status`
- `type`
- `email`
- `role`

### 4. Nach vielen Operationen komprimieren

Wenn viele Inserts, Updates und Deletes gelaufen sind:

```php
GBDB::compactTable("main", "users");
```

### 5. Für komplexere Dev-Workflows GreenQL verwenden

Direkte Methoden sind ideal für Produktivlogik.
GreenQL ist stark für Debugging, Migrations, Devtools, Admin-Oberflächen und lesbare, kompakte Bulk-Aktionen.

---

## Typischer Flow

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "users", ["name", "email", "role"]);

GBDB::insertData("main", "users", [
    "name" => "Markus",
    "email" => "markus@example.com",
    "role" => "admin"
]);

$user = GBDB::getData("main", "users", true, "id", 1);
GBDB::editData("main", "users", "id", 1, ["role" => "owner"]);
$users = GBDB::getData("main", "users");
```

---

## Zusammenspiel mit der UI

`greenql_ui.php` ist im Dev-Bereich die visuelle Arbeitsoberfläche für GBDB. Dort kannst du:

- Basen anlegen/löschen
- Tabellen anlegen/umbauen/löschen
- Entries anlegen/bearbeiten/löschen
- GreenQL direkt ausführen
- Live-Preview und Schema prüfen

Die UI ist kein separates System, sondern nur ein Frontend für dieselbe GBDB- und GreenQL-Logik.
