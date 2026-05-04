# SecondServerModul – Remote-Architektur

## Zweck

Das SecondServerModul erlaubt einem Server, sicher Aktionen auf einem zweiten Server auszuführen. Typische Gründe:

- Daten liegen auf einem getrennten Server.
- Ein Backend soll mehrere Frontends bedienen.
- Jobs sollen zentral verarbeitet werden.
- Auth- oder GBDBv2-Funktionen sollen remote nutzbar sein.

## Rollen

| Rolle | Klasse/Datei | Aufgabe |
|---|---|---|
| Client-Server | `SrvP` | baut JSON-Requests, holt Einmal-Token, sendet Aktionen. |
| Ziel-Server | `backend.php` | nimmt Requests entgegen, prüft Auth, dispatcht Aktionen. |
| Backend-Service | `Srv` | führt Jobs, Auth, Scripte und Module aus. |
| Hilfsfunktionen | `functions.php` | Response, Auth, Context, DB-Dispatch. |

## Request-Ablauf

1. `SrvP` ermittelt Endpoint aus `Vars::srvp_ip()` und `Vars::srvp_ssl()`.
2. `SrvP` fordert mit `do=gtoken` und statischer Auth einen Einmal-Token an.
3. Backend erzeugt/prüft Token in `assets/DB/framework_temp/_srvtkns.cry`.
4. `SrvP` sendet eigentlichen Request mit `sauth`, `token`, `do` und optional `ctx`.
5. `backend.php` dispatcht auf GBDB, GBDBv2, Auth oder Srv.
6. Response kommt als JSON-Envelope zurück.

## Context

Der Context steuert z. B. Instanz oder Treiber:

```php
SrvP::setContext([
    "instance" => "kunde_a",
    "driver" => "GBDBv2"
]);
```

Oder kurz:

```php
SrvP::setInstance("kunde_a");
```

## Beispiel: Remote Daten lesen

```php
SrvP::setInstance("kunde_a");
$users = SrvP::getData("main", "users");
```

## Beispiel: Remote GreenQL

```php
$result = SrvP::query('
    ROOT main;
    PICK * FROM users LIMIT 10;
', ["instance" => "kunde_a"]);
```

## Beispiel: Remote Auth

```php
$login = SrvP::auth_login("admin", "password");
if (($login["ok"] ?? false) === true) {
    $jwt = $login["data"]["jwt"] ?? "";
}
```

## Sicherheit

- `Vars::srvp_static_key()` muss auf beiden Servern übereinstimmen.
- Einmal-Tokens sind kurzlebig und sollten nicht geloggt werden.
- HTTPS ist empfohlen, wenn stabil nutzbar.
- Backend-Datei nicht öffentlich dokumentieren, wenn nicht nötig.
- Logs dürfen keine Secrets enthalten.

## Advanced-GBDB Remote-Funktionen

SrvP/Backend unterstützen jetzt auch die neuen Advanced-Funktionen für GBDB und GBDBv2. Bei GBDBv2 kann wie gewohnt ein Kontext mit `SrvP::setInstance("kunde1")` oder über den letzten `$ctx`-Parameter übergeben werden.

```php
SrvP::instance_exists("kunde1");
SrvP::base_exists("main");
SrvP::table_exists("main", "users");
SrvP::data_exists("main", "users", "email", "max@example.de");

SrvP::monitor();
SrvP::monitor("main", "users");
SrvP::recover("main", "users");
SrvP::page("main", "users", 1, 50);
SrvP::cursor("main", "users", 100);
SrvP::fulltext_search("main", "users", "Max Muster", ["username", "email"], 25);
```

Backend-`do`-Actions:

- `instance_exists`
- `base_exists`
- `table_exists`
- `data_exists`
- `monitor`
- `recover`
- `page`
- `cursor`
- `fulltext_search`

`fulltext` bleibt als Backend-Kompatibilitätsalias erhalten. Die bevorzugte neue PHP-Methode ist `fulltext_search`.
