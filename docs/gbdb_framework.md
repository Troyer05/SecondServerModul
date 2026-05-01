# GBDB Framework – Entwicklerhandbuch

## Ziel des Frameworks

Das greenbucket® GBDB Framework ist ein leichtgewichtiges PHP-Framework für Projekte, die ohne klassischen SQL-Zwang auskommen sollen, aber trotzdem strukturierte Daten, Authentifizierung, Remote-Kommunikation und Update-/Lizenzlogik benötigen.

Es ist modular aufgebaut: Core-Klassen übernehmen Basisaufgaben, Plugins binden externe Produkte an und das SecondServerModul erlaubt eine Trennung zwischen Frontend-Server und Daten-/Backend-Server.

## Bootstrapping

Der normale Einstieg ist:

```php
include 'assets/php/inc/.config/_config.inc.php';
```

Diese Datei lädt:

1. `gbdb.php`
2. alle Core-Klassen
3. alle Plugin-Klassen
4. `Srv.php`
5. `functions.php`

Dadurch stehen Klassen wie `GBDB`, `GBDBv2`, `GreenQLv2`, `Auth`, `Http`, `SrvP` und die Produkt-Plugins direkt zur Verfügung.

## Datenbankarchitektur

### GBDB

GBDB speichert Daten in JSON-Dateien unter `assets/DB`. Eine Tabelle besteht nicht nur aus einer Datei, sondern aus mehreren technischen Bestandteilen:

- Tabellen-JSON mit Header-Zeile und Datenzeilen.
- Meta-Datei mit Zähler-/Strukturinformationen.
- Append-Datei für Schreiboperationen.
- Lock-Datei für sichere Schreibzugriffe.
- Schema-Datei zur Dokumentation/Strukturpflege.

### GBDBv2

GBDBv2 ergänzt Instanzen. Eine Instanz ist ein separater Datenraum. Dadurch kann dieselbe Anwendung mehrere Mandanten oder getrennte Projekte verwalten.

```php
GBDBv2::setInstance('kunde_a');
GBDBv2::createDatabase('main');
```

## GreenQL

GreenQL ist eine einfache Sprache, um Datenbankoperationen in Scripten zu schreiben. Das ist nützlich für Migrationen, Seeds, Admin-Oberflächen und Remote-Ausführung.

Beispiel:

```greenql
ROOT main;
GROW TABLE users (uid, username, email);
SEED users WITH uid="u001", username="markus", email="markus@example.test";
SELECT * FROM users;
```

## Authentifizierung

`Auth` erzeugt und verwaltet Benutzer, JWT-Cookies, Mail-Verifikation und 2FA-Codes. Die Konfiguration liegt in `Vars::AUTH()`.

Typische Verwendung:

```php
Auth::init();
$status = Auth::login($username, $password);
if (Auth::check()) {
    $user = Auth::me();
}
```

## SecondServerModul

Das SecondServerModul trennt Client und Backend:

- Server 1 nutzt `SrvP`.
- Server 2 stellt `backend.php` und `Srv` bereit.
- Jeder Request wird mit Static-Key und Einmal-Token abgesichert.
- Optional kann per Kontext eine GBDBv2-Instanz gewählt werden.

## UI und Developer Tools

Das Projekt enthält GreenQL-UIs, CSS/JS-Dateien und Dev-Helfer. Diese sind hilfreich für Entwicklung, sollten aber produktiv nur geschützt erreichbar sein.

## Fehlerbehebung

### Leere JSON-Antwort

- Prüfen, ob `backend.php` erreichbar ist.
- Prüfen, ob PHP-Fehler die JSON-Ausgabe zerstören.
- Webserver-Logs lesen.

### GBDB schreibt nicht

- Rechte für `assets/DB` prüfen.
- Lock-Dateien und Besitzer prüfen.
- Kein pauschales `777`, lieber Gruppe/ACL korrekt setzen.

### Remote-Auth schlägt fehl

- `srvp_static_key` auf beiden Seiten vergleichen.
- HTTP/HTTPS in `Vars::srvp_ssl()` prüfen.
- Token-Datei unter `assets/DB/framework_temp/_srvtkns.cry` prüfen.

## Erweiterung

Neue Klassen gehören in `core/` oder `plugins/`. Neue öffentliche Methoden sollten direkt dokumentiert werden. Neue GBDB-Tabellen sollten Schema- und Seed-Logik bekommen.
