# GreenQL Language – ausführliche Sprachreferenz

## Zweck

GreenQL ist eine kleine, menschenlesbare Query- und Script-Sprache für GBDB. Sie ist nicht als SQL-Ersatz für große relationale Datenbanken gedacht, sondern als einfache Sprache für Admin-UIs, Seeds, Migrationen, Remote-Scripte und wiederholbare Datenbankoperationen.

## Grundregeln

- Befehle werden mit Semikolon abgeschlossen.
- Kommentare können vor der Ausführung entfernt werden.
- Namen werden bereinigt, damit keine gefährlichen Pfade entstehen.
- Strings können in Anführungszeichen geschrieben werden.
- Parameter können aus einem PHP-Array übergeben werden.

## Typischer Aufbau

```greenql
ROOT main;
GROW TABLE users (uid, username, email);
SEED users WITH uid="u001", username="markus", email="markus@example.test";
SELECT * FROM users;
```

## Instanzen mit GreenQLv2

```greenql
INSTANCE kunde_a;
ROOT main;
SELECT * FROM users;
```

`INSTANCE` wählt bei GBDBv2 den Mandanten/Datenraum. Ohne Instanz wird der normale GBDB-Kontext genutzt.

## Wichtige Befehle

### ROOT

Wählt die aktive Base/Datenbank.

```greenql
ROOT main;
```

### GROW TABLE

Legt eine Tabelle mit Spalten an.

```greenql
GROW TABLE users (uid, username, email, role);
```

### SEED

Fügt einen Datensatz ein.

```greenql
SEED users WITH uid="u001", username="markus", role="admin";
```

### SELECT

Liest Datensätze.

```greenql
SELECT * FROM users;
SELECT uid, username FROM users WHERE role="admin";
```

### EDIT

Ändert Datensätze anhand einer Bedingung.

```greenql
EDIT users SET role="dev" WHERE uid="u001";
```

### DELETE

Löscht Datensätze anhand einer Bedingung.

```greenql
DELETE FROM users WHERE uid="u001";
```

### PARAMETER

Parameter werden nicht direkt aus dem Script gelesen, sondern über das PHP-Array an `run()` übergeben.

```php
GreenQLv2::run('SEED users WITH uid=$uid, username=$username;', [], [
    'uid' => 'u001',
    'username' => 'markus'
]);
```

## Rückgaben

GreenQL gibt ein Array mit Ergebnissen der einzelnen Befehle zurück. Bei UI- oder API-Nutzung sollte nicht blind nur das letzte Element verwendet werden, sondern geprüft werden, welcher Befehl welches Ergebnis geliefert hat.

## Empfehlungen

- Für Seeds jeden Datensatz als eigenen `SEED` schreiben. Das ist besser lesbar und einfacher zu debuggen.
- Vor produktiven Migrationen Backup erstellen.
- In Remote-Scripten keine Secrets hardcoden.
- Tabellen- und Spaltennamen kurz, eindeutig und ASCII-kompatibel halten.
- Bei GBDBv2 immer bewusst `INSTANCE` setzen, wenn die Query mandantenbezogen ist.
