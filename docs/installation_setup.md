# Installation, Setup und Betrieb

## Voraussetzungen

Empfohlen wird PHP 8.1 oder neuer. Das Framework nutzt moderne Typen wie `mixed`, Union Types und statische Klassen. Ein Apache- oder Nginx-Setup reicht aus. Für klassische GBDB-Projekte ist keine SQL-Datenbank zwingend nötig.

## Einbindung in Seiten

Am Anfang einer PHP-Seite reicht normalerweise:

```php
<?php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";

Auth::init();
$rows = GBDB::getData("main", "settings");
```

## Schreibrechte

GBDB schreibt unter `assets/DB/`. Der Webserver-Benutzer muss in diesem Ordner schreiben können. Typisch unter Debian/Apache:

```bash
sudo chown -R deinuser:www-data /var/www/html/projekt
sudo chmod -R 2775 /var/www/html/projekt/assets/DB
sudo setfacl -R -m g:www-data:rwx /var/www/html/projekt/assets/DB
sudo setfacl -R -d -m g:www-data:rwx /var/www/html/projekt/assets/DB
```

## Erster Datenbanktest

```php
GBDB::createDatabase("main");
GBDB::createTable("main", "settings", ["key", "value"]);
GBDB::insertData("main", "settings", [
    "key" => "app_name",
    "value" => "Demo"
]);

print_r(GBDB::getData("main", "settings"));
```

## Instanztest mit GBDBv2

```php
GBDBv2::createInstance("kunde_a");
GBDBv2::setInstance("kunde_a");
GBDBv2::createDatabase("main");
GBDBv2::createTable("main", "items", ["title", "active"]);
```

## GreenQL-Test

```php
$result = GreenQLv2::runScript("scripts/greenql/greenql_feature_demo.gql");
print_r($result);
```

Alternativ direkt:

```php
$result = GBDB::query('
    GROW BASE main;
    ROOT main;
    GROW TABLE users WITH uid, username, email;
    SEED users WITH {"uid":"u1","username":"admin","email":"admin@example.com"};
    PICK * FROM users;
');
```

## Typische Fehlerquellen

| Fehler | Ursache | Lösung |
|---|---|---|
| Tabellen werden nicht erstellt | keine Schreibrechte in `assets/DB` | Rechte prüfen, ACL setzen. |
| Remote `SrvP` antwortet nicht | falscher Endpoint oder Auth-Key | `Vars::srvp_ip()`, `Vars::srvp_ssl()`, `Vars::srvp_static_key()` prüfen. |
| GreenQL UI zeigt interne Daten | UI-Helper/Userrechte prüfen | `GreenQLUIv2Helper` nutzt reservierte Systeminstanz und Filterlogik. |
| Public API blockiert Requests | Feature-Gates in `Vars::PUBLIC_API` | `pApi_*` Einstellungen prüfen. |
| Session/Cookie-Login instabil | Pfad/Secure/SameSite falsch | `Vars::INIT_SESSION()` und `Vars::INIT_COOKIES()` prüfen. |
