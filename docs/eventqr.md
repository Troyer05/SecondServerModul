# EventQR API – Plugin-Dokumentation
## Zweck
`EqrAPI` ist die EventQR-API-Bindung. Sie kapselt Events, Tickets, Zahlungen, Checkins, Produkte, Warenkörbe und Statistik-Endpunkte.
## Datei und Einbindung
- Klasse: `EqrAPI`
- Datei: `assets/php/inc/gbdb_framework/plugins/eventqr.php`
- Wird normalerweise über `assets/php/inc/gbdb_framework/gbdb.php` oder über `assets/php/inc/.config/_config.inc.php` geladen.

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
| `setUrl(string $url)` | `void` | EventQR API Bindung Doku URL: class EqrAPI { private static string $api_url = ""; private static string $auth = ""; private static int $timeout = 15; private static bool $return_raw = false; Setzt die API URL manuell. |
| `setAuth(string $auth)` | `void` | Setzt den API Auth Token manuell. |
| `setTimeout(int $seconds)` | `void` | Setzt das Request Timeout. |
| `returnRaw(bool $state)` | `void` | Schaltet Rohantworten an oder aus. |
| `auth()` | `string` | Gibt den Standard Auth Token zurück. |
| `url()` | `string` | Gibt die API URL zurück. |
| `request(string $do, array $body = [], array $data = [])` | `array` | Führt einen API Request aus. |
| `data(string $do, array $body = [], array $data = [])` | `mixed` | Gibt nur data zurück, wenn der Request erfolgreich war. |
| `ping()` | `array` | Prüft die Verbindung. |
| `docs()` | `array` | Holt die API Dokumentation. |
| `settings()` | `array` | Holt globale Einstellungen. |
| `updateSettings(array $data)` | `array` | Speichert globale Einstellungen. |
| `events()` | `array` | Holt alle Events. |
| `event(string $eid)` | `array` | Holt ein Event. |
| `createEvent(array $data)` | `array` | Erstellt ein Event. |
| `updateEvent(string $eid, array $data)` | `array` | Speichert ein Event. |
| `deleteEvent(string $eid)` | `array` | Löscht ein Event. |
| `eventSettings(string $eid)` | `array` | Holt Event Einstellungen. |
| `updateEventSettings(string $eid, array $data)` | `array` | Speichert Event Einstellungen. |
| `refreshMeta(string $eid)` | `array` | Berechnet Event Meta Daten neu. |
| `tickets(string $eid)` | `array` | Holt alle Tickets. |
| `ticket(string $eid, string $tid)` | `array` | Holt ein Ticket. |
| `createTicket(string $eid, array $data)` | `array` | Erstellt ein Ticket. |
| `updateTicket(string $eid, string $tid, array $data)` | `array` | Speichert ein Ticket. |
| `deleteTicket(string $eid, string $tid)` | `array` | Löscht ein Ticket. |
| `setTicketStatus(string $eid, string $tid, int|string $status)` | `array` | Setzt den Ticketstatus. |
| `scanTicket(string $eid, string $tid, string $scanner = "framework")` | `array` | Scannt ein Ticket. |
| `ticketUrl(string $eid, string $tid)` | `array` | Holt die Ticket URL. |
| `ticketPaid(string $eid, string $tid)` | `array` | Prüft ob ein Ticket bezahlt ist. |
| `addWallet(string $eid, string $tid, float|int|string $betrag, int|string $paymentStatus = 1)` | `array` | Lädt ein Ticket Wallet auf. |
| `ticketTypes(string $eid)` | `array` | Holt Tickettypen. |
| `ticketStates(string $eid)` | `array` | Holt Ticketstatus. |
| `paymentTypes(string $eid)` | `array` | Holt Paymenttypen. |
| `paymentStates(string $eid)` | `array` | Holt Paymentstatus. |
| `payments(string $eid)` | `array` | Holt alle Payments. |
| `payment(string $eid, string $pid)` | `array` | Holt ein Payment. |
| `createPayment(string $eid, array $data)` | `array` | Erstellt ein Payment. |
| `markPaymentPaid(string $eid, string $pid, string $orderId = "")` | `array` | Markiert ein Payment als bezahlt. |
| `products(string $eid)` | `array` | Holt alle Produkte. |
| `product(string $eid, string $bid)` | `array` | Holt ein Produkt. |
| `createProduct(string $eid, array $data)` | `array` | Erstellt ein Produkt. |
| `updateProduct(string $eid, string $bid, array $data)` | `array` | Speichert ein Produkt. |
| `deleteProduct(string $eid, string $bid)` | `array` | Löscht ein Produkt. |
| `sellProduct(string $eid, string $tid, string $bid, int $menge = 1)` | `array` | Verkauft ein Produkt über das Ticket Wallet. |
| `carts(string $eid)` | `array` | Holt alle Warenkörbe. |
| `cartItems(string $eid)` | `array` | Holt alle Warenkorb-Items. |
| `createCart(string $eid, string $tid)` | `array` | Erstellt einen Warenkorb. |
| `addCartItem(string $eid, string $cid, string $bid, int $menge = 1)` | `array` | Fügt einem Warenkorb einen Artikel hinzu. |
| `cartSum(string $eid, string $cid)` | `array` | Holt die Warenkorb-Summe. |
| `checkoutCart(string $eid, string $cid)` | `array` | Schließt einen Warenkorb ab. |
| `checkins(string $eid)` | `array` | Holt Checkins. |
| `stats(string $eid)` | `array` | Holt die komplette Event Übersicht. |
| `paymentStats(string $eid)` | `array` | Holt Payment Statistiken. |
| `checkinStats(string $eid)` | `array` | Holt Checkin Statistiken. |
| `capacityStats(string $eid)` | `array` | Holt Kapazitätsdaten. |
| `productStats(string $eid, int $limit = 5)` | `array` | Holt Produkt Statistiken. |
| `cartStats(string $eid)` | `array` | Holt Warenkorb Statistiken. |

## Beispiele
```php
EqrAPI::setUrl('https://eventqr.example/api.php');
EqrAPI::setAuth('secret');

$events = EqrAPI::events();
$stats = EqrAPI::stats('event_001');
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `private static body(string $do, array $body = [], array $data = []) : array` – Baut den Request Body.

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
