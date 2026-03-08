# Documentary GreenQL Language

## Überblick

GreenQL ist die eigene Query-Sprache des GBDB Frameworks. Sie ist dafür gebaut, GBDB-Basen, Tabellen und Datensätze ohne klassisches SQL zu steuern.

GreenQL kann verwendet werden über:

- `GBDB::query(...)`
- `SrvP::query(...)`
- `greenql_ui.php` im Query Mode

---

## Grundidee

GreenQL arbeitet command-basiert. Ein Script kann aus einem oder mehreren Befehlen bestehen.
Befehle werden mit `;` getrennt.

Beispiel:

```php
GBDB::query("ROOT main; SHOW TABLES;");
```

---

## Unterstützte Commands

### `ROOT <base>`

Setzt die aktive Base im Kontext.

```txt
ROOT main;
```

---

### `BRANCH <table>`

Setzt die aktive Tabelle im Kontext.

```txt
BRANCH users;
```

---

### `SHOW BASES`

Listet alle vorhandenen Basen.

```txt
SHOW BASES;
```

Rückgabe-Spalten:

- `base`
- `tables`
- `rows`

---

### `SHOW TABLES`

Listet alle Tabellen der aktiven oder explizit angegebenen Base.

```txt
SHOW TABLES;
SHOW TABLES IN main;
```

Rückgabe-Spalten:

- `table`
- `fields`
- `rows`

---

### `GROW BASE <base>`

Erstellt eine neue Base.

```txt
GROW BASE main;
```

---

### `DROP BASE <base>`

Löscht eine Base, wenn sie leer ist.

```txt
DROP BASE logs;
```

---

### `GROW TABLE <table> (<fields>) [IN <base>]`

Erstellt eine Tabelle.

```txt
GROW TABLE users (name, email, role) IN main;
```

Hinweise:

- `id` wird intern automatisch geführt.
- Feldnamen werden bereinigt.

---

### `DROP TABLE <table> [IN <base>]`

Löscht eine Tabelle.

```txt
DROP TABLE users IN main;
```

---

### `DESCRIBE <table> [IN <base>]`

Zeigt die Struktur der Tabelle.

```txt
DESCRIBE users IN main;
```

Rückgabe-Spalten:

- `field`
- `kind`

---

### `PACK <table> [IN <base>]`

Verdichtet die Tabelle und schreibt den aktuellen Zustand als frische Basisdatei.

```txt
PACK users IN main;
```

---

### `PEEK <table> [IN <base>] [LIMIT <n>]`

Schnelle Vorschau einer Tabelle.

```txt
PEEK users;
PEEK users IN main LIMIT 20;
```

Standardlimit: `50`

---

### `PICK <fields> FROM <table> [IN <base>] [WHERE ...] [SORT <field> ASC|DESC] [LIMIT <n>]`

Liest Datensätze aus einer Tabelle.

```txt
PICK * FROM users IN main;
PICK id, name, role FROM users IN main;
PICK id, name FROM users WHERE role = 'admin' SORT id DESC LIMIT 20;
```

#### Unterstützte Vergleichsoperatoren im `WHERE`

- `=`
- `==`
- `!=`
- `>`
- `<`
- `>=`
- `<=`
- `~=`

`~=` bedeutet Teilstring-Suche, case-insensitive.

Beispiel:

```txt
PICK * FROM users WHERE name ~= 'mar';
```

---

### `SEED <table> WITH <assignments> [IN <base>]`

Fügt einen neuen Datensatz ein.

```txt
SEED users WITH name='Markus', email='markus@example.com', role='admin' IN main;
```

#### Assignment-Syntax

```txt
feld='wert', anderes_feld=123, aktiv=true
```

Unterstützt:

- Strings in `'...'` oder `"..."`
- Zahlen
- `true`
- `false`
- `null`

---

### `RESHAPE <table> WITH <assignments> WHERE <condition> [IN <base>]`

Aktualisiert Datensätze.

```txt
RESHAPE users WITH role='editor' WHERE id = 1 IN main;
```

Aktuell gelten für Schreibzugriffe folgende Einschränkungen:

- `WHERE` muss gültig sein
- unterstützt für Schreibzugriffe aktuell nur `=` bzw. `==`

---

