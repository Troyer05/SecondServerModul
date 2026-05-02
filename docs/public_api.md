# Public API Core Dokumentation

**Klasse:** `PAPI`  
**Autor:** Markus Müller  
**Typ:** JSON-basierte Public API / Framework-API-Router  
**Datenbanken:** `GBDB` v1, `GBDBv2`, `GreenQL`, `GreenQLv2`  
**Routing-Prinzip:** Request-Body-Parameter `do` entscheidet die Aktion  

---

## 1. Überblick

Die `PAPI` Klasse ist der zentrale Public-API-Core des Frameworks. Sie nimmt JSON-Requests entgegen, prüft optional einen Auth-Key und führt anschließend anhand des Parameters `do` eine definierte API-Aktion aus.

Die API ist bewusst als **JSON-Router** aufgebaut. Es gibt also nicht viele einzelne Endpunkte wie:

```txt
/api/users
/api/databases
/api/tables
```

Sondern einen zentralen Endpunkt, zum Beispiel:

```txt
/public_api.php
```

Die gewünschte Aktion wird über den JSON-Body bestimmt:

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "ping"
}
```

Dadurch bleibt die API einfach, kompakt und gut mit Geräten, SecondServer-Kommunikation, kleinen Tools oder internen Services nutzbar.

---

## 2. Minimaler API-Endpunkt

Eine typische `public_api.php` sieht so aus:

```php
<?php

require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";

PAPI::init();

?>
```

Wichtig ist, dass die Klasse `PAPI` durch deinen Framework-Bootstrap oder durch ein direktes `require_once` geladen wurde.

Falls dein Bootstrap die Klasse nicht automatisch lädt:

```php
<?php

require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
require_once __DIR__ . "/assets/php/inc/gbdb_framework/core/PAPI.php";

PAPI::init();

?>
```

---

## 3. Request-Grundaufbau

Jeder Request wird als JSON im Body gesendet.

### Ohne Auth

Wenn `Vars::pApi_need_auth()` `false` zurückgibt:

```json
{
  "do": "ping"
}
```

### Mit Auth

Wenn `Vars::pApi_need_auth()` `true` zurückgibt:

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "ping"
}
```

---

## 4. Response-Grundaufbau

Alle Antworten haben dieselbe Grundstruktur:

```json
{
  "ok": true,
  "status": 200,
  "data": {}
}
```

### Felder

| Feld | Typ | Bedeutung |
|---|---:|---|
| `ok` | `bool` | Gibt an, ob die API-Antwort erfolgreich war. |
| `status` | `int` | HTTP-Statuscode. |
| `data` | `mixed` | Nutzdaten oder Fehlerobjekt. |

---

## 5. Erfolgsantworten

Beispiel für `ping`:

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "pong": true,
    "time": 1767350000
  }
}
```

---

## 6. Fehlerantworten

Fehler werden ebenfalls als JSON ausgegeben:

```json
{
  "ok": false,
  "status": 403,
  "data": {
    "msg": "Parameter \"do\" not provided."
  }
}
```

### Häufige Fehler

| Status | Bedeutung |
|---:|---|
| `400` | Ungültiger JSON-Body oder allgemeiner Request-Fehler. |
| `403` | Fehlender Parameter, leere Parameter, Auth-Fehler oder Zugriff verweigert. |
| `404` | Unbekannte API-Aktion. |
| `500` | Interner Fehler, fehlende Klasse oder fehlender Handler. |

---

## 7. Authentifizierung

Die Authentifizierung wird über `Vars::pApi_need_auth()` gesteuert.

Wenn diese Methode `true` zurückgibt, muss jeder Request den Parameter `auth_key` enthalten.

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "ping"
}
```

Die erlaubten Keys kommen aus:

```php
Vars::pApi_auth_keys()
```

Beispiel:

```php
public static function pApi_need_auth(): bool {
    return true;
}

public static function pApi_auth_keys(): array {
    return [
        "key_1",
        "key_2"
    ];
}
```

Die Klasse vergleicht Keys mit `hash_equals()`, wodurch der Vergleich sicherer ist als ein normales `==`.

---

## 8. Zugriffsrechte für GBDB und GreenQL

