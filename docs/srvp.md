# SrvP

## Zweck

Remote-Client für das SecondServerModul. Er spricht `backend.php` an, holt Einmal-Tokens und kapselt Remote-GBDB, GBDBv2, Auth, GreenQL und Service-Jobs.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `SrvP` zur Verfügung.

## Typisches Beispiel

```php
SrvP::setInstance("kunde_a");
$result = SrvP::query("ROOT main; PICK * FROM users LIMIT 10;");
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`setContext(array $ctx)`|void|öffentliche Methode der Klasse|
|`setInstance(string $instance)`|void|öffentliche Methode der Klasse|
|`getContext()`|array|öffentliche Methode der Klasse|
|`driver(array $ctx = [])`|array|öffentliche Methode der Klasse|
|`listInstances()`|array|öffentliche Methode der Klasse|
|`createInstance(string $instance)`|array|öffentliche Methode der Klasse|
|`deleteInstance(string $instance, bool $force = false)`|array|öffentliche Methode der Klasse|
|`listDBs(array $ctx = [])`|array|öffentliche Methode der Klasse|
|`listTables(string $db, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`createDatabase(string $db, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`deleteDatabase(string $db, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`createTable(string $db, string $table, array $cols, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`deleteTable(string $db, string $table, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`getKeys(string $db, string $table, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`getData(string $db, string $table, bool $filter = false, string $where = "", string $is = "", array $ctx = [])`|array|öffentliche Methode der Klasse|
|`addData(string $db, string $table, array $data, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`insertData(string $db, string $table, array $data, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`deleteData(string $db, string $table, string $where, string $is, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`editData(string $db, string $table, string $where, string $is, array $data, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`query(string $script, array $ctx = [], array $params = [])`|array|öffentliche Methode der Klasse|
|`runScript(string $path, array $params = [], array $ctx = [])`|array|öffentliche Methode der Klasse|
|`auth_init()`|array|öffentliche Methode der Klasse|
|`auth_login(string $username_or_email, string $plain_text_password)`|array|öffentliche Methode der Klasse|
|`auth_token(string $jwt)`|array|öffentliche Methode der Klasse|
|`auth_logout(string $jwt)`|array|öffentliche Methode der Klasse|
|`auth_login2Fa(string $uid, string $code)`|array|öffentliche Methode der Klasse|
|`auth_me(string $jwt)`|array|öffentliche Methode der Klasse|
|`auth_get(string $table, string $where = "", string $is = "")`|array|öffentliche Methode der Klasse|
|`auth_user(string $uid)`|array|öffentliche Methode der Klasse|
|`auth_newUser(array $user_data, array $user_meta = [], bool $is_this_register = false)`|array|öffentliche Methode der Klasse|
|`auth_editUser(string $uid, array $user_data, array $user_meta = [])`|array|öffentliche Methode der Klasse|
|`auth_delete(string $table, string $where, string $is)`|array|öffentliche Methode der Klasse|
|`auth_verifyEmail(string $token)`|array|öffentliche Methode der Klasse|
|`auth_verify2FaCode(string $code)`|array|öffentliche Methode der Klasse|
|`srv_enqueue(string $service, string $action, array $payload = [], array $ctx = [])`|array|öffentliche Methode der Klasse|
|`srv_run_one(int $id, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`srv_status(?int $id = null, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`srv_logs(int $job_id)`|array|öffentliche Methode der Klasse|
|`srv_jobs(array $ctx = [])`|array|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`endpoint()`|private|
|`ctx(array $ctx = [])`|private|
|`request(array $payload)`|private|
|`data(array $resp)`|private|
|`getToken()`|private|
|`payloadWithToken(array $body)`|private|
|`payload(array $body, array $ctx = [])`|private|

## Konstanten

_Keine dokumentationsrelevanten Konstanten._

## Verwendung im Framework

`SrvP` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `SrvP` aufrufen.
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

## Advanced-GBDB Remote-Funktionen

SrvP/Backend unterstützen jetzt auch die neuen Advanced-Funktionen für GBDB und GBDBv2. Bei GBDBv2 kann wie gewohnt ein Kontext mit `SrvP::setInstance("kunde1")` oder über den letzten `$ctx`-Parameter übergeben werden.

```php
SrvP::instance_exists("kunde1");
SrvP::base_exists("main");
SrvP::table_exists("main", "users");
SrvP::data_exists("main", "users", "email", "max@example.de");

SrvP::monitor();
SrvP::monitor("main", "users");
SrvP::recover("main", "users");
SrvP::page("main", "users", 1, 50);
SrvP::cursor("main", "users", 100);
SrvP::fulltext_search("main", "users", "Max Muster", ["username", "email"], 25);
```

Backend-`do`-Actions:

- `instance_exists`
- `base_exists`
- `table_exists`
- `data_exists`
- `monitor`
- `recover`
- `page`
- `cursor`
- `fulltext_search`

`fulltext` bleibt als Backend-Kompatibilitätsalias erhalten. Die bevorzugte neue PHP-Methode ist `fulltext_search`.
