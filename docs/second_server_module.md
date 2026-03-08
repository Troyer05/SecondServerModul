# Second Server Module

## Überblick

Das SecondServer Module erweitert das Framework um eine Remote-Ebene. Damit kannst du von einem Client-System aus auf eine andere GBDB-Instanz zugreifen.

Die zentrale Client-Klasse dafür ist:

- `SrvP`

Die zentrale Server-API dafür ist:

- `api.php`

Zusätzlich gibt es das lokale Job-/Service-System über:

- `Srv`

---

## Wofür das Modul gedacht ist

Das Modul ist sinnvoll, wenn du:

- Daten auf einem zweiten Server auslagern willst
- zentrale Datenhaltung für mehrere Clients brauchst
- Jobs, Mail-Services oder andere Serveraktionen queue-basiert ausführen willst
- lokal und remote dieselbe API-Sprache nutzen möchtest

---

## Verbindungsdaten

Die Verbindung wird über `Vars` gesteuert. Relevant sind insbesondere:

- `Vars::srvp_ssl()`
- `Vars::srvp_ip()`
- `Vars::srvp_static_key()`

`SrvP` baut daraus intern den API-Endpunkt:

```php
http(s)://<srvp_ip>/api.php
```

---

## Authentifizierung

Das System arbeitet mit zwei Schritten:

1. statische Auth über den Hash von `Vars::srvp_static_key()`
2. Einmal-Token über `gtoken`

Ablauf:

- `SrvP` fordert per `gtoken` ein API-Token an
- dieses Token wird mit jeder Aktion mitgesendet
- die API validiert den Token
- nach erfolgreichem Request wird der Token wieder entfernt

Das ergibt ein sehr klares Single-Use-Token-Verhalten.

---

## Direktmethoden von `SrvP`

### Lesen

```php
SrvP::getData("main", "users");
SrvP::getData("main", "users", true, "id", 1);
```

### Schreiben

```php
SrvP::addData("main", "users", [
    "name" => "Markus",
    "email" => "markus@example.com"
]);
```

### Bearbeiten

```php
SrvP::editData("main", "users", "id", 1, [
    "role" => "admin"
]);
```

### Löschen

```php
SrvP::deleteData("main", "users", "id", 1);
```

---

## GreenQL per Remote-API

Neu im System ist, dass dieselbe GreenQL-Sprache jetzt auch remote genutzt werden kann.

### Direkt aufrufen

```php
SrvP::query("PICK * FROM users IN main;");
```

### Mit Kontext

```php
SrvP::query("ROOT main; BRANCH users; PICK * FROM users;", [
    "db" => "main",
    "table" => "users"
]);
```

### Schreibzugriffe

```php
SrvP::query("SEED users WITH name='Lea', email='lea@example.com', role='editor' IN main;");
SrvP::query("RESHAPE users WITH role='owner' WHERE id = 1 IN main;");
SrvP::query("ERASE FROM users WHERE id = 1 IN main;");
```

Die Rückgabe hat dieselbe Struktur wie bei `GBDB::query()`.

---

## Server-seitige API-Kommandos

`api.php` unterstützt aktuell unter anderem diese `do`-Werte:

- `gtoken`
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

### Beispiel `query`

Request-Body:

```json
{
  "sauth": "<sha256-static-key>",
  "token": "<single-use-token>",
  "do": "query",
  "query": "PICK * FROM users IN main;",
  "ctx": {
    "db": "main",
    "table": "users"
  }
}
```

---

## Job-System über `Srv`

`Srv` ist das serverseitige Queue-/Service-System.

### Job anlegen

```php
$id = Srv::enqueue("mail", "send", [
    "to" => "info@example.com",
    "subject" => "Test"
]);
```

### Job abrufen

```php
Srv::getJob($id);
Srv::getJobs();
```

### Job ausführen

```php
Srv::runOne($id);
```

---

## Modulstruktur

Service-Module liegen unter:

```txt
assets/php/srv_modules/
```

Ein Modul `Mail.php` entspricht intern der Klasse:

```php
class Srv_Mail {
}
```

Das Laden passiert über:

- Dateiname `Mail.php`
- Klassenname `Srv_Mail`

---

## Logging

Logs werden unter folgendem Pfad geschrieben:

```txt
assets/php/srv_logs/
```

Jeder Job erhält eine eigene Logdatei:

```txt
<jobId>.log
```

Die Einträge werden als JSON-Zeilen geschrieben.

Das ist praktisch für:

- Debugging
- Nachvollziehbarkeit
- Module-Fehleranalyse
- spätere UI-Auswertung

---

## Typische Architektur

### Lokaler App-Server

- UI
- Business-Logik
- Admin-Oberfläche

### Zweiter Server

- zentrale GBDB-Datenbasis
- Jobsystem
- Mail-/Tool-Module
- API-Endpunkt

### Kommunikation

- `SrvP` auf Client-Seite
- `api.php` auf Server-Seite
- `GBDB` und `GreenQL` auf Server-Seite

---

## Best Practices

### 1. GreenQL für Admin- und Diagnoseaktionen nutzen

```php
SrvP::query("SHOW TABLES IN main;");
SrvP::query("DESCRIBE users IN main;");
SrvP::query("PICK * FROM srv_jobs IN main LIMIT 50;");
```

### 2. Direkte Methoden für klare App-Operationen nutzen

```php
SrvP::getData("main", "users", true, "id", 4);
SrvP::editData("main", "users", "id", 4, ["status" => "active"]);
```

### 3. Queue für länger laufende Aktionen nutzen

- Mailversand
- externe API-Synchronisation
- Dateiverarbeitung
- modulare Serveraufgaben

### 4. Remote und lokal gleich denken

Wenn lokal etwas mit `GBDB::query()` funktioniert, kann dieselbe Syntax auch über `SrvP::query()` auf dem Server genutzt werden.

---

## Fehlersuche

### Ungültige JSON-Antwort

Wenn `SrvP` eine Exception mit „Invalid JSON response“ wirft, dann liefert der Server meist:

- einen PHP-Fehler
- HTML statt JSON
- oder ein Modul hat ungeplante Ausgabe erzeugt

### Token-Fehler

Prüfe:

- gleichen `srvp_static_key()` auf Client und Server
- ob Token-Datei beschreibbar ist
- ob der Request wirklich als `POST` kommt

### Query-Fehler

Prüfe:

- `do = query`
- gültige GreenQL-Syntax
- ob Base/Tabelle existieren
- ob `ctx` sauber gesetzt ist

---

## Beispielworkflow

```php
$result = SrvP::query("ROOT main; PICK id, name, role FROM users SORT id DESC LIMIT 20;");

if ($result["ok"]) {
    foreach ($result["rows"] as $row) {
        echo $row["name"] . "\n";
    }
}
```

Damit kannst du remote fast genauso arbeiten wie lokal.