Die API nutzt optionale Schutzmethoden in `Vars`.

Empfohlene Methoden:

```php
public static function pApi_access_gbdb(): bool {
    return true;
}

public static function pApi_write_gbdb(): bool {
    return false;
}

public static function pApi_greenql(): bool {
    return false;
}
```

### Bedeutung

| Methode | Bedeutung |
|---|---|
| `pApi_access_gbdb()` | Erlaubt oder blockiert allgemeinen GBDB-Zugriff. |
| `pApi_write_gbdb()` | Erlaubt oder blockiert schreibende DB-Aktionen. |
| `pApi_greenql()` | Erlaubt oder blockiert GreenQL-Ausführung. |

### Wichtig

Wenn `pApi_write_gbdb()` oder `pApi_greenql()` nicht existieren, blockiert die Klasse diese Aktionen nicht automatisch. Das ist bewusst so, damit ältere Framework-Versionen nicht direkt kaputtgehen.

Für produktive öffentliche APIs sollten diese Methoden aber unbedingt existieren.

---

## 9. Routing-Prinzip

Die Klasse nutzt kein `switch`, sondern eine Action-Map:

```php
private const ACTION_FUNCTIONS = [
    "ping" => "apiPing",
    "gbdb_data" => "gbdb_data",
    "gbdbv2_data" => "gbdbv2_data"
];
```

Wenn ein Request kommt:

```json
{
  "do": "gbdb_data"
}
```

Dann ruft die API intern auf:

```php
self::gbdb_data();
```

Dadurch ist die API leicht erweiterbar. Neue Aktionen werden einfach in `ACTION_FUNCTIONS` eingetragen und als private Methode ergänzt.

---

## 10. Basis-Actions

## 10.1 `ping`

Prüft, ob die API erreichbar ist.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "ping"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "pong": true,
    "time": 1767350000
  }
}
```

---

## 10.2 `version`

Gibt Framework- und App-Version zurück, wenn die Methoden in `Vars` existieren.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "version"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "framework_version": "1.5",
    "app_version": "1.0"
  }
}
```

Falls die Methoden nicht existieren, können die Werte `null` sein.

---

# 11. GBDB v1 Actions

Die folgenden Actions arbeiten mit der Klasse:

```php
GBDB
```

GBDB v1 arbeitet ohne Instanzen. Datenbanken liegen direkt unter `Vars::DB_PATH()`.

---

## 11.1 `gbdb_databases`

Gibt alle GBDB-v1-Datenbanken zurück.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_databases"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": [
    "main",
    "userdb"
  ]
}
```

### Alias

```txt
get_gbdb_databases
```

---

## 11.2 `gbdb_create_database`

Erstellt eine neue GBDB-v1-Datenbank.

Benötigt Schreibzugriff über `Vars::pApi_write_gbdb()`.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Name der Datenbank. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_create_database",
  "database": "main"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "created": true
  }
}
```

---

## 11.3 `gbdb_delete_database`

Löscht eine leere GBDB-v1-Datenbank.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Name der Datenbank. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_delete_database",
  "database": "main"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "deleted": true
  }
}
```

---

## 11.4 `gbdb_delete_all`

Löscht eine komplette GBDB-v1-Datenbank inklusive Tabellen.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Name der Datenbank. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_delete_all",
  "database": "main"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "deleted": true
  }
}
```

---

## 11.5 `gbdb_tables`

Listet alle Tabellen einer GBDB-v1-Datenbank.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Name der Datenbank. |
| `descending` | `bool` | Nein | Sortierung absteigend. Standard: `false`. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_tables",
  "database": "main"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": [
    "settings",
    "users"
  ]
}
```

### Alias

```txt
get_gbdb_tables
```

---

## 11.6 `gbdb_create_table`

Erstellt eine neue GBDB-v1-Tabelle.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Datenbankname. |
| `table` | `string` | Ja | Tabellenname. |
| `cols` | `array` | Ja | Spaltenliste ohne `id`. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_create_table",
  "database": "main",
  "table": "users",
  "cols": ["uid", "username", "email", "role"]
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "created": true
  }
}
```

---

## 11.7 `gbdb_delete_table`

