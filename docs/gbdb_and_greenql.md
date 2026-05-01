# GBDB und GreenQL zusammen nutzen

## Zusammenhang

GBDB ist die Datenbank-Engine, GreenQL ist die Script-Sprache darüber. Jede GreenQL-Operation wird am Ende in GBDB-Methoden wie `createDatabase`, `createTable`, `insertData`, `getData`, `editData` oder `deleteData` übersetzt.

## Wann direkt GBDB nutzen?

Direkte GBDB-Aufrufe eignen sich, wenn Anwendungscode klar strukturierte Operationen ausführt:

```php
GBDB::insertData('main', 'users', ['uid' => 'u001']);
```

Vorteile:

- Typisch für PHP-Code am schnellsten nachvollziehbar.
- Parameter sind normale Arrays.
- Weniger Parser-Overhead.

## Wann GreenQL nutzen?

GreenQL eignet sich, wenn Operationen als Script gespeichert, remote ausgeführt oder von einer Admin-UI bearbeitet werden sollen:

```greenql
ROOT main;
GROW TABLE users (uid, username);
SEED users WITH uid="u001", username="markus";
```

Vorteile:

- Gut für Seeds und Migrationen.
- Gut für wiederholbare Setup-Scripte.
- Gut für Remote-Ausführung mit `SrvP::query()` oder `SrvP::runScript()`.

## GBDBv2 und GreenQLv2

Bei Multi-Instanz-Projekten sollte `GreenQLv2` genutzt werden. Instanzen trennen Daten sauber voneinander:

```greenql
INSTANCE kunde_a;
ROOT main;
SELECT * FROM settings;
```

## Best Practices

- Strukturänderungen in Scripts dokumentieren.
- Große Datenimporte lieber chunkweise einspielen.
- Vor `DELETE` oder `EDIT` immer mit `SELECT` prüfen, ob die WHERE-Bedingung korrekt ist.
- Remote-Scripte nur aus vertrauenswürdigen Quellen ausführen.
- Nach Schema-Änderungen `schema.json` prüfen.
