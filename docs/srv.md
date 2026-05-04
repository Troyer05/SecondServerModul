# Srv

## Zweck

Backend-Serviceklasse für Jobs, Module, Auth-Dispatch und Script-Ausführung.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `Srv` zur Verfügung.

## Typisches Beispiel

```php
$jobId = Srv::enqueue("Mail", "send", ["to" => "demo@example.com"]);
$result = Srv::runOne((int)$jobId);
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`enqueue(string $service, string $action, array $payload = [], array $ctx = [])`|mixed/void|öffentliche Methode der Klasse|
|`getJobs(array $ctx = [])`|mixed/void|öffentliche Methode der Klasse|
|`getJob(int $id, array $ctx = [])`|mixed/void|öffentliche Methode der Klasse|
|`runScript(string $path, array $params = [], array $ctx = [])`|array|öffentliche Methode der Klasse|
|`auth(string $action, array $body)`|array|öffentliche Methode der Klasse|
|`runOne(int $id, array $ctx = [])`|array|öffentliche Methode der Klasse|
|`loadModule(string $service)`|mixed/void|öffentliche Methode der Klasse|
|`log(int $jobId, string $level, string $message, $extra = null)`|mixed/void|öffentliche Methode der Klasse|
|`logs(int $jobId)`|array|öffentliche Methode der Klasse|
|`moduleLog(int $jobId, string $level, string $message, $extra = null)`|mixed/void|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`driver(array $ctx = [])`|private|
|`ensureTables(array $ctx = [])`|private|
|`scriptPath(string $path)`|private|

## Konstanten

_Keine dokumentationsrelevanten Konstanten._

## Verwendung im Framework

`Srv` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `Srv` aufrufen.
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