Löscht eine GBDB-v1-Tabelle.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Datenbankname. |
| `table` | `string` | Ja | Tabellenname. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_delete_table",
  "database": "main",
  "table": "users"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "deleted": true
  }
}
```

---

## 11.8 `gbdb_add_column`

Fügt einer GBDB-v1-Tabelle eine Spalte hinzu.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Datenbankname. |
| `table` | `string` | Ja | Tabellenname. |
| `column` | `string` | Ja | Neue Spalte. |
| `default` | `mixed` | Nein | Standardwert für bestehende Zeilen. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_add_column",
  "database": "main",
  "table": "users",
  "column": "active",
  "default": true
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "added": true
  }
}
```

---

## 11.9 `gbdb_schema` / `gbdb_keys`

Gibt die Keys einer GBDB-v1-Tabelle zurück.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Datenbankname. |
| `table` | `string` | Ja | Tabellenname. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_schema",
  "database": "main",
  "table": "users"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": ["id", "uid", "username", "email", "role"]
}
```

---

## 11.10 `gbdb_data`

Gibt alle Daten einer GBDB-v1-Tabelle zurück.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Datenbankname. |
| `table` | `string` | Ja | Tabellenname. |
| `filter` | `bool` | Nein | Einzelnen Datensatz filtern. |
| `where` | `string` | Nur bei Filter | Spalte zum Filtern. |
| `is` | `mixed` | Nur bei Filter | Vergleichswert. |
| `id` | `mixed` | Alternativ zu `is` | ID-Wert bei Standard-Filter `where = id`. |

### Alle Daten holen

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_data",
  "database": "main",
  "table": "users"
}
```

### Einzelnen Datensatz über Filter holen

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_data",
  "database": "main",
  "table": "users",
  "filter": true,
  "where": "uid",
  "is": "u_123"
}
```

### Alias

```txt
get_gbdb_data
```

---

## 11.11 `gbdb_row`

Gibt eine einzelne GBDB-v1-Zeile zurück.

### Request mit `id`

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_row",
  "database": "main",
  "table": "users",
  "id": 1
}
```

### Request mit `where` / `is`

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_row",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123"
}
```

---

## 11.12 `gbdb_exists`

Prüft, ob ein Element existiert.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_exists",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "exists": true
  }
}
```

---

## 11.13 `gbdb_insert`

Fügt eine neue Zeile ein.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `database` | `string` | Ja | Datenbankname. |
| `table` | `string` | Ja | Tabellenname. |
| `data` | `object` | Ja | Daten der neuen Zeile. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_insert",
  "database": "main",
  "table": "users",
  "data": {
    "uid": "u_123",
    "username": "markus",
    "email": "markus@example.com",
    "role": "admin"
  }
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "inserted": true,
    "id": 1
  }
}
```

---

## 11.14 `gbdb_update`

Bearbeitet eine oder mehrere passende Zeilen.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_update",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123",
  "data": {
    "role": "dev"
  }
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "updated": true
  }
}
```

---

## 11.15 `gbdb_delete`

Löscht eine oder mehrere passende Zeilen.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_delete",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "deleted": true
  }
}
```

---

## 11.16 `gbdb_next_id`

Gibt die nächste ID einer Tabelle zurück.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_next_id",
  "database": "main",
  "table": "users"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "next_id": 2
  }
}
```

---

## 11.17 `gbdb_compact`

Komprimiert eine Tabelle und wendet Append-Operationen auf die Hauptdatei an.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_compact",
  "database": "main",
  "table": "users"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "compacted": true
  }
}
```

---

## 11.18 `gbdb_query` / `greenql`

Führt eine GreenQL-v1-Abfrage aus.

Benötigt GreenQL-Zugriff über `Vars::pApi_greenql()`.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `query` | `string` | Ja | GreenQL-Script. |
| `ctx` | `object` | Nein | Kontextdaten. |
| `params` | `object` | Nein | Parameter. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_query",
  "query": "ROOT main; FETCH users;"
}
```

### Alias

```txt
greenql
```

---

## 11.19 `gbdb_run_script`

