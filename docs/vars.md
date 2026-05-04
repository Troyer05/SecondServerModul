# Vars

## Zweck

zentrale Konfigurationsklasse des gesamten Frameworks. `Vars` ist die Quelle für App-Version, API-Gates, DB-Pfade, SQL-Zugang, Cookies, Sessions, SRVP, mRoot, MuseumQR und Sicherheitsoptionen.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `Vars` zur Verfügung.

## Typisches Beispiel

```php
$version = Vars::app_version();
$dbPath = Vars::DB_PATH();
$apiEnabled = Vars::pApi_access_gbdb();
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`AUTH()`|array|öffentliche Methode der Klasse|
|`__DEV__()`|bool|öffentliche Methode der Klasse|
|`app_version()`|string|öffentliche Methode der Klasse|
|`pApi_need_auth()`|bool|öffentliche Methode der Klasse|
|`pApi_auth_keys()`|array|öffentliche Methode der Klasse|
|`pApi_access_gbdb()`|bool|öffentliche Methode der Klasse|
|`pApi_write_gbdb()`|bool|öffentliche Methode der Klasse|
|`pApi_greenql()`|bool|öffentliche Methode der Klasse|
|`mRoot_url()`|string|öffentliche Methode der Klasse|
|`mRoot_license_form()`|string|öffentliche Methode der Klasse|
|`mRoot_pid()`|string|öffentliche Methode der Klasse|
|`mRoot_auth()`|string|öffentliche Methode der Klasse|
|`update_auth()`|string|öffentliche Methode der Klasse|
|`srvp_ip()`|string|öffentliche Methode der Klasse|
|`srvp_ssl()`|bool|öffentliche Methode der Klasse|
|`srvp_static_key()`|string|öffentliche Methode der Klasse|
|`srvp_api_log()`|bool|öffentliche Methode der Klasse|
|`srvp_log_path()`|string|öffentliche Methode der Klasse|
|`sharesuite_api_url()`|string|öffentliche Methode der Klasse|
|`sharesuite_api_key()`|string|öffentliche Methode der Klasse|
|`sharesuite_api_auth()`|string|öffentliche Methode der Klasse|
|`sharesuite_sid()`|string|öffentliche Methode der Klasse|
|`mqr_api_url()`|string|öffentliche Methode der Klasse|
|`mqr_api_key()`|string|öffentliche Methode der Klasse|
|`enable_https_redirect()`|bool|öffentliche Methode der Klasse|
|`json_path()`|string|öffentliche Methode der Klasse|
|`json_pretty()`|bool|öffentliche Methode der Klasse|
|`sql_server()`|string|öffentliche Methode der Klasse|
|`sql_database()`|string|öffentliche Methode der Klasse|
|`sql_user()`|string|öffentliche Methode der Klasse|
|`sql_password()`|string|öffentliche Methode der Klasse|
|`sql_dev_server()`|string|öffentliche Methode der Klasse|
|`sql_dev_database()`|string|öffentliche Methode der Klasse|
|`sql_dev_user()`|string|öffentliche Methode der Klasse|
|`sql_dev_password()`|string|öffentliche Methode der Klasse|
|`reCaptcha_website_key()`|string|öffentliche Methode der Klasse|
|`reCaptcha_secret_key()`|string|öffentliche Methode der Klasse|
|`crypt_data()`|bool|öffentliche Methode der Klasse|
|`cryptKey()`|string|öffentliche Methode der Klasse|
|`data_extension()`|string|öffentliche Methode der Klasse|
|`init_cookies()`|array|öffentliche Methode der Klasse|
|`init_session()`|array|öffentliche Methode der Klasse|
|`EQR_API_URL()`|string|öffentliche Methode der Klasse|
|`EQR_API_AUTH()`|string|öffentliche Methode der Klasse|
|`this_file()`|string|öffentliche Methode der Klasse|
|`this_path()`|string|öffentliche Methode der Klasse|
|`this_uri()`|string|öffentliche Methode der Klasse|
|`client_ip()`|string|öffentliche Methode der Klasse|
|`DB_PATH()`|string|öffentliche Methode der Klasse|
|`jpretty()`|int|öffentliche Methode der Klasse|
|`framework_version()`|string|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`serverVar(string $key, $default = "")`|protected|

## Konstanten

|Sichtbarkeit|Konstante|
|---|---|
|private|`APP`|
|private|`PUBLIC_API`|
|private|`MROOT`|
|private|`UPDATE`|
|private|`SRVP`|
|private|`SHARESUTE`|
|private|`MQR`|
|private|`SECURITY`|
|private|`GBDB`|
|private|`SQL`|
|private|`RECAPTCHA`|
|private|`EQR_API`|
|private|`INIT_COOKIES`|
|private|`INIT_SESSION`|

## Verwendung im Framework

`Vars` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `Vars` aufrufen.
4. Rückgabe prüfen.
5. Fehlerfälle sauber behandeln und keine sensitiven Werte ausgeben.

## Hinweise zur Verwendung

- Eingaben aus Formularen oder Requests vor der Übergabe validieren.
- Rückgaben immer auf erwartete Struktur prüfen, insbesondere bei API-/Remote-Klassen.
- Bei Klassen mit Datei- oder DB-Zugriff müssen Schreibrechte im Projektordner passen.
- Bei sicherheitsrelevanten Klassen keine Tokens, Passwörter oder Secrets in Logs ausgeben.
- Bei Erweiterungen den bestehenden Stil beibehalten: Konstanten zuerst, private Helfer danach, öffentliche Methoden am Ende.

## Fehlerquellen

| Problem | Mögliche Ursache | Empfehlung |
|---|---|---|
| leere oder unerwartete Rückgabe | fehlende Daten, falscher Pfad oder falscher Context | Eingabeparameter und Config prüfen. |
| Schreiboperation schlägt fehl | Webserver hat keine Rechte | Besitzer, Gruppe und ACLs prüfen. |
| Remote/API-Antwort ungültig | Endpoint, Auth oder JSON-Format falsch | Response debuggen, aber Secrets maskieren. |
| Methode wirkt ohne Effekt | falsche Instanz, falsche Base oder Cache-Zustand | Context und aktive Instanz kontrollieren. |

## Erweiterungsidee

Wenn diese Klasse erweitert wird, sollte jede neue öffentliche Methode ein klares Ziel haben, eine robuste Rückgabe liefern und keine versteckten Seiteneffekte erzeugen. Für wiederkehrende Validierung oder Normalisierung besser private Hilfsmethoden ergänzen, statt Logik in mehreren öffentlichen Methoden zu duplizieren.
