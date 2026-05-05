# Please come back later
This Project is under a completly new rework....


# SecondServerModul / greenbucket® GBDB Framework

## Überblick

Dieses Repository enthält ein vollständiges PHP-Framework rund um die greenbucket® GBDB-Dateidatenbank, GreenQL, Authentifizierung, Remote-Backend-Kommunikation und optionale Produkt-Plugins. Das System ist bewusst ohne große Build-Pipeline gehalten und kann klassisch auf Apache/PHP betrieben werden.

Die wichtigsten Bausteine sind:

- **GBDB** als dateibasierte JSON-Datenbank.
- **GBDBv2** als instanzfähige Variante für getrennte Mandanten/Projekte.
- **GreenQL / GreenQLv2** als kleine Script- und Query-Sprache für Datenbankoperationen.
- **Auth** als Login-, JWT-, Mail-Verifikation- und 2FA-System.
- **SrvP** als Client-Klasse für Remote-Zugriffe auf einen zweiten Server.
- **backend.php + Srv** als Empfänger und Ausführer auf dem Backend-Server.
- **mRootLicense / mRootUpdate** für Lizenz- und Update-Prozesse.
- **API-Plugins** für ShareSuite, MuseumQR und EventQR.

## Schnellstart

```php
include 'assets/php/inc/.config/_config.inc.php';

GBDB::createDatabase('main');
GBDB::createTable('main', 'users', ['uid', 'username', 'email']);
GBDB::insertData('main', 'users', [
    'uid' => 'u001',
    'username' => 'markus',
    'email' => 'markus@example.test'
]);

$users = GBDB::getData('main', 'users');
```

## Grundstruktur

```text
assets/php/inc/.config/_config.inc.php   zentrale Projekt-Einbindung
assets/php/inc/gbdb_framework/ENV.php    Vars-Konfiguration
assets/php/inc/gbdb_framework/core/      Core-Klassen
assets/php/inc/gbdb_framework/plugins/   Produkt-/API-Plugins
assets/php/inc/Srv.php                   Backend-Service-Modul
backend.php                              Remote-API-Endpunkt
functions.php                            Backend-Helfer und Tokenlogik
docs/                                    ausführliche Entwicklerdokumentation
```

## Installation

1. Repository in den Webroot legen.
2. PHP mit `openssl`, `json`, `curl` und Schreibrechten für `assets/DB` bereitstellen.
3. `assets/php/inc/gbdb_framework/ENV.php` prüfen und projektbezogene Werte setzen.
4. Bei SecondServer-Nutzung `Vars::srvp_ip()`, `Vars::srvp_ssl()` und `Vars::srvp_static_key()` auf beiden Seiten passend setzen.
5. `assets/php/inc/.config/_config.inc.php` im Anwendungscode einbinden.

## Wichtige Sicherheitsregeln

- `backend.php` nur mit starkem `srvp_static_key` betreiben.
- Keine produktiven API-Keys oder Lizenzdaten ins öffentliche Repository committen.
- `assets/DB` nicht öffentlich ausliefern, falls der Webserver Dateilisten oder direkte Downloads erlaubt.
- Schreibrechte so setzen, dass PHP schreiben kann, aber nicht pauschal alles `777` ist.
- Update- und Script-Ausführung nur hinter Auth/Admin-UI anbieten.

## Typische Arbeitsabläufe

### Lokale GBDB-Tabelle erstellen

```php
GBDB::createDatabase('main');
GBDB::createTable('main', 'settings', ['key', 'value']);
GBDB::insertData('main', 'settings', ['key' => 'theme', 'value' => 'dark']);
```

### Instanz mit GBDBv2 nutzen

```php
GBDBv2::createInstance('kunde_a');
GBDBv2::setInstance('kunde_a');
GBDBv2::createDatabase('main');
```

### GreenQL ausführen

```php
$result = GBDB::query('
ROOT main;
GROW TABLE settings (key, value);
SEED settings WITH key="theme", value="dark";
SELECT * FROM settings;
');
```

### Remote über SrvP schreiben

```php
SrvP::setInstance('kunde_a');
SrvP::createDatabase('main');
SrvP::createTable('main', 'logs', ['type', 'message']);
SrvP::insertData('main', 'logs', ['type' => 'info', 'message' => 'remote ready']);
```

## Dokumentation

Die ausführlichen Einzeldateien liegen im Ordner `docs/`. Besonders relevant:

- `docs/gbdb.md` für die Dateidatenbank.
- `docs/greenql.md` und `docs/documentary_greenql_langunge.md` für GreenQL.
- `docs/srvp.md`, `docs/srv.md` und `docs/second_server_module.md` für Remote-Nutzung.
- `docs/auth.md` für Benutzer, Login, JWT und 2FA.
- `docs/classes_index.md` als Klassenübersicht.
- `docs/gbdbv2.md`, `docs/greenqlv2.md` und `docs/eventqr.md` für ergänzende v2-/API-Module.

## Wartung

Bei neuen Klassen oder Methoden sollten die passenden `.md`-Dateien im `docs/`-Ordner aktualisiert werden. Bei Änderungen an GBDB-Tabellen gehört auch `schema.json` geprüft. Für Releases sollte das Update-System mit einem echten ZIP-Release und einer Versionsprüfung getestet werden.
