# SecondServerModul / GBDB Framework

Dieses Repository enthält ein **PHP-basiertes Framework** für zwei Hauptaufgaben:

1. **Persistenz-Layer** mit einem dateibasierten Datenbanksystem (**GBDB**) oder alternativ SQL.
2. **Second-Server-API** für abgesicherte CRUD-Zugriffe und ein kleines Job-/Service-System.

Zusätzlich sind mehrere **Developer-Tools** (UI, Migration, Optimierung, Verschlüsselungs-Migration) enthalten.

---

## Inhaltsverzeichnis

- [Überblick](#überblick)
- [Architektur](#architektur)
- [Projektstruktur](#projektstruktur)
- [Konfiguration](#konfiguration)
- [GBDB im Detail](#gbdb-im-detail)
- [API (`api.php`)](#api-apiphp)
- [SrvP-Client (Aufruf von Server 1)](#srvp-client-aufruf-von-server-1)
- [Job-/Service-System (`Srv`)](#job-service-system-srv)
- [Developer-Tools](#developer-tools)
- [Sicherheit & Betriebshinweise](#sicherheit--betriebshinweise)
- [Beispiel-Requests](#beispiel-requests)
- [Typische Workflows](#typische-workflows)
- [Bekannte Stolpersteine](#bekannte-stolpersteine)

---

## Überblick

Das Framework wird über `assets/php/inc/.config/_config.inc.php` initialisiert. Dort werden folgende Kernkomponenten geladen:

- `assets/php/inc/gbdb_framework/gbdb.php` (Framework + Core)
- `assets/php/inc/Srv.php` (Job-System)
- `functions.php` (API-Utilityfunktionen)

Über `DB_ARCH` wird gesteuert, ob CRUD-Operationen gegen **GBDB** oder **SQL** laufen.

---

## Architektur

### 1) Einstiegspunkte

- **`api.php`**
  - JSON-API-Endpunkt (POST)
  - unterstützt Token-Handshake + CRUD + Job-Endpunkte
- **`greenql_ui.php`**
  - Dev-UI zur manuellen Verwaltung von GBDB-Datenbanken/Tabellen
- **`migration.php` / `cryption.php` / `optimize.php`**
  - Dev-Tools für Datenmigration, Verschlüsselungsumschaltung und Kompaktierung

### 2) Daten-Layer

- **GBDB** (`GBDB`-Klasse) als Dateispeicher
- optional **SQL** (`SQL`-Klasse, PDO)
- Bridge-Funktionen in `functions.php`: `DB_GET`, `DB_PUT`, `DB_EDIT`, `DB_DELETE`

### 3) Remote-Zugriff zwischen Servern

- **Server 2**: stellt `api.php` bereit
- **Server 1**: nutzt `SrvP`-Klasse (`core/srvp.php`) als Client
- Authentisierung:
  - statischer Schlüssel (`sauth`)
  - Einmal-Token (`token`) über `do=gtoken`

---

## Projektstruktur

```text
.
├── api.php                         # API-Endpunkt (CRUD + srv_*)
├── functions.php                   # Antwortformat, Auth, DB-Bridge, Token-Storage
├── assets/
│   ├── php/inc/
│   │   ├── .config/_config.inc.php # Bootstrap + DB_ARCH
│   │   ├── Srv.php                 # Job-System
│   │   └── gbdb_framework/
│   │       ├── ENV.php             # zentrale Konfiguration (Vars)
│   │       ├── gbdb.php            # Loader für core/ + plugins/
│   │       └── core/
│   │           ├── gbdb_sys.php    # GBDB-Kernlogik
│   │           ├── crypt.php       # Verschlüsselung
│   │           ├── sql.php         # SQL-Adapter
│   │           ├── http.php        # HTTP-Helfer + Mail-Helfer
│   │           └── srvp.php        # Client für Second-Server-API
│   ├── php/srv_modules/            # Service-Module (z. B. Mail)
│   ├── php/srv_logs/               # Job-Logs
│   ├── DB/                         # Datenablage (GBDB + temporäre Dateien)
│   ├── css/, js/                   # UI-Assets
│   └── tool_apis/                  # QR-/Barcode-Tools
├── greenql_ui.php                  # Admin-/Dev-Oberfläche für GBDB
├── migration.php                   # Dev-Migration alter/neuer GBDB-Strukturen
├── cryption.php                    # Dev-Tool Encrypt/Decrypt der GBDB-Daten
├── optimize.php                    # Dev-Tool für GBDB-Compaction
└── dev.php                         # lädt nur Konfiguration (Minimal-Entry)
```

---

## Konfiguration

Alle zentralen Einstellungen liegen in `assets/php/inc/gbdb_framework/ENV.php` in der Klasse `Vars`.

Wichtige Parameter:

- `Vars::__DEV__()`
  - erkennt Entwicklungsmodus über ENV (`GBDB_ENV=dev` / `GBDB_DEV=1`) und Marker-Dateien
- `Vars::json_path()`
  - Basisordner der Daten, standardmäßig `assets/DB/`
- `Vars::crypt_data()`
  - aktiviert verschlüsselte GBDB-Ablage (`.db` statt `.json`)
- `Vars::cryptKey()`
  - Schlüssel für Datenverschlüsselung (GBDB + Namenstoken)
- `Vars::srvp_static_key()`
  - Shared Secret für API-Erstauthentisierung
- SQL-Konfiguration:
  - `sql_server/sql_database/sql_user/sql_password`
  - dev-Varianten: `sql_dev_*`

In `assets/php/inc/.config/_config.inc.php` wird zusätzlich gesetzt:

```php
define("DB_ARCH", "GBDB"); // oder "SQL"
```

---

## GBDB im Detail

`GBDB` (in `core/gbdb_sys.php`) ist ein dateibasiertes DB-System mit:

- Tabellenkopfzeile (`id = -1`) als Schema-Header
- `id`-basierten Datensätzen
- Locking pro Tabelle (`.lock`)
- Meta-Datei pro Tabelle (`last_id`, `rows`, `append_ops`)
- Append-Log (Operationen `ins`, `upd`, `del`)
- optionaler Namensverschleierung für DB-/Tabellennamen bei aktivierter Verschlüsselung

### CRUD-Verhalten

- `insertData()`
  - schreibt Insert als Append-Operation
  - erzeugt neue ID
- `editData()`
  - schreibt Update(s) als Append-Operationen
- `deleteData()`
  - schreibt Delete(s) als Append-Operationen
- `getData()`
  - lädt Base + Append und materialisiert den aktuellen Zustand

### Compaction

Mit `compactTable()` wird:

1. der materialisierte Zustand als neue Base-Datei gespeichert,
2. die Append-Datei geleert,
3. Meta-Daten aktualisiert.

Dafür existiert auch das Dev-UI `optimize.php`.

### Verschlüsselung

- Umsetzung in `core/crypt.php` (AES-256-CBC + HMAC, neues Prefix `enc1.`)
- Legacy-Format wird beim Decoding weiterhin unterstützt
- Dateiendung wird über `Vars::data_extension()` gesteuert (`.db` oder `.json`)

---

## API (`api.php`)

### Allgemeines

- Nur `POST` erlaubt
- Request Body muss JSON sein
- Standard-Antwortformat:

```json
{
  "ok": true,
  "status": 200,
  "data": {"...": "..."}
}
```

### Authentisierung

Ablauf:

1. `do=gtoken` mit `sauth=sha256(static_key)` aufrufen
2. Token aus Antwort verwenden
3. Alle weiteren Requests mit `sauth` + `token`
4. Token wird serverseitig nach Nutzung gelöscht (One-Time-Token)

### Unterstützte `do`-Operationen

#### CRUD

- `get`
  - Pflicht: `db`, `table`
  - optional: `where`, `is`
- `put`
  - Pflicht: `db`, `table`, `data`
- `edit`
  - Pflicht: `db`, `table`, `where`, `is`, `data`
- `delete`
  - Pflicht: `db`, `table`, `where`, `is`

#### Service/Jobs

- `srv_enqueue` (`service`, `action`, optional `payload`)
- `srv_run_one` (`id`)
- `srv_status` (optional `id`)
- `srv_logs` (`job_id`)
- `srv_jobs` (alle Jobs)

---

## SrvP-Client (Aufruf von Server 1)

Die Klasse `SrvP` (`core/srvp.php`) kapselt den API-Zugriff:

- Token-Handling intern (`getToken()`)
- API-Basisadresse aus
  - `Vars::srvp_ssl()`
  - `Vars::srvp_ip()`
- Methoden:
  - `getData`, `addData`, `editData`, `deleteData`
  - `srv_enqueue`, `srv_run_one`, `srv_status`, `srv_logs`, `srv_jobs`

Damit kann Server 1 den Server-2-Endpunkt ohne manuelles Auth-Handling ansprechen.

---

## Job-/Service-System (`Srv`)

`assets/php/inc/Srv.php` implementiert eine einfache Queue über GBDB:

- Jobs landen in `main/srv_jobs`
- Statusfelder: z. B. `pending`, `done`, `failed`
- `runOne()` lädt Modul + Action dynamisch
  - Moduldatei: `assets/php/srv_modules/<Service>.php`
  - Klassenname: `Srv_<Service>`
- Logging:
  - pro Job Zeilen-JSON in `assets/php/srv_logs/<jobId>.log`

Aktuell existiert ein Platzhaltermodul `Srv_Mail`.

---

## Developer-Tools

> Alle Tools sind für Dev-Umgebungen gedacht und prüfen i. d. R. `Vars::__DEV__()`.

### `greenql_ui.php`

- Browseroberfläche für:
  - Datenbanken erstellen/löschen
  - Tabellen erstellen/löschen
  - Datensätze anlegen/bearbeiten/löschen
  - Suche innerhalb Tabellenansicht

### `migration.php`

- Migrationswerkzeug für GBDB-Strukturen/Dateiformate
- arbeitet mit Backups und rekursiver Dateibehandlung

### `cryption.php`

- Encrypt/Decrypt-Migrator für GBDB-Daten
- erzeugt Backups
- unterstützt Struktur mit Index-Dateien

### `optimize.php`

- führt Compaction für einzelne oder alle Tabellen einer DB aus
- zeigt Größen-/Statusinfos zu Base/Meta/Append

---

## Sicherheit & Betriebshinweise

1. **Static Key ändern**
   - `Vars::srvp_static_key()` darf nicht auf Defaultwert bleiben.
2. **`__DEV__()` sauber setzen**
   - Dev-Tools dürfen in Produktion nicht erreichbar sein.
3. **HTTPS zwischen Servern nutzen**
   - bei externen Aufrufen `srvp_ssl()` aktivieren.
4. **Dateirechte prüfen**
   - Schreibrechte für `assets/DB/` und `assets/php/srv_logs/` sicherstellen.
5. **Token-Datei schützen**
   - Tokens liegen verschlüsselt in `assets/DB/framework_temp/_srvtkns.cry`.
6. **Compaction regelmäßig ausführen**
   - bei starkem Schreibbetrieb wachsen Append-Dateien.

---

## Beispiel-Requests

### 1) Token holen

```bash
curl -X POST http://<host>/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "do": "gtoken",
    "sauth": "<sha256_static_key>"
  }'
```

### 2) Datensatz schreiben

```bash
curl -X POST http://<host>/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "do": "put",
    "sauth": "<sha256_static_key>",
    "token": "<token>",
    "db": "main",
    "table": "users",
    "data": {
      "name": "Max",
      "email": "max@example.com"
    }
  }'
```

### 3) Gefiltert lesen

```bash
curl -X POST http://<host>/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "do": "get",
    "sauth": "<sha256_static_key>",
    "token": "<token>",
    "db": "main",
    "table": "users",
    "where": "email",
    "is": "max@example.com"
  }'
```

### 4) Job enqueuen

```bash
curl -X POST http://<host>/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "do": "srv_enqueue",
    "sauth": "<sha256_static_key>",
    "token": "<token>",
    "service": "mail",
    "action": "send",
    "payload": {
      "to": "mail@example.com"
    }
  }'
```

---

## Typische Workflows

### Lokale Entwicklung starten

1. `Vars::__DEV__()` aktivieren (ENV `GBDB_ENV=dev` empfohlen).
2. `DB_ARCH` auf `GBDB` oder `SQL` setzen.
3. Falls GBDB neu aufgesetzt wird:
   - DB/Tables über `greenql_ui.php` erstellen.
4. API mit `gtoken` + CRUD testen.

### Wechsel Plain ↔ Encrypt

1. Backup erstellen.
2. `cryption.php` im Dev-Modus verwenden.
3. Nach Migration `Vars::crypt_data()` passend setzen.
4. Mit `optimize.php` optional kompaktieren.

---

## Bekannte Stolpersteine

- **One-Time-Tokens**: Ein Token ist nach einem Request ungültig.
- **SQL-Insert-Returnwert**: Der SQL-Adapter gibt bei `insert()` bool zurück; die API erwartet für `put` jedoch eher eine ID.
- **Direkter Dateizugriff im alten UI-Code**: Einzelne UI-Stellen löschen Tabellen über Dateipfade statt über zentrale GBDB-Methoden.
- **Dev-Tools sind mächtig**: Migration/Cryption können Datenstruktur tiefgreifend verändern.

---

Wenn du möchtest, kann ich im nächsten Schritt zusätzlich eine **kurze Betriebs-Doku (`RUNBOOK`)** oder eine **API-Referenz als kompakte Tabelle** erstellen.
