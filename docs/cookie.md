# Cookie

## Zweck

Wrapper für sichere Cookie-Erzeugung, Lesen, Löschen, Vergleich und Refresh.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `Cookie` zur Verfügung.

## Typisches Beispiel

```php
Cookie::set("theme", "dark");
$value = Cookie::get("theme");
Cookie::delete("theme");
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`set(string $name, string $value, int $expiration = self::DUR)`|void|öffentliche Methode der Klasse|
|`setSecure(string $name, string $value, int $expiration = self::DUR)`|void|öffentliche Methode der Klasse|
|`add(string $name, string $value)`|void|öffentliche Methode der Klasse|
|`get(string $name)`|mixed|öffentliche Methode der Klasse|
|`delete(string $name)`|void|öffentliche Methode der Klasse|
|`edit(string $name, string $value)`|void|öffentliche Methode der Klasse|
|`compare(string $name, string $value)`|bool|öffentliche Methode der Klasse|
|`refresh(int $thresholdSeconds = 3600)`|void|öffentliche Methode der Klasse|
|`init()`|void|öffentliche Methode der Klasse|
|`exists(string $name)`|bool|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`validateName(string $name)`|protected|
|`options(int $expiration, bool $secureOverride = null)`|protected|
|`send(string $name, string $value, int $expiration)`|protected|

## Konstanten

|Sichtbarkeit|Konstante|
|---|---|
|private|`DUR`|

## Verwendung im Framework

`Cookie` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `Cookie` aufrufen.
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
