# Architekturübersicht

## Zielbild

Das Framework ist darauf ausgelegt, kleine bis komplexere PHP-Projekte ohne schwere Framework-Abhängigkeiten aufzubauen. Der Code ist bewusst statisch, direkt und copy-paste-freundlich gehalten. Klassen werden zentral geladen, danach kann jede Seite sofort mit `GBDB`, `GBDBv2`, `Auth`, `SrvP`, `GreenQL`, `Http`, `Validate` usw. arbeiten.

## Zentrale Einbindung

Der wichtigste Einstiegspunkt ist:

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Diese Datei lädt unter anderem:

```php
require_once __DIR__ . "/../gbdb_framework/gbdb.php";
require_once __DIR__ . "/../Srv.php";
require_once __DIR__ . "/../../../functions.php";
```

`gbdb.php` lädt zuerst `ENV.php` und danach automatisch alle Dateien aus `core/` und `plugins/`. Dadurch sind die Framework-Klassen nach einem Include verfügbar.

## Hauptordner

| Pfad | Zweck |
|---|---|
| `assets/php/inc/.config/_config.inc.php` | Projekt-Config-Einstieg. Wird von Seiten eingebunden. |
| `assets/php/inc/gbdb_framework/ENV.php` | zentrale Klasse `Vars` mit Konfiguration. |
| `assets/php/inc/gbdb_framework/gbdb.php` | Loader für Core- und Plugin-Klassen. |
| `assets/php/inc/gbdb_framework/core/` | technische Kernklassen: GBDB, GreenQL, Auth, HTTP, Cookie, Session, Cache usw. |
| `assets/php/inc/gbdb_framework/plugins/` | Produkt-/Integrationsklassen wie mRoot, MuseumQR, ShareSuite. |
| `assets/php/inc/gbdb_framework/dev/` | Entwicklungswerkzeuge, UI-Helper, Migration, Public-API-Handler. |
| `assets/DB/` | Datenbereich für GBDB, Tokens, Framework-Temp-Dateien. |
| `docs/` | diese Dokumentation. |
| `scripts/greenql/` | Beispiel- und Wartungsscripte für GreenQL. |

## Datenbank-Architektur

Das Framework kann je nach Bereich mit mehreren Datenhaltungswegen arbeiten:

1. **GBDB v1**: klassische lokale dateibasierte Datenbank ohne Instanztrennung.
2. **GBDBv2**: dateibasierte Datenbank mit Instanzen/Mandanten.
3. **SQL**: über `SQL` und `DatabaseBridge` vorbereitet.
4. **SrvP/Srv**: Remote-Zugriff auf einen anderen Server, der dort wiederum GBDB/GBDBv2 nutzt.

## GreenQL als höhere Ebene

GreenQL ist nicht nur ein Query-Wrapper. Es ist eine kleine Script-Sprache mit Variablen, Konstanten, Kontrollfluss, Funktionen, Klassen-/Objektlogik, Logging und Datei-Ausführung. Dadurch können Wartungs- oder Setup-Scripte geschrieben werden, ohne PHP direkt anfassen zu müssen.

## Remote-Kommunikation

`SrvP` läuft auf Server 1 und spricht mit `backend.php` auf Server 2. Die Kommunikation nutzt JSON, einen statischen Auth-Key und kurzlebige Einmal-Tokens. Der Backend-Server führt dann Aktionen aus: Datenbankoperationen, GreenQL-Scripte, Auth-Funktionen oder Service-Jobs.

## Sicherheitsidee

- Konfiguration liegt zentral in `Vars`.
- Remote-Kommunikation benötigt statischen Auth-Key plus Einmal-Token.
- Public API kann über Auth-Keys und Feature-Gates eingeschränkt werden.
- GreenQL UI besitzt eine eigene Userverwaltung mit Rollen und Zugriffseinschränkungen.
- GBDB kann Daten verschlüsselt/obfuskiert speichern, abhängig von `Vars::crypt_data()`.

## Intention des Frameworks

Das Framework soll schnell, verständlich und wartbar bleiben. Es ist kein Laravel-Ersatz, sondern ein eigenes Werkzeug für Projekte, die eine kompakte, kontrollierbare Architektur brauchen: MuseumQR, EventQR, ShareSuite, RFID-Backends, interne Tools oder kleine SaaS-Instanzen.
