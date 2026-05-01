# Auth
## Zweck
`Auth` ist das zentrale Benutzer-, Login-, JWT-, E-Mail-Verifikations- und 2FA-Modul. Es verwaltet Userdaten in der konfigurierten Auth-GBDB, erzeugt Tokens, prüft Sessions/Cookies und stellt zusätzlich Remote-Login-Funktionen für das SecondServerModul bereit.
## Datei und Einbindung
- Klasse: `Auth`
- Datei: `assets/php/inc/gbdb_framework/core/auth.php`
- Wird normalerweise über `assets/php/inc/gbdb_framework/gbdb.php` oder über `assets/php/inc/.config/_config.inc.php` geladen.

## Wichtige Konfiguration
Benötigt `Vars::AUTH()` mit `main_db`, Token-Laufzeiten, Cookie-Name, Root-User und optional 2FA/Verify-Vorgaben. Die Auth-Tabellen werden bei `Auth::init()` erstellt.

## Konstanten
| Konstante | Zweck / Wert |
|---|---|
| `USER_TABLE_SCHEMA` | `["uid", "username", "email", "password", "active", "rolle", "datum", "tfa"]` |
| `JWT_SCHEMA` | `["uid", "token", "exp"]` |
| `MAIL_VERIFY_SCHEMA` | `["uid", "token", "exp"]` |
| `PWF_SCHEMA` | `["uid", "token", "exp"]` |
| `TFA_SCHEMA` | `["uid", "code", "exp"]` |
| `USER_META_SCHEMA` | `["uid", "vorname", "nachname", "telefon", "mobil", "adresse", "gender", "bio", "image"]` |

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
| `init()` | `void` | Initialisiert die Klasse und prüft lokale Authentifizierung. |
| `initRemote()` | `array` | Initialisiert Auth ohne lokale Weiterleitung für Remote-Nutzung. |
| `hashPass(string $pass)` | `string` | Erzeugt den Framework-Passwort-Hash. |
| `get(string $table, string $where = "", string $is = "")` | `array` | Liest Daten aus der Auth-Datenbank. |
| `delete(string $table, string $where, string $is)` | `void` | Löscht Daten aus der Auth-Datenbank. |
| `logout()` | `void` | Beendet die aktuelle lokale Anmeldung. |
| `login(string $username_or_email, string $plain_text_password)` | `string` | Prüft Login-Daten und startet die lokale Anmeldung. |
| `loginRemote(string $username_or_email, string $plain_text_password)` | `array` | Prüft Login-Daten für Remote/API/Srv-Nutzung. |
| `login2Fa(string $code)` | `string` | Schließt einen lokalen 2FA-Login ab. |
| `login2FaRemote(string $uid, string $code)` | `array` | Schließt einen Remote/API/Srv-2FA-Login ab. |
| `authByToken(string $jwt)` | `array` | Prüft einen JWT. |
| `me()` | `array` | Gibt den aktuell eingeloggten Benutzer zurück. |
| `check()` | `bool` | Prüft, ob lokal ein Benutzer eingeloggt ist. |
| `user(string $uid)` | `array` | Holt einen Benutzer anhand der UID. |
| `newUser(array $user_data, array $user_meta, bool $is_this_register = false)` | `string` | Legt einen neuen Benutzer an. |
| `editUser(string $uid, array $user_data, array $user_meta = [])` | `string` | Bearbeitet einen Benutzer. |
| `verifyEmail(string $token)` | `bool` | Verifiziert eine E-Mail-Adresse. |
| `verify2FaCode(string $code)` | `bool` | Prüft einen 2FA-Code ohne Login-Abschluss. |

## Beispiele
```php
include 'assets/php/inc/.config/_config.inc.php';

Auth::init();

$status = Auth::login('admin', 'admin');
if (Auth::check()) {
    $me = Auth::me();
}

// Remote über SecondServer:
$remote = Auth::loginRemote('admin', 'admin');
```

## Fehlerquellen und Debugging
- Prüfe zuerst, ob `_config.inc.php` korrekt geladen wurde.
- Bei leeren Rückgaben immer zwischen `false`, leerem Array und nicht vorhandenem Datensatz unterscheiden.
- Bei Datei- oder GBDB-Zugriffen Schreibrechte des Webservers prüfen.
- Bei Remote-Aufrufen Netzwerk, URL, Auth-Key und JSON-Antwort kontrollieren.
- In Entwicklung `Vars::__DEV__()` bzw. eigene Logs nutzen, aber produktive Secrets nie ausgeben.

