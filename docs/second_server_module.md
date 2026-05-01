# SecondServerModul – ausführliche Dokumentation

## Zweck

Das SecondServerModul erlaubt es, ein Projekt in zwei Ebenen zu betreiben:

- **Server 1 / Client-Seite**: Enthält die Anwendung, die `SrvP` nutzt.
- **Server 2 / Backend-Seite**: Enthält `backend.php`, `Srv`, GBDB/GBDBv2 und optionale Module.

Dadurch kann eine Anwendung Daten, Jobs, Auth oder GreenQL-Scripte remote ausführen, ohne dass die Frontend-Seite direkten Dateisystemzugriff auf die Backend-Daten braucht.

## Beteiligte Dateien

```text
assets/php/inc/gbdb_framework/core/srvp.php   Client-Klasse
backend.php                                   JSON-Endpunkt auf Server 2
assets/php/inc/Srv.php                        Backend-Service-Klasse
functions.php                                 Backend-Helfer, DB-Routing, Tokenlogik
assets/php/srv_modules/                       optionale Job-Module
```

## Authentifizierungsablauf

1. `SrvP` baut die Backend-URL aus `Vars::srvp_ip()` und `Vars::srvp_ssl()`.
2. `SrvP` fordert mit `do=gtoken` und Hash des Static-Keys ein Einmal-Token an.
3. Das Backend erzeugt ein Token und speichert es verschlüsselt/geschützt in `_srvtkns.cry`.
4. `SrvP` sendet den eigentlichen Request mit `sauth` und `token`.
5. `backend.php` prüft Methode, Static-Key und Token.
6. Das Token wird verbraucht und kann nicht erneut genutzt werden.

## Kontext und Instanzen

Für GBDBv2 kann ein Kontext übergeben werden:

```php
SrvP::setInstance('kunde_a');
SrvP::createDatabase('main');
```

Alternativ pro Aufruf:

```php
SrvP::getData('main', 'users', false, '', '', ['instance' => 'kunde_a']);
```

## Unterstützte Backend-Aktionen

- `driver` – aktiven Treiber und Instanzstatus prüfen.
- `instances`, `create_instance`, `delete_instance` – GBDBv2-Instanzen verwalten.
- `bases`, `tables`, `create_base`, `delete_base`, `create_table`, `delete_table` – Struktur verwalten.
- `get`, `put`, `edit`, `delete`, `keys` – Datenoperationen.
- `query` – GreenQL/GreenQLv2 ausführen.
- `runscript` – Script auf Backend-Seite lesen und ausführen.
- `auth` – Remote-Auth-Funktionen nutzen.
- `srv_enqueue`, `srv_run_one`, `srv_status`, `srv_logs`, `srv_jobs` – Job-System nutzen.

## Beispiel: Remote-Tabelle anlegen

```php
include 'assets/php/inc/.config/_config.inc.php';

SrvP::setInstance('kunde_a');
SrvP::createDatabase('main');
SrvP::createTable('main', 'logs', ['type', 'message', 'created']);
SrvP::insertData('main', 'logs', [
    'type' => 'info',
    'message' => 'remote write ok',
    'created' => date('Y-m-d H:i:s')
]);
```

## Beispiel: Script remote ausführen

```php
$result = SrvP::runScript('scripts/greenql/makeUser.gql', [
    'uid' => 'u001',
    'username' => 'markus'
]);
```

Wichtig: Die Datei muss auf dem Backend-Server existieren. Der Client übermittelt nur den Pfad. `Srv::runScript()` prüft die Datei backendseitig.

## Sicherheit

- Static-Key niemals kurz oder erratbar wählen.
- `backend.php` nicht öffentlich dokumentieren.
- Logs nicht mit Secrets befüllen.
- Script-Ausführung nur mit erlaubten Pfaden/geschützter UI anbieten.
- Bei Multi-Tenant-Nutzung immer Kontext/Instanz prüfen.

## Debugging

### `Empty response from backend`

Backend nicht erreichbar, PHP-Fehler, falsche URL oder falsches Protokoll.

### `Static auth failed`

`Vars::srvp_static_key()` unterscheidet sich zwischen Client und Backend.

### `Token auth failed`

Token-Datei nicht schreibbar, Token wurde schon verbraucht oder Systemzeit/Request-Ablauf ist fehlerhaft.

### `Script not found on backend`

Pfad existiert auf Client-Seite vielleicht, aber nicht auf Server 2. Scriptpfade immer aus Sicht des Backends prüfen.
