# EqrAPI

## Zweck

EventQR API-Bindung für externe EventQR-Kommunikation.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `EqrAPI` zur Verfügung.

## Typisches Beispiel

```php
// Beispiel für EqrAPI
// Klasse nach Include der _config.inc.php direkt nutzbar.
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`setUrl(string $url)`|void|öffentliche Methode der Klasse|
|`setAuth(string $auth)`|void|öffentliche Methode der Klasse|
|`setTimeout(int $seconds)`|void|öffentliche Methode der Klasse|
|`returnRaw(bool $state)`|void|öffentliche Methode der Klasse|
|`auth()`|string|öffentliche Methode der Klasse|
|`url()`|string|öffentliche Methode der Klasse|
|`request(string $do, array $body = [], array $data = [])`|array|öffentliche Methode der Klasse|
|`data(string $do, array $body = [], array $data = [])`|mixed|öffentliche Methode der Klasse|
|`ping()`|array|öffentliche Methode der Klasse|
|`docs()`|array|öffentliche Methode der Klasse|
|`settings()`|array|öffentliche Methode der Klasse|
|`updateSettings(array $data)`|array|öffentliche Methode der Klasse|
|`events()`|array|öffentliche Methode der Klasse|
|`event(string $eid)`|array|öffentliche Methode der Klasse|
|`createEvent(array $data)`|array|öffentliche Methode der Klasse|
|`updateEvent(string $eid, array $data)`|array|öffentliche Methode der Klasse|
|`deleteEvent(string $eid)`|array|öffentliche Methode der Klasse|
|`eventSettings(string $eid)`|array|öffentliche Methode der Klasse|
|`updateEventSettings(string $eid, array $data)`|array|öffentliche Methode der Klasse|
|`refreshMeta(string $eid)`|array|öffentliche Methode der Klasse|
|`tickets(string $eid)`|array|öffentliche Methode der Klasse|
|`ticket(string $eid, string $tid)`|array|öffentliche Methode der Klasse|
|`createTicket(string $eid, array $data)`|array|öffentliche Methode der Klasse|
|`updateTicket(string $eid, string $tid, array $data)`|array|öffentliche Methode der Klasse|
|`deleteTicket(string $eid, string $tid)`|array|öffentliche Methode der Klasse|
|`setTicketStatus(string $eid, string $tid, int\|string $status)`|array|öffentliche Methode der Klasse|
|`scanTicket(string $eid, string $tid, string $scanner = "framework")`|array|öffentliche Methode der Klasse|
|`ticketUrl(string $eid, string $tid)`|array|öffentliche Methode der Klasse|
|`ticketPaid(string $eid, string $tid)`|array|öffentliche Methode der Klasse|
|`addWallet(string $eid, string $tid, float\|int\|string $betrag, int\|string $paymentStatus = 1)`|array|öffentliche Methode der Klasse|
|`ticketTypes(string $eid)`|array|öffentliche Methode der Klasse|
|`ticketStates(string $eid)`|array|öffentliche Methode der Klasse|
|`paymentTypes(string $eid)`|array|öffentliche Methode der Klasse|
|`paymentStates(string $eid)`|array|öffentliche Methode der Klasse|
|`payments(string $eid)`|array|öffentliche Methode der Klasse|
|`payment(string $eid, string $pid)`|array|öffentliche Methode der Klasse|
|`createPayment(string $eid, array $data)`|array|öffentliche Methode der Klasse|
|`markPaymentPaid(string $eid, string $pid, string $orderId = "")`|array|öffentliche Methode der Klasse|
|`products(string $eid)`|array|öffentliche Methode der Klasse|
|`product(string $eid, string $bid)`|array|öffentliche Methode der Klasse|
|`createProduct(string $eid, array $data)`|array|öffentliche Methode der Klasse|
|`updateProduct(string $eid, string $bid, array $data)`|array|öffentliche Methode der Klasse|
|`deleteProduct(string $eid, string $bid)`|array|öffentliche Methode der Klasse|
|`sellProduct(string $eid, string $tid, string $bid, int $menge = 1)`|array|öffentliche Methode der Klasse|
|`carts(string $eid)`|array|öffentliche Methode der Klasse|
|`cartItems(string $eid)`|array|öffentliche Methode der Klasse|
|`createCart(string $eid, string $tid)`|array|öffentliche Methode der Klasse|
|`addCartItem(string $eid, string $cid, string $bid, int $menge = 1)`|array|öffentliche Methode der Klasse|
|`cartSum(string $eid, string $cid)`|array|öffentliche Methode der Klasse|
|`checkoutCart(string $eid, string $cid)`|array|öffentliche Methode der Klasse|
|`checkins(string $eid)`|array|öffentliche Methode der Klasse|
|`stats(string $eid)`|array|öffentliche Methode der Klasse|
|`paymentStats(string $eid)`|array|öffentliche Methode der Klasse|
|`checkinStats(string $eid)`|array|öffentliche Methode der Klasse|
|`capacityStats(string $eid)`|array|öffentliche Methode der Klasse|
|`productStats(string $eid, int $limit = 5)`|array|öffentliche Methode der Klasse|
|`cartStats(string $eid)`|array|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`body(string $do, array $body = [], array $data = [])`|private|

## Konstanten

_Keine dokumentationsrelevanten Konstanten._

## Verwendung im Framework

`EqrAPI` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `EqrAPI` aufrufen.
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