### `ERASE FROM <table> WHERE <condition> [IN <base>]`

Löscht Datensätze.

```txt
ERASE FROM users WHERE id = 1 IN main;
```

Auch hier gilt aktuell bei Schreibzugriffen:

- nur `=` bzw. `==`

---

## Kontextnutzung

Wenn du `ROOT` und `BRANCH` setzt, kannst du viele Commands kürzer formulieren.

```txt
ROOT main;
BRANCH users;
PICK * FROM users;
SEED users WITH name='Lea', role='editor';
```

Der Kontext wird nach jedem Command aktualisiert und in der Rückgabe unter `ctx` mitgegeben.

---

## Mehrere Commands in einem Script

```txt
GROW BASE main;
ROOT main;
GROW TABLE users (name, email, role);
SEED users WITH name='Markus', email='markus@example.com', role='admin';
PICK * FROM users;
```

Bei Fehlern stoppt die Ausführung an der ersten fehlerhaften Stelle.

---

## Rückgabeformat

Beispiel:

```php
$result = GBDB::query("PICK id, name FROM users IN main LIMIT 10;");
```

Wichtige Felder im Rückgabe-Array:

- `ok` -> Gesamtstatus
- `messages` -> Meldungen pro Command
- `results` -> alle Result-Sets
- `keys` -> Feldliste des letzten Result-Sets
- `rows` -> Datensätze des letzten Result-Sets
- `ctx` -> aktueller Kontext
- `refresh` -> zeigt Struktur-/Datenänderungen an

---

## Beispiele

### Beispiel 1: Struktur aufbauen

```txt
GROW BASE main;
ROOT main;
GROW TABLE users (name, email, role);
```

### Beispiel 2: Testdaten einfügen

```txt
SEED users WITH name='Markus', email='markus@example.com', role='admin' IN main;
SEED users WITH name='Lea', email='lea@example.com', role='editor' IN main;
```

### Beispiel 3: Lesen

```txt
PICK * FROM users IN main;
PICK id, name FROM users IN main WHERE role = 'admin';
PICK id, name FROM users IN main SORT id DESC LIMIT 20;
```

### Beispiel 4: Bearbeiten

```txt
RESHAPE users WITH role='owner' WHERE id = 1 IN main;
```

### Beispiel 5: Löschen

```txt
ERASE FROM users WHERE id = 2 IN main;
```

### Beispiel 6: Schema prüfen

```txt
DESCRIBE users IN main;
```

### Beispiel 7: Tabellenzustand verdichten

```txt
PACK users IN main;
```

---

## Typische Fehlerquellen

### Keine Base aktiv

Bei Commands wie `SHOW TABLES`, `GROW TABLE`, `PICK`, `SEED`, `RESHAPE`, `ERASE` ohne `IN <base>` muss entweder eine Base im Kontext stehen oder die Base explizit angegeben werden.

### Falsche Feldnamen

Feldnamen werden bereinigt. Sonderzeichen außerhalb des erlaubten Namensschemas werden entfernt.

### Leere Assignments

Bei `SEED` und `RESHAPE` müssen echte Werte übergeben werden.

### Ungültiges WHERE

`PICK` ist flexibler.
`RESHAPE` und `ERASE` sind derzeit absichtlich restriktiver.

---

## GreenQL lokal und remote

### Lokal

```php
GBDB::query("PICK * FROM users IN main;");
```

### Remote

```php
SrvP::query("PICK * FROM users IN main;");
```

Dadurch kannst du dieselbe Sprache in Dev-UI, Framework-Code und SecondServer nutzen.

---

## Kurzreferenz

```txt
ROOT main;
BRANCH users;
SHOW BASES;
SHOW TABLES IN main;
GROW BASE main;
DROP BASE logs;
GROW TABLE users (name, email, role) IN main;
DROP TABLE users IN main;
DESCRIBE users IN main;
PACK users IN main;
PEEK users IN main LIMIT 20;
PICK * FROM users IN main;
PICK id, name FROM users WHERE role = 'admin' SORT id DESC LIMIT 10;
SEED users WITH name='Markus', email='markus@example.com' IN main;
RESHAPE users WITH role='owner' WHERE id = 1 IN main;
ERASE FROM users WHERE id = 1 IN main;
```
