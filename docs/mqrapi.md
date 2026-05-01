# MqrApi
## Zweck
`MqrApi` ist die MuseumQR-API-Bindung für Objekte, Feedback, Einstellungen, Sprachen, Touren und API-Keys.
## Datei und Einbindung
- Klasse: `MqrApi`
- Datei: `assets/php/inc/gbdb_framework/plugins/museumqr.php`
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
| `getFeedback(string $item_id = "")` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `getObjects()` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `getObject(string $oid)` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `getSettings()` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `getLangs()` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `getTours()` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `newApiKey(string $permission = "readwrite")` | `array` | Kapselt die Fachlogik für `newApiKey()` innerhalb dieser Klasse. |
| `saveSettings(string $theme, string $name, string $intro)` | `array` | Kapselt die Fachlogik für `saveSettings()` innerhalb dieser Klasse. |
| `newLang(string $lid, string $name)` | `array` | Kapselt die Fachlogik für `newLang()` innerhalb dieser Klasse. |
| `newTour(string $name)` | `array` | Kapselt die Fachlogik für `newTour()` innerhalb dieser Klasse. |

## Beispiele
```php
include 'assets/php/inc/.config/_config.inc.php';

// Beispielaufruf; Parameter bitte passend zum Projekt einsetzen.
$result = MqrApi::getFeedback("");
var_dump($result);
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `private static base() : array` – MuseumQR API Anbindung API Dokumentation: https://museumqr.de/api_doc.html class MqrApi { Verarbeitet die Funktion base.
- `private static fetch(array $data) : array` – Kapselt die Fachlogik für `fetch()` innerhalb dieser Klasse.

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