Führt ein GreenQL-v1-Script aus einer Datei aus.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdb_run_script",
  "path": "/var/www/html/project/scripts/demo.gql",
  "params": {},
  "ctx": {}
}
```

### Sicherheits-Hinweis

Diese Action ist mächtig, weil sie Script-Dateien vom Server ausführt. In produktiven APIs sollte sie nur für interne Systeme oder sehr streng abgesicherte Umgebungen aktiviert werden.

---

# 12. GBDBv2 Actions

Die folgenden Actions arbeiten mit:

```php
GBDBv2
```

GBDBv2 unterstützt Instanzen. Eine Instanz kann optional über den Request-Parameter `instance` gesetzt werden.

Beispiel:

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_databases",
  "instance": "default"
}
```

Wenn keine Instanz angegeben wird, nutzt GBDBv2 die aktuell gesetzte Instanz. Standardmäßig ist das meistens:

```txt
default
```

---

## 12.1 `gbdbv2_instance`

Gibt die aktive GBDBv2-Instanz zurück.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_instance"
}
```

### Request mit Instanzwechsel

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_instance",
  "instance": "kunde_a"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "instance": "kunde_a"
  }
}
```

---

## 12.2 `gbdbv2_instances`

Listet alle GBDBv2-Instanzen.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_instances"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": [
    "default",
    "kunde_a",
    "kunde_b"
  ]
}
```

---

## 12.3 `gbdbv2_create_instance`

Erstellt eine neue GBDBv2-Instanz.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `name` | `string` | Ja | Name der Instanz. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_create_instance",
  "name": "kunde_a"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "created": true
  }
}
```

---

## 12.4 `gbdbv2_delete_instance`

Löscht eine GBDBv2-Instanz.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `name` | `string` | Ja | Name der Instanz. |
| `force` | `bool` | Nein | Wenn `true`, werden enthaltene Datenbanken vorher gelöscht. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_delete_instance",
  "name": "kunde_a",
  "force": true
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "deleted": true
  }
}
```

---

## 12.5 `gbdbv2_databases`

Listet alle Datenbanken der aktiven oder angegebenen Instanz.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_databases",
  "instance": "default"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": [
    "main",
    "userdb"
  ]
}
```

---

## 12.6 `gbdbv2_create_database`

Erstellt eine Datenbank in der aktiven oder angegebenen Instanz.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_create_database",
  "instance": "default",
  "database": "main"
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "created": true
  }
}
```

---

## 12.7 `gbdbv2_delete_database`

Löscht eine leere Datenbank in GBDBv2.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_delete_database",
  "instance": "default",
  "database": "main"
}
```

---

## 12.8 `gbdbv2_delete_all`

Löscht eine komplette GBDBv2-Datenbank inklusive Tabellen.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_delete_all",
  "instance": "default",
  "database": "main"
}
```

---

## 12.9 `gbdbv2_tables`

Listet Tabellen einer GBDBv2-Datenbank.

### Parameter

| Parameter | Typ | Pflicht | Bedeutung |
|---|---:|---:|---|
| `instance` | `string` | Nein | Instanzname. |
| `database` | `string` | Ja | Datenbankname. |
| `descending` | `bool` | Nein | Absteigend sortieren. |

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_tables",
  "instance": "default",
  "database": "main"
}
```

---

## 12.10 `gbdbv2_create_table`

Erstellt eine GBDBv2-Tabelle.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_create_table",
  "instance": "default",
  "database": "main",
  "table": "users",
  "cols": ["uid", "username", "email", "role"]
}
```

---

## 12.11 `gbdbv2_delete_table`

Löscht eine GBDBv2-Tabelle.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_delete_table",
  "instance": "default",
  "database": "main",
  "table": "users"
}
```

---

## 12.12 `gbdbv2_add_column`

Fügt einer GBDBv2-Tabelle eine Spalte hinzu.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_add_column",
  "instance": "default",
  "database": "main",
  "table": "users",
  "column": "active",
  "default": true
}
```

---

## 12.13 `gbdbv2_schema` / `gbdbv2_keys`

Gibt die Keys einer GBDBv2-Tabelle zurück.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_schema",
  "instance": "default",
  "database": "main",
  "table": "users"
}
```

---

## 12.14 `gbdbv2_data`

Gibt Daten einer GBDBv2-Tabelle zurück.

