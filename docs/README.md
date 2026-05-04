# greenbucket® GBDB Framework / SecondServerModul – Dokumentation

Diese Dokumentation beschreibt den aktuellen Stand des Frameworks im Projekt **SecondServerModul**. Sie ist bewusst ausführlich geschrieben: nicht nur als Methodenliste, sondern als Arbeitsgrundlage für Entwickler, die das System erweitern, debuggen oder produktiv einsetzen möchten.

## Was dieses Framework ist

Das Framework ist ein PHP-8.x-orientiertes, statisch aufgebautes Projektframework mit folgenden Kernideen:

- **GBDB** als dateibasierte Datenbank für kleine bis mittlere Webanwendungen ohne klassischen SQL-Zwang.
- **GBDBv2** als instanzfähige Variante für Mandanten, getrennte Kundenbereiche oder isolierte Test-/Produktivräume.
- **GreenQL / GreenQLv2** als eigene Script- und Query-Sprache für Datenbankoperationen, Kontrollfluss, Variablen, Funktionen, Logging und Datei-Ausführung.
- **Srv/SrvP** als Second-Server-Brücke: ein Server kann sicher Requests an einen anderen Server senden und dort GBDB, GBDBv2, Auth, GreenQL oder Service-Jobs ausführen.
- **Public API** als JSON-Schnittstelle für externe Anwendungen.
- **Plugin-Schicht** für Produkte wie MuseumQR, ShareSuite, EventQR und mRoot.

## Empfohlene Lesereihenfolge

1. [`architecture.md`](architecture.md) – Gesamtaufbau, Loader, Ordner, Datenfluss.
2. [`installation_setup.md`](installation_setup.md) – Einbindung, Rechte, erster Start.
3. [`vars.md`](vars.md) – zentrale Konfiguration über `Vars`.
4. [`gbdb.md`](gbdb.md) – GBDB v1, Tabellen, CRUD, Schema, Indizes, Snapshots.
5. [`gbdbv2.md`](gbdbv2.md) – Mandanten-/Instanzlogik.
6. [`greenql.md`](greenql.md) – GreenQL-Sprache, Syntax, Beispiele.
7. [`greenqlv2.md`](greenqlv2.md) – GreenQL mit Instanzen, Meta, Health, Constraints und erweiterten Storage-Befehlen.
8. [`greenql_ui.md`](greenql_ui.md) – Web-UI, Userverwaltung, Rechte, Script-Ausführung.
9. [`srvp.md`](srvp.md), [`srv.md`](srv.md), [`second_server_module.md`](second_server_module.md) – Remote-Kommunikation und Service-Jobs.
10. [`public_api.md`](public_api.md) – externe JSON-API.

## Dokumentationsstil

Jede Klassendoku enthält:

- Zweck und Hintergrund.
- typische Einsatzfälle.
- wichtige Methoden.
- Copy-Paste-Beispiele.
- Hinweise zu Fehlerquellen, Sicherheit und Intention.

## Wichtige Grundregel

Konfigurationswerte in `ENV.php` sind Projektwerte. Platzhalter, Tokens und Keys aus Beispielinstallationen dürfen nie als produktive Secrets verstanden oder öffentlich dokumentiert werden.

## Neue technische Struktur

- [GBDB-System-Aufteilung](gbdb_system_split.md) – erklärt die neue Zerlegung der großen GBDB-/GreenQL-Klassen in `assets/php/inc/gbdb_framework/core/gbdb_system/`.


## Neue Engine-Erweiterungen

- [`gbdb_advanced.md`](gbdb_advanced.md) beschreibt Indexe, Partitionierung, WAL/Recovery, Volltextsuche, ACL, Monitoring, Streaming/Bulk, Query Planner, Cache, Migrationen, DSGVO/Audit, Shards, Pages, Cursor und Append Logs.
