# SecondServer Module

## Überblick

Das SecondServer Module ist die Remote-Schicht des Frameworks. Es erlaubt, dieselbe Datenbasis-Logik über HTTP anzusprechen, ohne dass der Client direkt lokal auf die GBDB-Dateien zugreift.

Wichtig ist: Die Remote-Schicht ist nicht "anders" als lokal. Sie bildet die lokale Logik nach.

- lokal: `GBDB::*`
- remote: `SrvP::*`

## API-Prinzip

`SrvP` baut Requests an `backend.php` und übernimmt dabei Auth, Token-Erzeugung und Transport.

### Direktmethoden

```php
SrvP::getData("main", "users");
SrvP::addData("main", "users", ["name" => "Markus"]);
SrvP::editData("main", "users", "id", 1, ["name" => "MM"]);
SrvP::deleteData("main", "users", "id", 1);
```

### Remote-GreenQL

```php
SrvP::query("PICK * FROM users IN main;");
SrvP::query("SEED users WITH name='Lea' IN main;");
```

### Remote-Scripts

```php
SrvP::runScript("scripts/greenql/makeUser.gql", [
    "uid" => $uid,
    "name" => $name,
    "username" => $username,
    "email" => $email,
    "password" => $password
]);
```

`SrvP::runScript()` liest das Script lokal in deinem Projekt und sendet den Scriptinhalt an die Remote-API, wo dieselbe GreenQL-Engine ausgeführt wird.

## Wichtige Methoden

### `SrvP::query()`

```php
SrvP::query(string $script, array $ctx = [], array $params = []): array
```

### `SrvP::runScript()`

```php
SrvP::runScript(string $path, array $params = [], array $ctx = []): array
```

## Unterstützte API-`do` Werte

- `get`
- `put`
- `edit`
- `delete`
- `query`
- `srv_enqueue`
- `srv_run_one`
- `srv_status`
- `srv_logs`
- `srv_jobs`

## `query` Payload

```json
{
  "do": "query",
  "query": "PICK * FROM users IN main;",
  "ctx": {
    "db": "main",
    "table": "users"
  },
  "params": {
    "uid": "u_1001"
  }
}
```

## Response-Struktur

Die Remote-Antwort wird in das API-Standardformat gewrappt:

```php
[
    "ok" => true,
    "status" => 200,
    "data" => [
        "ok" => true,
        "messages" => [...],
        "results" => [...],
        "keys" => [...],
        "rows" => [...],
        "ctx" => [...],
        "vars" => [...],
        "refresh" => true
    ]
]
```

## Unterschiede lokal vs. remote

### Lokal

- direkte Dateizugriffe
- schneller im selben Projekt
- ideal für serverseitige App-Logik

### Remote

- Zugriff über HTTP
- gut für getrennte Systeme, Tools, Clients oder Services
- ideal wenn ein anderer Server dieselbe Datenbasis kontrolliert

## Wann GreenQL remote besonders stark ist

- zentrale Admin-Tasks
- Datenimport-Skripte
- Setup-/Seed-Scripts auf Zielsystemen
- standardisierte Automations-Abläufe
- dieselben Scripts in mehreren Projekten oder Deployments

## Sicherheit

Die API nutzt:

- `sauth` per statischem Hash
- Einmal-Token
- serverseitige Prüfung in `general_auth()`

Das heißt: Der Client bekommt kurzlebige Tokens und jeder Request wird validiert.

## Best Practice

- direkte CRUD-Aufrufe für einfache Einzelaktionen
- `SrvP::query()` für Serienlogik
- `SrvP::runScript()` für wiederverwendbare Geschäftsabläufe
- Scripts versionieren und sauber benennen
