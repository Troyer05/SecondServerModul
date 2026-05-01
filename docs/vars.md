# Vars
## Zweck
`Vars` ist die zentrale Framework-Konfiguration. Alle produktabhängigen Pfade, API-Keys, DB-Optionen, SecondServer-Einstellungen und Security-Flags werden hier gekapselt.
## Datei und Einbindung
- Klasse: `Vars`
- Datei: `assets/php/inc/gbdb_framework/ENV.php`
- Wird normalerweise über `assets/php/inc/gbdb_framework/gbdb.php` oder über `assets/php/inc/.config/_config.inc.php` geladen.

## Wichtige Konfiguration
Alle Werte werden über statische Methoden geliefert. Dadurch kann Anwendungscode `Vars::srvp_ip()`, `Vars::json_path()` oder `Vars::AUTH()` nutzen, ohne globale Variablen direkt zu lesen. Secrets gehören nicht in öffentliche Repositories.

## Konstanten
| Konstante | Zweck / Wert |
|---|---|
| `APP` | `[ "version" => "1.0", ]` |
| `MROOT` | `[ "url" => "https://mamueller.de/mroot/api.php", "license_form" => "lizenz.php", "pid" => "12345", "auth" => "AWE-mm_4",` |
| `UPDATE` | `[ "auth" => "AWE-mm_4", ]` |
| `SRVP` | `[ "ip" => "127.0.0.1/REPOS/SecondServerModul", "ssl" => false, "static_key" => "abc", "api_log" => false, "log_path" => ` |
| `SHARESUTE` | `[ "api_url" => "", "api_key" => "", "api_auth" => "", "sid" => "" ]` |
| `MQR` | `[ "api_url" => "https://museumqr.de/api.php", "api_key" => "DEIN_API_KEY", ]` |
| `SECURITY` | `[ "https_redirect" => true, "crypt_data" => true, "crypt_key" => "abc", ]` |
| `GBDB` | `[ "json_path" => "assets/DB/", ]` |
| `SQL` | `[ "prod" => [ "server" => "", "database" => "", "user" => "", "password" => "", ], "dev" => [ "server" => "", "database"` |
| `RECAPTCHA` | `[ "website_key" => "", "secret_key" => "", ]` |
| `EQR_API` | `[ "url" => "", "auth" => "" ]` |
| `INIT_COOKIES` | `[ [ "cookie_name" => "TestCookie", "cookie_value" => "Test1", ], [ "cookie_name" => "Cookie2", "cookie_value" => "abc", ` |
| `INIT_SESSION` | `[ [ "session_name" => "pnp", "session_value" => "", ], [ "session_name" => "Test Session Variable 2", "session_value" =>` |

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
| `AUTH()` | `array` | Umgebungsvariablen zur Konfiguration von GBDB-FrameWork protected static ?bool $isDev = null; private const APP = [ "version" => "1.0", ]; private const MROOT = [ "url"          => "https://mamueller.de/mroot/api.php", "license_form" => "lizenz.php", "pid"          => "12345", "auth"         => "AWE-mm_4", ]; private const UPDATE = [ "auth" => "AWE-mm_4", ]; private const SRVP = [ "ip"         => "127.0.0.1/REPOS/SecondServerModul", "ssl"        => false, "static_key" => "abc", "api_log"    => false, "log_path"   => "assets/php/srv_logs/", ]; private const SHARESUTE = [ "api_url"  => "", "api_key"  => "", "api_auth" => "", "sid" => "" ]; private const MQR = [ "api_url" => "https://museumqr.de/api.php", "api_key" => "DEIN_API_KEY", ]; private const SECURITY = [ "https_redirect" => true, "crypt_data"     => true, "crypt_key"      => "abc", ]; private const GBDB = [ "json_path" => "assets/DB/", ]; private const SQL = [ "prod" => [ "server"   => "", "database" => "", "user"     => "", "password" => "", ], "dev" => [ "server"   => "", "database" => "", "user"     => "", "password" => "", ], ]; private const RECAPTCHA = [ "website_key" => "", "secret_key"  => "", ]; private const EQR_API = [ "url" => "", "auth" => "" ]; private const INIT_COOKIES = [ [ "cookie_name"  => "TestCookie", "cookie_value" => "Test1", ], [ "cookie_name"  => "Cookie2", "cookie_value" => "abc", ], ]; private const INIT_SESSION = [ [ "session_name"  => "pnp", "session_value" => "", ], [ "session_name"  => "Test Session Variable 2", "session_value" => "Test 2", ], ]; Verarbeitet die Funktion a u t h. |
| `__DEV__()` | `bool` | Kapselt die Fachlogik für `__DEV__()` innerhalb dieser Klasse. |
| `app_version()` | `string` | Kapselt die Fachlogik für `app_version()` innerhalb dieser Klasse. |
| `mRoot_url()` | `string` | Kapselt die Fachlogik für `mRoot_url()` innerhalb dieser Klasse. |
| `mRoot_license_form()` | `string` | Kapselt die Fachlogik für `mRoot_license_form()` innerhalb dieser Klasse. |
| `mRoot_pid()` | `string` | Kapselt die Fachlogik für `mRoot_pid()` innerhalb dieser Klasse. |
| `mRoot_auth()` | `string` | Kapselt die Fachlogik für `mRoot_auth()` innerhalb dieser Klasse. |
| `update_auth()` | `string` | Kapselt die Fachlogik für `update_auth()` innerhalb dieser Klasse. |
| `srvp_ip()` | `string` | Kapselt die Fachlogik für `srvp_ip()` innerhalb dieser Klasse. |
| `srvp_ssl()` | `bool` | Kapselt die Fachlogik für `srvp_ssl()` innerhalb dieser Klasse. |
| `srvp_static_key()` | `string` | Kapselt die Fachlogik für `srvp_static_key()` innerhalb dieser Klasse. |
| `srvp_api_log()` | `bool` | Kapselt die Fachlogik für `srvp_api_log()` innerhalb dieser Klasse. |
| `srvp_log_path()` | `string` | Kapselt die Fachlogik für `srvp_log_path()` innerhalb dieser Klasse. |
| `sharesuite_api_url()` | `string` | Kapselt die Fachlogik für `sharesuite_api_url()` innerhalb dieser Klasse. |
| `sharesuite_api_key()` | `string` | Kapselt die Fachlogik für `sharesuite_api_key()` innerhalb dieser Klasse. |
| `sharesuite_api_auth()` | `string` | Kapselt die Fachlogik für `sharesuite_api_auth()` innerhalb dieser Klasse. |
| `sharesuite_sid()` | `string` | Kapselt die Fachlogik für `sharesuite_sid()` innerhalb dieser Klasse. |
| `mqr_api_url()` | `string` | Kapselt die Fachlogik für `mqr_api_url()` innerhalb dieser Klasse. |
| `mqr_api_key()` | `string` | Kapselt die Fachlogik für `mqr_api_key()` innerhalb dieser Klasse. |
| `enable_https_redirect()` | `bool` | Kapselt die Fachlogik für `enable_https_redirect()` innerhalb dieser Klasse. |
| `json_path()` | `string` | Kapselt die Fachlogik für `json_path()` innerhalb dieser Klasse. |
| `json_pretty()` | `bool` | Kapselt die Fachlogik für `json_pretty()` innerhalb dieser Klasse. |
| `sql_server()` | `string` | Kapselt die Fachlogik für `sql_server()` innerhalb dieser Klasse. |
| `sql_database()` | `string` | Kapselt die Fachlogik für `sql_database()` innerhalb dieser Klasse. |
| `sql_user()` | `string` | Kapselt die Fachlogik für `sql_user()` innerhalb dieser Klasse. |
| `sql_password()` | `string` | Kapselt die Fachlogik für `sql_password()` innerhalb dieser Klasse. |
| `sql_dev_server()` | `string` | Kapselt die Fachlogik für `sql_dev_server()` innerhalb dieser Klasse. |
| `sql_dev_database()` | `string` | Kapselt die Fachlogik für `sql_dev_database()` innerhalb dieser Klasse. |
| `sql_dev_user()` | `string` | Kapselt die Fachlogik für `sql_dev_user()` innerhalb dieser Klasse. |
| `sql_dev_password()` | `string` | Kapselt die Fachlogik für `sql_dev_password()` innerhalb dieser Klasse. |
| `reCaptcha_website_key()` | `string` | Kapselt die Fachlogik für `reCaptcha_website_key()` innerhalb dieser Klasse. |
| `reCaptcha_secret_key()` | `string` | Kapselt die Fachlogik für `reCaptcha_secret_key()` innerhalb dieser Klasse. |
| `crypt_data()` | `bool` | Kapselt die Fachlogik für `crypt_data()` innerhalb dieser Klasse. |
| `cryptKey()` | `string` | Kapselt die Fachlogik für `cryptKey()` innerhalb dieser Klasse. |
| `data_extension()` | `string` | Kapselt die Fachlogik für `data_extension()` innerhalb dieser Klasse. |
| `init_cookies()` | `array` | Initialisiert benötigte Tabellen, Sessions, Cookies oder interne Zustände. |
| `init_session()` | `array` | Initialisiert benötigte Tabellen, Sessions, Cookies oder interne Zustände. |
| `EQR_API_URL()` | `string` | Kapselt die Fachlogik für `EQR_API_URL()` innerhalb dieser Klasse. |
| `EQR_API_AUTH()` | `string` | Kapselt die Fachlogik für `EQR_API_AUTH()` innerhalb dieser Klasse. |
| `this_file()` | `string` | Kapselt die Fachlogik für `this_file()` innerhalb dieser Klasse. |
| `this_path()` | `string` | Kapselt die Fachlogik für `this_path()` innerhalb dieser Klasse. |
| `this_uri()` | `string` | Kapselt die Fachlogik für `this_uri()` innerhalb dieser Klasse. |
| `client_ip()` | `string` | Kapselt die Fachlogik für `client_ip()` innerhalb dieser Klasse. |
| `DB_PATH()` | `string` | Kapselt die Fachlogik für `DB_PATH()` innerhalb dieser Klasse. |
| `jpretty()` | `int` | Kapselt die Fachlogik für `jpretty()` innerhalb dieser Klasse. |

## Beispiele
```php
include 'assets/php/inc/.config/_config.inc.php';

// Beispielaufruf; Parameter bitte passend zum Projekt einsetzen.
$result = Vars::AUTH();
var_dump($result);
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `protected static serverVar(string $key, $default = "") : mixed` – Kapselt die Fachlogik für `serverVar()` innerhalb dieser Klasse.

## Best Practices
- Öffentliche Methoden bevorzugen und interne Dateipfade nicht hart im Anwendungscode duplizieren.
- Rückgaben immer validieren, bevor sie in HTML, API-Antworten oder weitere DB-Operationen fließen.
- Für neue Features erst Schema/Tabellen sauber anlegen und danach Daten schreiben.
- Für produktive Systeme Backups, Schreibrechte und Authentifizierung vor dem Rollout testen.

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
