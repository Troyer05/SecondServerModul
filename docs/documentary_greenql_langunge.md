# Documentary: GreenQL Language

## Idee

GreenQL ist die eigene Query- und Scriptsprache des GBDB Frameworks.

Sie ist bewusst nicht als SQL-Klon gedacht. Die Sprache soll lesbar bleiben, aber direkt auf den GBDB-Core arbeiten.

## Kommandos im Überblick

### Kontext

#### `ROOT`

```gql
ROOT main;
```

Setzt die aktive Base.

#### `BRANCH`

```gql
BRANCH users;
```

Setzt die aktive Tabelle.

### Anzeigen

#### `SHOW BASES`

```gql
SHOW BASES;
```

Listet alle Basen.

#### `SHOW TABLES`

```gql
SHOW TABLES;
SHOW TABLES IN main;
```

Listet Tabellen der aktiven oder angegebenen Base.

#### `DESCRIBE`

```gql
DESCRIBE users;
DESCRIBE users IN main;
```

Zeigt die Struktur einer Tabelle.

#### `PEEK`

```gql
PEEK users;
PEEK users IN main LIMIT 20;
```

Schnelle Vorschau einer Tabelle.

#### `PICK`

```gql
PICK * FROM users;
PICK uid, name FROM users IN main;
PICK * FROM users IN main WHERE role = 'admin';
PICK * FROM users IN main WHERE name ~= 'mar';
PICK * FROM users IN main SORT id DESC LIMIT 25;
```

`PICK` ist die flexible Abfrage.

Unterstützte Operatoren in `WHERE`:

- `=`
- `==`
- `!=`
- `>`
- `<`
- `>=`
- `<=`
- `~=`

### Struktur

#### `GROW BASE`

```gql
GROW BASE main;
```

Erstellt eine Base.

#### `DROP BASE`

```gql
DROP BASE main;
```

Löscht eine Base, wenn sie leer ist.

#### `GROW TABLE`

```gql
GROW TABLE users (uid, name, email) IN main;
```

Erstellt eine Tabelle.

#### `DROP TABLE`

```gql
DROP TABLE users IN main;
```

Löscht eine Tabelle.

#### `PACK`

```gql
PACK users IN main;
```

Kompaktiert eine Tabelle.

### Daten

#### `SEED`

```gql
SEED users WITH uid='u_1001', name='Markus', email='markus@example.com' IN main;
```

Legt einen Datensatz an.

#### `RESHAPE`

```gql
RESHAPE users WITH role='editor' WHERE id = 1 IN main;
```

Ändert Datensätze.

Aktuell für Schreibzugriffe nur mit `WHERE feld = wert` oder `==`.

#### `ERASE`

```gql
ERASE FROM users WHERE id = 1 IN main;
```

Löscht Datensätze.

Aktuell für Schreibzugriffe nur mit `WHERE feld = wert` oder `==`.

## Kommentare

```gql
# Kommentarzeile
SEED users WITH name="Markus" IN main; # Inline-Kommentar
```

Zusätzlich werden auch `//`-Inline-Kommentare außerhalb von Strings ignoriert.

## Variablen

### Syntax

```gql
declare _name = "Markus";
declare _uid = param("uid");
```

Der aus deinem Beispiel bekannte Schreibfehler wird auch toleriert:

```gql
decalre _name = "Markus";
```

### Parameternutzung

```gql
declare _name = param("name");
declare _email = param("email");
```

Die Parameter werden beim Aufruf übergeben:

```php
GBDB::runScript("scripts/greenql/makeUser.gql", [
    "name" => $name,
    "email" => $email
]);
```

### Wo Variablen eingesetzt werden können

```gql
declare _base = "main";
declare _table = "users";
declare _state = "Aktiv";

ROOT _base;
GROW TABLE _table (uid, name, status) IN _base;
SEED _table WITH status=_state IN _base;
```

Variablen funktionieren aktuell in:

- Base-Namen
- Tabellennamen
- Spaltenlisten, wenn das Token als Variablenname vorliegt
- `WITH`-Werten
- `WHERE`-Werten
- `SORT`-Feldern

## Beispielscript

```gql
# Parameter empfangen
declare _name = param("name");
declare _username = param("username");
declare _email = param("email");
declare _password = param("password");
declare _uid = param("uid");

declare _state = "Aktiv";

SEED users WITH uid=_uid, name=_name, username=_username, email=_email, password=_password IN main;
GROW TABLE _uid (firma, status) IN main;
GROW BASE _state;

declare _state = "Inaktiv";
GROW BASE _state;
```

## Ausführungsmöglichkeiten

### In PHP direkt

```php
GBDB::query("PICK * FROM users IN main;");
```

### Aus Datei

```php
GBDB::runScript("scripts/greenql/makeUser.gql", ["uid" => "u_1001"]);
```

### Remote

```php
SrvP::query("PICK * FROM users IN main;");
SrvP::runScript("scripts/greenql/makeUser.gql", ["uid" => "u_1001"]);
```

### In der UI

- Query im Query Mode einfügen
- oder `.gql` Datei im Query Mode hochladen und ausführen

## Rückgabewerte

Jede Query bzw. jedes Script liefert:

- Status `ok`
- Meldungen `messages`
- Resultsets `results`
- letztes Tabellenresultat in `keys` und `rows`
- Kontext `ctx`
- Variablenzustand `vars`
- `refresh`, wenn sich Struktur oder Daten verändert haben
