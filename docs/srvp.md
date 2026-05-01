# SrvP
## Zweck
`SrvP` ist der Client/Proxy auf Server 1. Die Klasse spricht mit `backend.php` auf Server 2, holt Einmal-Tokens, sendet signierte Requests und kapselt Remote-GBDB, GreenQL, Auth und Job-Aufrufe.
## Datei und Einbindung
- Klasse: `SrvP`
- Datei: `assets/php/inc/gbdb_framework/core/srvp.php`
- Wird normalerweise über `assets/php/inc/gbdb_framework/gbdb.php` oder über `assets/php/inc/.config/_config.inc.php` geladen.

## Wichtige Konfiguration
Benötigt `Vars::srvp_ip()`, `Vars::srvp_ssl()`, `Vars::srvp_static_key()` und erreichbar deploytes `backend.php`. Der Static-Key wird gehasht übertragen und zusätzlich mit Einmal-Token abgesichert.

## Arbeitsweise
Die Klasse wird überwiegend statisch genutzt. Öffentliche Methoden sind die stabile API für Projektcode. Private/protected Methoden sind interne Bausteine und sollten nicht direkt aus Anwendungen heraus verwendet werden.

Typische Aufrufkette:

1. Framework-Konfiguration laden.
2. Optional benötigte Initialisierung ausführen.
3. Öffentliche Methode der Klasse nutzen.
4. Rückgabewert auf Fehler/Leere prüfen.

## Öffentliche API
| Methode | Rückgabe | Beschreibung |
|---|---:|---|
| `setContext(array $ctx)` | `void` | Setzt den Remote-Kontext, z.B. GBDBv2 Instanz. |
| `setInstance(string $instance)` | `void` | Setzt die Remote-GBDBv2-Instanz. |
| `getContext()` | `array` | Gibt den aktuellen Remote-Kontext zurück. |
| `driver(array $ctx = [])` | `array` | Prüft den Backend-Treiber. |
| `listInstances()` | `array` | Listet Instanzen. |
| `createInstance(string $instance)` | `array` | Erstellt eine Instanz. |
| `deleteInstance(string $instance, bool $force = false)` | `array` | Löscht eine Instanz. |
| `listDBs(array $ctx = [])` | `array` | Listet Bases. |
| `listTables(string $db, array $ctx = [])` | `array` | Listet Tabellen. |
| `createDatabase(string $db, array $ctx = [])` | `array` | Erstellt eine Base. |
| `deleteDatabase(string $db, array $ctx = [])` | `array` | Löscht eine Base. |
| `createTable(string $db, string $table, array $cols, array $ctx = [])` | `array` | Erstellt eine Tabelle. |
| `deleteTable(string $db, string $table, array $ctx = [])` | `array` | Löscht eine Tabelle. |
| `getKeys(string $db, string $table, array $ctx = [])` | `array` | Liest Tabellenschlüssel. |
| `getData(string $db, string $table, bool $filter = false, string $where = "", string $is = "", array $ctx = [])` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `addData(string $db, string $table, array $data, array $ctx = [])` | `array` | Kapselt die Fachlogik für `addData()` innerhalb dieser Klasse. |
| `insertData(string $db, string $table, array $data, array $ctx = [])` | `array` | Alias für addData. |
| `deleteData(string $db, string $table, string $where, string $is, array $ctx = [])` | `array` | Löscht einen Eintrag oder entfernt eine Ressource. |
| `editData(string $db, string $table, string $where, string $is, array $data, array $ctx = [])` | `array` | Aktualisiert vorhandene Daten anhand eines Suchkriteriums. |
| `query(string $script, array $ctx = [], array $params = [])` | `array` | Führt eine GreenQL-Abfrage aus. |
| `runScript(string $path, array $params = [], array $ctx = [])` | `array` | Führt ein Script aus und gibt das Ergebnis zurück. |
| `auth_init()` | `array` | Initialisiert Auth auf dem Zielserver. |
| `auth_login(string $username_or_email, string $plain_text_password)` | `array` | Meldet einen Benutzer über den Zielserver an. |
| `auth_token(string $jwt)` | `array` | Prüft einen Auth-Token über den Zielserver. |
| `auth_logout(string $jwt)` | `array` | Meldet einen Benutzer remote ab. |
| `auth_login2Fa(string $uid, string $code)` | `array` | Prüft 2FA remote. |
| `auth_me(string $jwt)` | `array` | Liest den aktuell authentifizierten Benutzer remote. |
| `auth_get(string $table, string $where = "", string $is = "")` | `array` | Liest Auth-Daten über den Zielserver. |
| `auth_user(string $uid)` | `array` | Liest einen Benutzer über den Zielserver. |
| `auth_newUser(array $user_data, array $user_meta = [], bool $is_this_register = false)` | `array` | Legt einen Benutzer über den Zielserver an. |
| `auth_editUser(string $uid, array $user_data, array $user_meta = [])` | `array` | Bearbeitet einen Benutzer über den Zielserver. |
| `auth_delete(string $table, string $where, string $is)` | `array` | Löscht Auth-Daten über den Zielserver. |
| `auth_verifyEmail(string $token)` | `array` | Verifiziert eine E-Mail über den Zielserver. |
| `auth_verify2FaCode(string $code)` | `array` | Verifiziert einen 2FA-Code über den Zielserver. |
| `srv_enqueue(string $service, string $action, array $payload = [], array $ctx = [])` | `array` | Kapselt die Fachlogik für `srv_enqueue()` innerhalb dieser Klasse. |
| `srv_run_one(int $id, array $ctx = [])` | `array` | Kapselt die Fachlogik für `srv_run_one()` innerhalb dieser Klasse. |
| `srv_status(?int $id = null, array $ctx = [])` | `array` | Kapselt die Fachlogik für `srv_status()` innerhalb dieser Klasse. |
| `srv_logs(int $job_id)` | `array` | Kapselt die Fachlogik für `srv_logs()` innerhalb dieser Klasse. |
| `srv_jobs(array $ctx = [])` | `array` | Kapselt die Fachlogik für `srv_jobs()` innerhalb dieser Klasse. |

