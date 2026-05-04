# DatabaseBridge

## Zweck

Abstraktionsschicht zwischen GBDBv2 und SQL, damit höhere Ebenen nicht direkt den Treiber kennen müssen.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `DatabaseBridge` zur Verfügung.

## Typisches Beispiel

```php
DatabaseBridge::setDriver("GBDBv2");
DatabaseBridge::setInstance("kunde_a");
DatabaseBridge::insert("main", "users", ["uid" => "u1"]);
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`setDriver(string $driver)`|void|öffentliche Methode der Klasse|
|`setInstance(string $instance)`|void|öffentliche Methode der Klasse|
|`get(string $db, string $table, bool $filter = false, string $where = "", mixed $is = "")`|mixed|öffentliche Methode der Klasse|
|`insert(string $db, string $table, array $data)`|mixed|öffentliche Methode der Klasse|
|`delete(string $db, string $table, string $where, mixed $is)`|mixed|öffentliche Methode der Klasse|
|`update(string $db, string $table, string $where, mixed $is, array $data)`|mixed|öffentliche Methode der Klasse|
|`createDatabase(string $name)`|bool|öffentliche Methode der Klasse|
|`deleteDatabase(string $name)`|bool|öffentliche Methode der Klasse|
|`createTable(string $db, string $table, array $columns)`|bool|öffentliche Methode der Klasse|
|`deleteTable(string $db, string $table)`|bool|öffentliche Methode der Klasse|
|`addColumn(string $db, string $table, string $column, mixed $default = "")`|bool|öffentliche Methode der Klasse|
|`createIndex(string $db, string $table, string $column)`|bool|öffentliche Methode der Klasse|
|`begin()`|bool|öffentliche Methode der Klasse|
|`commit()`|bool|öffentliche Methode der Klasse|
|`rollback()`|bool|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`driver()`|private|
|`isSQL()`|private|
|`isGBDBv2()`|private|
|`ensureSQL()`|private|
|`ensureGBDBv2()`|private|

## Konstanten

_Keine dokumentationsrelevanten Konstanten._

## Verwendung im Framework

`DatabaseBridge` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `DatabaseBridge` aufrufen.
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
