# GreenQL
## Zweck
`GreenQL` ist die kleine Query-Sprache für GBDB. Sie ermöglicht strukturierte Operationen wie ROOT, GROW, SEED, SELECT, EDIT und DELETE in Scriptform.
## Datei und Einbindung
- Klasse: `GreenQL`
- Datei: `assets/php/inc/gbdb_framework/core/greenql_engine.php`
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
| `cleanName(string $name)` | `string` | Kapselt die Fachlogik für `cleanName()` innerhalb dieser Klasse. |
| `unquote(string $value)` | `mixed` | Kapselt die Fachlogik für `unquote()` innerhalb dieser Klasse. |
| `stripComments(string $script)` | `string` | Kapselt die Fachlogik für `stripComments()` innerhalb dieser Klasse. |
| `splitCommands(string $script)` | `array` | Kapselt die Fachlogik für `splitCommands()` innerhalb dieser Klasse. |
| `evaluateValue(string $value, array $vars = [], array $params = [])` | `mixed` | Kapselt die Fachlogik für `evaluateValue()` innerhalb dieser Klasse. |
| `resolveNameToken(string $token, array $vars = [])` | `string` | Kapselt die Fachlogik für `resolveNameToken()` innerhalb dieser Klasse. |
| `parseList(string $raw, array $vars = [])` | `array` | Kapselt die Fachlogik für `parseList()` innerhalb dieser Klasse. |
| `parseAssignments(string $raw, array $vars = [], array $params = [])` | `array` | Kapselt die Fachlogik für `parseAssignments()` innerhalb dieser Klasse. |
| `parseWhere(string $raw, array $vars = [], array $params = [])` | `?array` | Kapselt die Fachlogik für `parseWhere()` innerhalb dieser Klasse. |
| `rowMatch(array $row, ?array $where)` | `bool` | Kapselt die Fachlogik für `rowMatch()` innerhalb dieser Klasse. |
| `sortRows(array &$rows, ?string $field, string $dir = 'ASC')` | `void` | Kapselt die Fachlogik für `sortRows()` innerhalb dieser Klasse. |
| `getRows(string $db, string $table)` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `getTableKeys(string $db, string $table)` | `array` | Liest Daten aus der jeweiligen Quelle und gibt sie strukturiert zurück. |
| `selectRows(string $db, string $table, array $columns = ['*'], ?array $where = null, ?string $sortField = null, string $sortDir = 'ASC', ?int $limit = null)` | `array` | Kapselt die Fachlogik für `selectRows()` innerhalb dieser Klasse. |
| `stats(string $db)` | `array` | Kapselt die Fachlogik für `stats()` innerhalb dieser Klasse. |
| `command(string $command, array &$ctx = [], array &$vars = [], array $params = [])` | `array` | Kapselt die Fachlogik für `command()` innerhalb dieser Klasse. |
| `run(string $script, array $ctx = [], array $params = [])` | `array` | Führt ein Script, eine Query oder einen Job aus. |

## Beispiele
```php
$result = GBDB::query('
ROOT main;
GROW TABLE users (uid, username, email);
SEED users WITH uid="u001", username="markus", email="markus@example.test";
SELECT * FROM users WHERE uid="u001";
');
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

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