## Interne Methoden
Diese Methoden erklären die interne Struktur. Sie sind nicht als öffentliche API gedacht:

- `private static db() : string` – Liefert den Namen der Auth-Datenbank.
- `private static jwtCookie() : string` – Liefert den Namen des JWT-Cookies.
- `private static session() : void` – Startet eine Session, falls noch keine aktiv ist.
- `private static insert(string $table, array $obj) : void` – Fügt neue Daten ein.
- `private static edit(string $table, string $where, string $is, array $obj) : void` – Bearbeitet bestehende Daten.
- `private static redirect(string $file) : void` – Leitet weiter, falls ein Ziel angegeben wurde.
- `private static expired(string $exp) : bool` – Prüft, ob ein Ablaufzeitpunkt abgelaufen ist.
- `private static expires() : string` – Erzeugt einen Ablaufzeitpunkt für normale Tokens.
- `private static tfaExpires() : string` – Erzeugt einen Ablaufzeitpunkt für 2FA-Codes.
- `private static boolValue(mixed $value) : bool` – Wandelt typische Werte in bool um.
- `private static isHash(string $pass) : bool` – Prüft, ob ein Wert wie ein gespeicherter Hash aussieht.
- `private static passwordValue(string $pass) : string` – Normalisiert ein Passwort für Speicherung.
- `private static firstRow(array $data) : array` – Normalisiert ein GBDB-Ergebnis auf den ersten Datensatz.
- `private static readEmailHtmlFile(string $path_with_file) : string` – Liest eine HTML-Mail-Datei.
- `private static getUserFull(string $uid) : array` – Holt Benutzer- und Meta-Daten zusammen.
- `private static replaceMailVars(string $content, array $user, array $extra = []) : string` – Ersetzt Variablen in Mail-Vorlagen.
- `private static mail(array $mail) : void` – Versendet eine Mail über die Framework-Mailfunktion.
- `private static sendVerifyMail(string $uid) : void` – Versendet eine Verifizierungs-Mail.
- `private static send2FaMail(string $uid) : void` – Versendet eine 2FA-Mail.
- `private static new2FaCode() : string` – Erzeugt einen eindeutigen 2FA-Code.
- `private static newVerifyToken() : string` – Erzeugt einen eindeutigen Mail-Verifizierungstoken.
- `private static newJWT(string $uid) : string` – Erzeugt einen neuen JWT.
- `private static newUID() : string` – Erzeugt eine eindeutige Benutzer-ID.
- `private static isNoLoginFile() : bool` – Prüft, ob die aktuelle Datei ohne Login erreichbar ist.
- `private static auth() : array` – Prüft die aktuelle lokale Authentifizierung.
- `private static doubleUser(string $username, string $email, string $uid = "") : string` – Prüft auf doppelte Benutzer.
- `private static userObj(string $uid, array $user_data, bool $new = false) : array` – Baut ein Benutzer-Objekt.
- `private static metaObj(string $uid, array $user_meta, bool $new = false) : array` – Baut ein Meta-Objekt.
- `private static initTables() : void` – Legt benötigte Tabellen an.
- `private static loginCore(string $username_or_email, string $plain_text_password, bool $remote = false) : array` – Prüft Login-Daten zentral für lokalen und remote Login.

## Best Practices
- Öffentliche Methoden bevorzugen und interne Dateipfade nicht hart im Anwendungscode duplizieren.
- Rückgaben immer validieren, bevor sie in HTML, API-Antworten oder weitere DB-Operationen fließen.
- Für neue Features erst Schema/Tabellen sauber anlegen und danach Daten schreiben.
- Für produktive Systeme Backups, Schreibrechte und Authentifizierung vor dem Rollout testen.

## Zusatzhinweise
Passwörter werden mit `Auth::hashPass()` gespeichert. Bestehende Projekte sollten nicht verschiedene Hash-Funktionen mischen. Für produktive Systeme wären PHP `password_hash()`/`password_verify()` langfristig sicherer, falls Kompatibilität angepasst werden darf.

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
