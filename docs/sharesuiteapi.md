# ShareSuiteAPI

## Zweck

ShareSuite API-Client für Tabellen, Kalender, Bibliothek, Blogs und Tickets.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `ShareSuiteAPI` zur Verfügung.

## Typisches Beispiel

```php
// Beispiel für ShareSuiteAPI
// Klasse nach Include der _config.inc.php direkt nutzbar.
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`getTable(string $tid, string $id = "")`|array|öffentliche Methode der Klasse|
|`getTableSettings(string $tid)`|array|öffentliche Methode der Klasse|
|`getTableIndex()`|array|öffentliche Methode der Klasse|
|`getCalendar(string $kid, string $id = "")`|array|öffentliche Methode der Klasse|
|`getBib(string $bid = "")`|array|öffentliche Methode der Klasse|
|`getBlogs(string $bid = "")`|array|öffentliche Methode der Klasse|
|`getTickets(string $tid = "")`|array|öffentliche Methode der Klasse|
|`newTableEntry(string $tid, array $data)`|array|öffentliche Methode der Klasse|
|`newCalendarEntry(string $kid, string $titel, string $von, string $bis, string $text = "")`|array|öffentliche Methode der Klasse|
|`newBlog(string $user, string $user_auth, string $title, string $text)`|array|öffentliche Methode der Klasse|
|`editTableEntry(string $tid, string $id, array $data)`|array|öffentliche Methode der Klasse|
|`editCalendarEntry(string $kid, string $id, string $titel, string $von, string $bis, string $text = "")`|array|öffentliche Methode der Klasse|
|`editBlog(string $id, string $user, string $user_auth, string $title, string $text)`|array|öffentliche Methode der Klasse|
|`editBib(string $id, string $name)`|array|öffentliche Methode der Klasse|
|`editTicket(string $id, string $status, string $reply = "")`|array|öffentliche Methode der Klasse|
|`deleteTableEntry(string $tid, string $id)`|array|öffentliche Methode der Klasse|
|`deleteCalendarEntry(string $kid, string $id)`|array|öffentliche Methode der Klasse|
|`deleteBlog(string $id)`|array|öffentliche Methode der Klasse|
|`deleteBib(string $id)`|array|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`base()`|private|
|`fetch(string $method, array $data)`|private|

## Konstanten

_Keine dokumentationsrelevanten Konstanten._

## Verwendung im Framework

`ShareSuiteAPI` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `ShareSuiteAPI` aufrufen.
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