## Beispiele
```php
include 'assets/php/inc/.config/_config.inc.php';

SrvP::setInstance('kunde_a');
SrvP::createDatabase('main');
SrvP::createTable('main', 'logs', ['type', 'message']);
SrvP::insertData('main', 'logs', ['type' => 'info', 'message' => 'remote ok']);

$rows = SrvP::getData('main', 'logs');
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `private static endpoint() : string` – Ermittelt den API-Endpunkt.
- `private static ctx(array $ctx = []) : array` – Kombiniert globalen und lokalen Kontext.
- `private static request(array $payload) : array` – Sendet eine Anfrage und verarbeitet die Antwort.
- `private static data(array $resp) : mixed` – Gibt den data-Teil einer Backend-Antwort zurück.
- `private static getToken() : string` – Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück.
- `private static payloadWithToken(array $body) : array` – Kapselt die Fachlogik für `payloadWithToken()` innerhalb dieser Klasse.
- `private static payload(array $body, array $ctx = []) : array` – Fügt Kontext zur Nutzlast hinzu.

## Best Practices
- Öffentliche Methoden bevorzugen und interne Dateipfade nicht hart im Anwendungscode duplizieren.
- Rückgaben immer validieren, bevor sie in HTML, API-Antworten oder weitere DB-Operationen fließen.
- Für neue Features erst Schema/Tabellen sauber anlegen und danach Daten schreiben.
- Für produktive Systeme Backups, Schreibrechte und Authentifizierung vor dem Rollout testen.

## Zusatzhinweise
Jeder Request holt zuerst ein Einmal-Token. Das ist absichtlich etwas mehr Overhead, verhindert aber einfache Wiederverwendung abgefangener Requests.

## Integration in eigene Projekte

Beim Einbau in neue Projekte sollte diese Komponente nicht isoliert betrachtet werden. Fast alle Framework-Klassen hängen indirekt an der zentralen Konfiguration `Vars` und an der gemeinsamen Einbindung über `_config.inc.php`. Dadurch bleibt der Anwendungscode kurz, aber Konfigurationsfehler fallen oft erst zur Laufzeit auf. Für saubere Projekte empfiehlt es sich deshalb, zuerst eine kleine Setup- oder Healthcheck-Seite anzulegen, die prüft, ob die Klasse geladen ist, ob die benötigten Pfade existieren und ob Schreib-/Leserechte stimmen.

Ein typischer Integrationsablauf sieht so aus:

1. `_config.inc.php` laden.
2. Benötigte Konstanten und `Vars`-Werte prüfen.
3. Falls nötig Initialisierung ausführen.
4. Einen einfachen Leseaufruf testen.
5. Einen einfachen Schreibaufruf testen.
6. Fehlerfälle testen, nicht nur den Erfolgsfall.

## Test-Checkliste

- Läuft der Code lokal und auf dem Server mit derselben PHP-Version?
- Sind alle benötigten Core-Dateien wirklich geladen?
- Sind Rückgaben dokumentiert und werden sie im Anwendungscode geprüft?
- Gibt es einen Test mit leerer Eingabe, ungültiger Eingabe und gültiger Eingabe?
- Sind Dateipfade relativ zum Projekt-Root und nicht zum aktuellen Browserpfad gedacht?
- Sind produktive Secrets aus Logs, Fehlermeldungen und Screenshots entfernt?
- Funktioniert der Ablauf nach einem frischen Upload ohne manuelles Nachbessern der Rechte?

## Wartung und Erweiterung

Wenn diese Klasse erweitert wird, sollte jede neue öffentliche Methode sofort in dieser Dokumentation auftauchen. Bei Klassen, die mit GBDB arbeiten, muss außerdem geprüft werden, ob neue Tabellen oder Spalten in `schema.json` bzw. `schema_v2.json` berücksichtigt werden müssen. Bei Klassen, die Remote-Requests ausführen, sollten Fehlermeldungen immer so formuliert werden, dass Entwickler das Problem finden können, ohne dabei Auth-Tokens oder API-Keys offenzulegen.

## Praktische Hinweise für andere Entwickler

Dieses Framework folgt bewusst einem sehr direkten PHP-Stil. Viele Methoden sind statisch und dadurch einfach aufzurufen. Der Nachteil ist, dass falsche globale Konfigurationen schneller Auswirkungen auf mehrere Klassen haben. Andere Entwickler sollten deshalb nicht nur die einzelne Methode lesen, sondern auch die umgebenden Dateien `ENV.php`, `_config.inc.php` und bei Remote-Funktionen `backend.php` prüfen.