### Alle Daten

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_data",
  "instance": "default",
  "database": "main",
  "table": "users"
}
```

### Gefilterter Datensatz

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_data",
  "instance": "default",
  "database": "main",
  "table": "users",
  "filter": true,
  "where": "uid",
  "is": "u_123"
}
```

---

## 12.15 `gbdbv2_row`

Gibt eine einzelne GBDBv2-Zeile zurück.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_row",
  "instance": "default",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123"
}
```

---

## 12.16 `gbdbv2_exists`

Prüft, ob ein Element existiert.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_exists",
  "instance": "default",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123"
}
```

---

## 12.17 `gbdbv2_insert`

Fügt Daten in eine GBDBv2-Tabelle ein.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_insert",
  "instance": "default",
  "database": "main",
  "table": "users",
  "data": {
    "uid": "u_123",
    "username": "markus",
    "email": "markus@example.com",
    "role": "admin"
  }
}
```

### Response

```json
{
  "ok": true,
  "status": 200,
  "data": {
    "inserted": true,
    "id": 1
  }
}
```

---

## 12.18 `gbdbv2_update`

Bearbeitet Daten in einer GBDBv2-Tabelle.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_update",
  "instance": "default",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123",
  "data": {
    "role": "dev"
  }
}
```

---

## 12.19 `gbdbv2_delete`

Löscht Daten aus einer GBDBv2-Tabelle.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_delete",
  "instance": "default",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_123"
}
```

---

## 12.20 `gbdbv2_next_id`

Gibt die nächste ID einer GBDBv2-Tabelle zurück.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_next_id",
  "instance": "default",
  "database": "main",
  "table": "users"
}
```

---

## 12.21 `gbdbv2_compact`

Komprimiert eine GBDBv2-Tabelle.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_compact",
  "instance": "default",
  "database": "main",
  "table": "users"
}
```

---

## 12.22 `gbdbv2_query` / `greenqlv2`

Führt eine GreenQLv2-Abfrage aus.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_query",
  "instance": "default",
  "query": "ROOT main; FETCH users;",
  "ctx": {},
  "params": {}
}
```

### Alias

```txt
greenqlv2
```

---

## 12.23 `gbdbv2_run_script`

Führt ein GreenQLv2-Script aus einer Datei aus.

