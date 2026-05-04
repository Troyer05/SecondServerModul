# Tools

## Zweck

allgemeine kleine Hilfsfunktionen.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `Tools` zur Verfügung.

## Typisches Beispiel

```php
// Beispiel für Tools
// Klasse nach Include der _config.inc.php direkt nutzbar.
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`generatePassword(int $length)`|string|öffentliche Methode der Klasse|
|`testPasswordStrength(string $password)`|string|öffentliche Methode der Klasse|
|`getDomainInfo(string $domain)`|mixed|öffentliche Methode der Klasse|
|`generateId()`|int|öffentliche Methode der Klasse|
|`generateToken(string $delimiter = "-", int $many = 1, int $fragments = 4)`|array|öffentliche Methode der Klasse|
|`generateTokenExt(string $delimiter = "-", int $many = 1, int $fragments = 4)`|array|öffentliche Methode der Klasse|
|`getIpCountry(string $ip)`|string|öffentliche Methode der Klasse|
|`ping4(string $ip)`|string|öffentliche Methode der Klasse|
|`ping6(string $ip)`|string|öffentliche Methode der Klasse|
|`qr(string $value, int $width, int $height)`|string|öffentliche Methode der Klasse|
|`bar(string $value, int $width, int $height = 175)`|string|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`generateTokenInternal(string $delimiter, int $many, int $fragments)`|private|
|`buildToken(string $delimiter, int $fragments)`|private|
|`getFrameworkTempFile(string $filename)`|private|
|`ensureDir(string $dir)`|private|

## Konstanten

_Keine dokumentationsrelevanten Konstanten._

## Verwendung im Framework

`Tools` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `Tools` aufrufen.
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
