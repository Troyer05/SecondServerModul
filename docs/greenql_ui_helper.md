# GreenQLUIv2Helper

## Zweck

Helper der GreenQL UI v2 für Login, CSRF, Rollen, Rechte, Systeminstanz und sichere Script-Ausführung.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `GreenQLUIv2Helper` zur Verfügung.

## Typisches Beispiel

```php
GreenQLUIv2Helper::boot();
if (GreenQLUIv2Helper::loggedIn()) {
    $result = GreenQLUIv2Helper::runScript($script, "kunde_a", []);
}
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`boot()`|void|öffentliche Methode der Klasse|
|`csrf()`|string|öffentliche Methode der Klasse|
|`checkCsrf(string $token)`|bool|öffentliche Methode der Klasse|
|`systemInstance()`|string|öffentliche Methode der Klasse|
|`hasUsers()`|bool|öffentliche Methode der Klasse|
|`createUser(string $username, string $password, string $role = "admin", string $instances = "*", string $bases = "*")`|bool|öffentliche Methode der Klasse|
|`updateUser(int $id, array $data)`|bool|öffentliche Methode der Klasse|
|`deleteUser(int $id)`|bool|öffentliche Methode der Klasse|
|`login(string $username, string $password)`|bool|öffentliche Methode der Klasse|
|`logout()`|void|öffentliche Methode der Klasse|
|`user()`|array|öffentliche Methode der Klasse|
|`freshUser()`|array|öffentliche Methode der Klasse|
|`loggedIn()`|bool|öffentliche Methode der Klasse|
|`users()`|array|öffentliche Methode der Klasse|
|`normalizeRole(string $role)`|string|öffentliche Methode der Klasse|
|`isAdmin()`|bool|öffentliche Methode der Klasse|
|`canWrite()`|bool|öffentliche Methode der Klasse|
|`canStructure()`|bool|öffentliche Methode der Klasse|
|`clean(string $value)`|string|öffentliche Methode der Klasse|
|`reservedInstance(string $instance)`|bool|öffentliche Methode der Klasse|
|`reservedName(string $name)`|bool|öffentliche Methode der Klasse|
|`canAccessInstance(string $instance, ?array $user = null)`|bool|öffentliche Methode der Klasse|
|`canAccessDb(string $instance, string $db, ?array $user = null)`|bool|öffentliche Methode der Klasse|
|`instances()`|array|öffentliche Methode der Klasse|
|`databases(string $instance)`|array|öffentliche Methode der Klasse|
|`tables(string $instance, string $database)`|array|öffentliche Methode der Klasse|
|`parseParams(string $raw)`|array|öffentliche Methode der Klasse|
|`scriptAllowed(string $script, string $instance)`|array|öffentliche Methode der Klasse|
|`readScriptPath(string $path)`|array|öffentliche Methode der Klasse|
|`runScript(string $script, string $instance, array $params = [])`|array|öffentliche Methode der Klasse|
|`errorResult(string $message)`|array|öffentliche Methode der Klasse|
|`e(mixed $value)`|string|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`inSystem(callable $fn)`|private|
|`ensureAuthStore()`|private|
|`publicUser(array $user)`|private|
|`normalizeAccessValue(string $value)`|private|
|`reservedSlug(string $value)`|private|
|`accessValue(string $value)`|private|
|`guardParams(array $params)`|private|
|`containsReserved(string $value)`|private|
|`instanceFromScript(string $script)`|private|
|`filterResult(array $result)`|private|
|`filterSystemRows(array $rows)`|private|

## Konstanten

|Sichtbarkeit|Konstante|
|---|---|
|private|`SYSTEM_INSTANCE`|
|private|`SYSTEM_DB`|
|private|`USERS_TABLE`|

## Verwendung im Framework

`GreenQLUIv2Helper` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `GreenQLUIv2Helper` aufrufen.
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