### Request

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_run_script",
  "instance": "default",
  "path": "/var/www/html/project/scripts/demo.gql",
  "params": {},
  "ctx": {}
}
```

---

# 13. Übersicht aller Actions

## Basis

| Action | Beschreibung |
|---|---|
| `ping` | Prüft API-Erreichbarkeit. |
| `version` | Gibt Framework- und App-Version zurück. |

## GBDB v1

| Action | Beschreibung |
|---|---|
| `gbdb_databases` | Listet Datenbanken. |
| `gbdb_create_database` | Erstellt Datenbank. |
| `gbdb_delete_database` | Löscht leere Datenbank. |
| `gbdb_delete_all` | Löscht komplette Datenbank. |
| `gbdb_tables` | Listet Tabellen. |
| `gbdb_create_table` | Erstellt Tabelle. |
| `gbdb_delete_table` | Löscht Tabelle. |
| `gbdb_add_column` | Fügt Spalte hinzu. |
| `gbdb_schema` | Gibt Tabellen-Keys zurück. |
| `gbdb_keys` | Alias für `gbdb_schema`. |
| `gbdb_data` | Gibt Tabellendaten zurück. |
| `gbdb_row` | Gibt einzelne Zeile zurück. |
| `gbdb_exists` | Prüft Existenz. |
| `gbdb_insert` | Fügt Daten ein. |
| `gbdb_update` | Bearbeitet Daten. |
| `gbdb_delete` | Löscht Daten. |
| `gbdb_next_id` | Gibt nächste ID zurück. |
| `gbdb_compact` | Komprimiert Tabelle. |
| `gbdb_query` | Führt GreenQL aus. |
| `greenql` | Alias für `gbdb_query`. |
| `gbdb_run_script` | Führt GreenQL-Scriptdatei aus. |

## GBDB v2

| Action | Beschreibung |
|---|---|
| `gbdbv2_instance` | Gibt aktive Instanz zurück. |
| `gbdbv2_instances` | Listet Instanzen. |
| `gbdbv2_create_instance` | Erstellt Instanz. |
| `gbdbv2_delete_instance` | Löscht Instanz. |
| `gbdbv2_databases` | Listet Datenbanken einer Instanz. |
| `gbdbv2_create_database` | Erstellt Datenbank. |
| `gbdbv2_delete_database` | Löscht leere Datenbank. |
| `gbdbv2_delete_all` | Löscht komplette Datenbank. |
| `gbdbv2_tables` | Listet Tabellen. |
| `gbdbv2_create_table` | Erstellt Tabelle. |
| `gbdbv2_delete_table` | Löscht Tabelle. |
| `gbdbv2_add_column` | Fügt Spalte hinzu. |
| `gbdbv2_schema` | Gibt Tabellen-Keys zurück. |
| `gbdbv2_keys` | Alias für `gbdbv2_schema`. |
| `gbdbv2_data` | Gibt Tabellendaten zurück. |
| `gbdbv2_row` | Gibt einzelne Zeile zurück. |
| `gbdbv2_exists` | Prüft Existenz. |
| `gbdbv2_insert` | Fügt Daten ein. |
| `gbdbv2_update` | Bearbeitet Daten. |
| `gbdbv2_delete` | Löscht Daten. |
| `gbdbv2_next_id` | Gibt nächste ID zurück. |
| `gbdbv2_compact` | Komprimiert Tabelle. |
| `gbdbv2_query` | Führt GreenQLv2 aus. |
| `greenqlv2` | Alias für `gbdbv2_query`. |
| `gbdbv2_run_script` | Führt GreenQLv2-Scriptdatei aus. |

## Alte Aliases

| Alias | Ziel |
|---|---|
| `get_gbdb_databases` | `gbdb_databases` |
| `get_gbdb_tables` | `gbdb_tables` |
| `get_gbdb_data` | `gbdb_data` |

---

# 14. Beispiel mit PHP `Http::post`

Wenn deine `Http` Klasse JSON-POSTs unterstützt:

```php
$resp = Http::post("https://example.com/public_api.php", [
    "auth_key" => "DEIN_API_KEY",
    "do" => "gbdbv2_data",
    "instance" => "default",
    "database" => "main",
    "table" => "users"
]);
```

---

# 15. Beispiel mit cURL

```bash
curl -X POST "https://example.com/public_api.php" \
    -H "Content-Type: application/json" \
    -d '{
        "auth_key": "DEIN_API_KEY",
        "do": "ping"
    }'
```

---

# 16. Beispiel: User anlegen mit GBDBv2

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_insert",
  "instance": "default",
  "database": "main",
  "table": "users",
  "data": {
    "uid": "u_001",
    "username": "admin",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

---

# 17. Beispiel: User aktualisieren mit GBDBv2

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_update",
  "instance": "default",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_001",
  "data": {
    "role": "dev"
  }
}
```

---

# 18. Beispiel: User löschen mit GBDBv2

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "gbdbv2_delete",
  "instance": "default",
  "database": "main",
  "table": "users",
  "where": "uid",
  "is": "u_001"
}
```

---

# 19. Sicherheitsempfehlungen

Diese API kann sehr mächtig sein. Besonders gefährlich sind Aktionen wie:

```txt
gbdb_delete_all
gbdb_delete_table
gbdb_delete
gbdb_query
gbdb_run_script
gbdbv2_delete_instance
gbdbv2_delete_all
gbdbv2_delete_table
gbdbv2_delete
gbdbv2_query
gbdbv2_run_script
```

Für produktive Systeme wird empfohlen:

1. `pApi_need_auth()` immer auf `true` setzen.
2. Lange, zufällige Auth-Keys verwenden.
3. `pApi_write_gbdb()` standardmäßig auf `false` setzen.
4. `pApi_greenql()` standardmäßig auf `false` setzen.
5. Die Public API nur über HTTPS bereitstellen.
6. Schreibende Actions nur für interne Services oder SecondServer-Kommunikation aktivieren.
7. Script-Ausführung über `run_script` nur intern verwenden.
8. Bei echter öffentlicher Nutzung zusätzlich erlaubte Datenbanken und gesperrte Tabellen prüfen.

---

# 20. Empfohlene `Vars`-Konfiguration

```php
public static function pApi_need_auth(): bool {
    return true;
}

