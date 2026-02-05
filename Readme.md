
# GBDB Framework  
**GreenBucket Database Framework • Lightweight JSON Database Engine for PHP**

GBDB ist ein schnelles, modulares und extrem leichtgewichtiges Datenbank-Framework, das komplett ohne SQL auskommt.  
Es speichert Daten in strukturierten JSON-Dateien und bietet eine API, die einer echten Datenbank sehr ähnlich ist.

Es eignet sich hervorragend für:

- kleine bis mittlere Webprojekte  
- Microservices und Edge-Server  
- Tools, Dashboards und Admin-Panels  
- Standalone-Anwendungen ohne SQL-Server  
- Entwicklungs- und Debugumgebungen  

> **Philosophie:**  
> Keine externe Abhängigkeit, keine Installation, keine Migrationen – Plug & Play JSON-Datenbanken mit optionaler Verschlüsselung.

---

# 🚀 Features

✔ JSON-basierte Datenbank  
✔ Tabellenstruktur mit Headerzeile  
✔ Insert / Select / Update / Delete  
✔ Auto-ID-System  
✔ Vollständige Dateiverschlüsselung (AES-256)  
✔ Atomic-Write für sichere Saves  
✔ Pluginsystem  
✔ Session & Cookie Wrapper  
✔ HTTPS-Forwarding & Proxy Awareness  
✔ ReCaptcha Wrapper  
✔ Tools, Time, Validate, Route, Http u.v.m.  
✔ UI für Datenbankverwaltung *(greenql_ui)*  
✔ SQL-Bridge vorhanden (optional)  
✔ Zero-Dependency, kein Composer notwendig  

---

# 📁 Verzeichnisstruktur

```

assets/
├── DB/
│    └── GBDB/              # Datenbanken (JSON)
├── php/
│    └── inc/
│          ├── gbdb_framework/
│          │       ├── ENV.php
│          │       ├── gbdb.php            # Loader + Bootstrap
│          │       ├── core/               # Systemklassen
│          │       ├── plugins/            # Erweiterungen
│          │       └── ui/                 # greenql_ui
│          └── Srv.php
├── css/
├── js/
└── img/

````

---

# ⚙ Installation

1. Repository herunterladen  
2. In deine PHP-App einbinden:

```php
include 'assets/php/inc/gbdb_framework/gbdb.php';
````

3. ENV.php konfigurieren:

```php
Vars::set("DB_PATH", "assets/DB/GBDB/");
Vars::set("data_extension", ".json");
Vars::set("__DEV__", true);
Vars::set("crypt_data", false); // JSON verschlüsseln?
```

Fertig.

---

# 🗄 Datenbanksystem (GBDB)

## 📌 Grundprinzip

Eine Datenbank ist ein Ordner:

```
assets/DB/GBDB/main/
```

Eine Tabelle ist eine JSON-Datei:

```
main/users.json
```

Der Inhalt besteht aus:

* **Header-Zeile** (`id = -1`, enthält Spaltennamen)
* **Datensätzen**

Beispiel:

```json
[
  {
    "id": -1,
    "name": "-header-",
    "email": "-header-"
  },
  {
    "id": 0,
    "name": "Markus",
    "email": "test@example.com"
  }
]
```

---

# 🧩 Core-Funktionen (GBDB)

## Neue Datenbank erstellen

```php
GBDB::createDatabase("main");
```

## Tabelle erstellen

```php
GBDB::createTable("main", "users", ["name", "email", "age"]);
```

## Eintrag hinzufügen

```php
$id = GBDB::insertData("main", "users", [
    "name" => "Max",
    "email" => "max@test.de",
    "age" => 22
]);
```

## Daten auslesen

```php
$data = GBDB::getData("main", "users");
```

Gefiltert:

```php
$user = GBDB::getData("main", "users", true, "email", "max@test.de");
```

## Datensatz bearbeiten

```php
GBDB::editData("main", "users", "id", 5, [
    "name" => "Neuer Name"
]);
```

## Datensatz löschen

```php
GBDB::deleteData("main", "users", "id", 5);
```

## Tabellen oder Datenbanken löschen

```php
GBDB::deleteTable("main", "users");
GBDB::deleteDatabase("main");
```

## Tabellen & Datenbanken auflisten

```php
$dbs    = GBDB::listDBs();
$tables = GBDB::listTables("main");
```

## Nächste ID abrufen

```php
$nextID = GBDB::nextID("main", "users");
```

## Header / Spaltennamen abrufen

```php
$keys = GBDB::getKeys("main", "users");
```

---

# 🔐 Verschlüsselung (optional)

Aktiviere in ENV.php:

```php
Vars::set("crypt_data", true);
```

Dann speichert GBDB die JSON-Dateien AES-256-verschlüsselt.

---

# 🌐 greenql_ui – Webinterface

Unter `greenql_ui.php` liegt ein modernes UI:

### Features:

* Datenbanken auswählen
* Tabellen anzeigen
* Datensätze hinzufügen
* Datensätze löschen
* Datensätze bearbeiten (Inline-Edit)
* Suchfunktion

Keine Installation – einfach öffnen:

```
http://localhost/greenql_ui.php
```

---

# 🔧 Systemklassen (Kurzüberblick)

| Klasse        | Aufgabe                                    |
| ------------- | ------------------------------------------ |
| **GBDB**      | JSON-Datenbankengine                       |
| **Vars**      | Umgebungsvariablen                         |
| **Ref**       | Redirects                                  |
| **Http**      | GET/POST Wrapper (mit sendMail)            |
| **Cache**     | Session-Cache-System                       |
| **Auth**      | Login- & Cookie-basierte Authentifizierung |
| **Tools**     | Utilities (Token, Passwörter, QR usw.)     |
| **Validate**  | Validierungssystem                         |
| **Time**      | Zeitfunktionen (timeAgo etc.)              |
| **Route**     | Werkzeug für kleine Router                 |
| **Crypt**     | AES-256 Verschlüsselung                    |
| **FileTool**  | Dateioperationen                           |
| **Fs**        | Filesystem Helper                          |
| **SQL**       | SQL-Bridge (PDO)                           |
| **SrvP**      | Server-Job-System                          |
| **ReCaptcha** | Google reCAPTCHA Wrapper                   |
| **Converter** | Datenkonvertierung                         |
| **getForm**   | HTML-Formularerzeugung                     |

---

# 🧪 Beispielprojekt

```php
include 'assets/php/inc/gbdb_framework/gbdb.php';

if (!GBDB::elementExists("main", "users", "email", "test@test.de")) {
    GBDB::insertData("main", "users", [
        "name" => "Test",
        "email" => "test@test.de",
    ]);
}

$data = GBDB::getData("main", "users");
print_r($data);
```

---

# 🛡 Sicherheit

* Atomic writes verhindern Datenkorruption
* Optional verschlüsselte Tabellen
* Validierungssystem
* CSRF-sichere Formularerzeugung
* HTTPS-Erkennung & Redirect

---

# 🧩 Voraussetzungen

* PHP 8.1+
* Schreibrechte im DB-Ordner
* Keine weiteren Libraries notwendig

