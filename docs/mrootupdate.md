# mRootUpdate
## Zweck
`mRootUpdate` prüft Updates, Changelog, Download-URL, Backups, ZIP-Extraktion, Release-Kopien und Schema-Migrationen.
## Datei und Einbindung
- Klasse: `mRootUpdate`
- Datei: `assets/php/inc/gbdb_framework/plugins/mroot.php`
- Wird normalerweise über `assets/php/inc/gbdb_framework/gbdb.php` oder über `assets/php/inc/.config/_config.inc.php` geladen.

## Wichtige Konfiguration
Benötigt `Vars::mRoot_url()`, `Vars::mRoot_pid()`, `Vars::update_auth()` und eine sinnvolle lokale `Vars::app_version()`.

## Konstanten
| Konstante | Zweck / Wert |
|---|---|
| `STORE` | `"assets/php/inc/gbdb_framework/json/mRoot/license.json"` |
| `UPDATE_DIR` | `"update"` |
| `TMP_DIR` | `"update/tmp"` |
| `BACKUP_DIR` | `"update/backups"` |
| `CACHE_FILE` | `"update/check_cache.json"` |
| `CACHE_TTL` | `1800` |
| `PRESERVE_PATHS` | `[ "assets/DB/GBDB", "assets/php/inc/.config/_config.inc.php", "update", "uploads" ]` |

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
| `testLicense()` | `void` | Kapselt die Fachlogik für `testLicense()` innerhalb dieser Klasse. |
| `setLicense(string $key, string $kid)` | `void` | Setzt einen Wert oder Zustand in der jeweiligen Komponente. |
| `checkLicense(string $key, string $kid)` | `array` | Prüft einen Zustand und gibt ein boolesches oder strukturiertes Ergebnis zurück. |
| `check(bool $force = false)` | `array` | Prüft einen Zustand und gibt ein boolesches oder strukturiertes Ergebnis zurück. |
| `needUpdate(bool $force = false)` | `bool` | Kapselt die Fachlogik für `needUpdate()` innerhalb dieser Klasse. |
| `latestVersion(bool $force = false)` | `string` | Kapselt die Fachlogik für `latestVersion()` innerhalb dieser Klasse. |
| `changelog(bool $force = false)` | `string` | Kapselt die Fachlogik für `changelog()` innerhalb dieser Klasse. |
| `updateUrl(bool $force = false)` | `string` | Kapselt die Fachlogik für `updateUrl()` innerhalb dieser Klasse. |
| `app_version()` | `\n` | Kapselt die Fachlogik für `app_version()` innerhalb dieser Klasse. |
| `update(bool $force = true)` | `array` | Kapselt die Fachlogik für `update()` innerhalb dieser Klasse. |

## Beispiele
```php
$check = mRootUpdate::check(true);
if (mRootUpdate::needUpdate()) {
    // in der UI anzeigen, nicht automatisch erzwingen
    $url = mRootUpdate::updateUrl();
}
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `private static fetch(array $data) : array` – mRoot Anbindung API Doku: class mRootLicense { private const STORE = "assets/php/inc/gbdb_framework/json/mRoot/license.json"; Verarbeitet die Funktion fetch.
- `private static storePath() : string` – Kapselt die Fachlogik für `storePath()` innerhalb dieser Klasse.
- `private static ensureStore() : void` – Kapselt die Fachlogik für `ensureStore()` innerhalb dieser Klasse.
- `private static licenseData() : array` – Kapselt die Fachlogik für `licenseData()` innerhalb dieser Klasse.
- `private static saveLicense(array $data) : bool` – Kapselt die Fachlogik für `saveLicense()` innerhalb dieser Klasse.
- `private static currentUrl() : string` – Kapselt die Fachlogik für `currentUrl()` innerhalb dieser Klasse.
- `private static validApiResponse(array $resp) : bool` – Kapselt die Fachlogik für `validApiResponse()` innerhalb dieser Klasse.
- `private static root() : string` – Kapselt die Fachlogik für `root()` innerhalb dieser Klasse.
- `private static server() : string` – Kapselt die Fachlogik für `server()` innerhalb dieser Klasse.
- `private static auth() : string` – Verarbeitet Auth-Aktionen.
- `private static normalizeVersion(string $version) : string` – Kapselt die Fachlogik für `normalizeVersion()` innerhalb dieser Klasse.
- `private static newer(string $remote, string $local) : bool` – Kapselt die Fachlogik für `newer()` innerhalb dieser Klasse.
- `private static value(array $data, array $paths, mixed $default = "") : mixed` – Kapselt die Fachlogik für `value()` innerhalb dieser Klasse.
- `private static cleanResponse(array $resp) : array` – Kapselt die Fachlogik für `cleanResponse()` innerhalb dieser Klasse.
- `private static fetchRemote() : array` – Kapselt die Fachlogik für `fetchRemote()` innerhalb dieser Klasse.
- `private static cachePath() : string` – Kapselt die Fachlogik für `cachePath()` innerhalb dieser Klasse.
- `private static readCache(int $ttl = self::CACHE_TTL) : array` – Liest Inhalt aus Datei, Request oder Speicher.
- `private static writeCache(array $resp) : void` – Schreibt Inhalt in Datei, Datenbank oder Speicher.
- `private static fetch(bool $force = false) : array` – Kapselt die Fachlogik für `fetch()` innerhalb dieser Klasse.
- `private static ensureDir(string $dir) : void` – Kapselt die Fachlogik für `ensureDir()` innerhalb dieser Klasse.
- `private static rrmdir(string $dir) : void` – Kapselt die Fachlogik für `rrmdir()` innerhalb dieser Klasse.
- `private static normalizePath(string $path) : string` – Kapselt die Fachlogik für `normalizePath()` innerhalb dieser Klasse.
- `private static isPreserved(string $relative) : bool` – Kapselt die Fachlogik für `isPreserved()` innerhalb dieser Klasse.
- `private static isIgnoredForBackup(string $relative) : bool` – Kapselt die Fachlogik für `isIgnoredForBackup()` innerhalb dieser Klasse.
- `private static download(string $url, string $target) : bool` – Kapselt die Fachlogik für `download()` innerhalb dieser Klasse.
- `private static extractZip(string $zipFile, string $targetDir) : bool` – Kapselt die Fachlogik für `extractZip()` innerhalb dieser Klasse.
- `private static backup() : string|false` – Kapselt die Fachlogik für `backup()` innerhalb dieser Klasse.
- `private static detectReleaseRoot(string $extractDir) : string` – Kapselt die Fachlogik für `detectReleaseRoot()` innerhalb dieser Klasse.
- `private static copyRelease(string $src, string $dst, array &$stats = []) : bool` – Kapselt die Fachlogik für `copyRelease()` innerhalb dieser Klasse.
- `private static updateLocalVersion(string $version) : bool` – Kapselt die Fachlogik für `updateLocalVersion()` innerhalb dieser Klasse.
- … weitere 3 interne Methoden.

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