public static function pApi_auth_keys(): array {
    return [
        "CHANGE_ME_TO_A_LONG_RANDOM_SECRET"
    ];
}

public static function pApi_access_gbdb(): bool {
    return true;
}

public static function pApi_write_gbdb(): bool {
    return false;
}

public static function pApi_greenql(): bool {
    return false;
}
```

Für interne SecondServer-Kommunikation kann man Schreibzugriff gezielt aktivieren:

```php
public static function pApi_write_gbdb(): bool {
    return true;
}

public static function pApi_greenql(): bool {
    return true;
}
```

---

# 21. Neue Action hinzufügen

Eine neue Action wird in zwei Schritten ergänzt.

## Schritt 1: In `ACTION_FUNCTIONS` eintragen

```php
private const ACTION_FUNCTIONS = [
    "ping" => "apiPing",
    "status" => "apiStatus"
];
```

## Schritt 2: Methode ergänzen

```php
private static function apiStatus(): void {
    self::success([
        "status" => "online",
        "time" => time()
    ]);
}
```

Danach kann sie so aufgerufen werden:

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "status"
}
```

---

# 22. Typische Fehler und Lösungen

## Fehler: `Class "PAPI" not found`

Die Datei mit der Klasse wurde nicht eingebunden.

Lösung:

```php
require_once __DIR__ . "/assets/php/inc/gbdb_framework/core/PAPI.php";
```

Oder sicherstellen, dass der Framework-Bootstrap die Klasse lädt.

---

## Fehler: `Parameter "do" not provided.`

Der JSON-Body enthält keinen `do` Parameter.

Falsch:

```json
{
  "auth_key": "DEIN_API_KEY"
}
```

Richtig:

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "ping"
}
```

---

## Fehler: `Invalid JSON body.`

Der Request-Body ist kein gültiges JSON.

Prüfen:

- Wird `Content-Type: application/json` gesetzt?
- Ist der JSON-Body korrekt escaped?
- Gibt es versehentliche Kommata am Ende?

---

## Fehler: `Authentication failed. Wrong auth_key.`

Der übergebene `auth_key` ist nicht in `Vars::pApi_auth_keys()` enthalten.

---

## Fehler: `GBDB access on public API is denied.`

`Vars::pApi_access_gbdb()` gibt `false` zurück.

---

## Fehler: `GBDB write access on public API is denied.`

`Vars::pApi_write_gbdb()` gibt `false` zurück.

---

## Fehler: `GreenQL access on public API is denied.`

`Vars::pApi_greenql()` gibt `false` zurück.

---

# 23. Empfohlene Nutzung

Für einfache Geräte, SecondServer-Kommunikation oder Admin-Tools eignet sich die API sehr gut.

Empfohlene sichere Grundkonfiguration:

```txt
Auth:        an
GBDB read:   an
GBDB write:  aus
GreenQL:     aus
```

Für interne Server-zu-Server-Kommunikation:

```txt
Auth:        an
GBDB read:   an
GBDB write:  an
GreenQL:     optional an
```

Für eine wirklich öffentliche API:

```txt
Auth:        an
GBDB read:   stark eingeschränkt
GBDB write:  aus
GreenQL:     aus
```

---

# 24. Kurzfassung

Die `PAPI` Klasse ist ein zentraler JSON-Endpunkt für dein Framework.

Ein Request besteht mindestens aus:

```json
{
  "do": "ping"
}
```

Mit Auth:

```json
{
  "auth_key": "DEIN_API_KEY",
  "do": "ping"
}
```

Die API kann:

- Verfügbarkeit prüfen
- Versionen ausgeben
- GBDB v1 lesen und schreiben
- GBDBv2 mit Instanzen lesen und schreiben
- Tabellen und Spalten verwalten
- Datensätze einfügen, bearbeiten und löschen
- GreenQL und GreenQLv2 ausführen
- Script-Dateien ausführen

Die API ist stark, aber deshalb auch sicherheitskritisch. Besonders Schreibzugriff und GreenQL sollten nur gezielt aktiviert werden.
