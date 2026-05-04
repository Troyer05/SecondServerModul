# Auth

## Zweck

Authentifizierung, JWT-Cookies, Login, 2FA, Mail-Verifizierung und Userverwaltung über GBDB.

## Hintergrund und Intention

Die Klasse ist bewusst als statische Utility-/Serviceklasse aufgebaut. Dadurch kann sie nach dem zentralen Framework-Include ohne Dependency-Injection oder Objektinitialisierung verwendet werden. Das passt zum Coding-Stil dieses Frameworks: kurze Aufrufe, klare Dateistruktur, einfache Erweiterbarkeit und möglichst wenig Boilerplate.

## Einbindung

```php
require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
```

Danach steht `Auth` zur Verfügung.

## Typisches Beispiel

```php
Auth::init();

if (Auth::check()) {
    $me = Auth::me();
}

$msg = Auth::login("admin", "admin");
```

## Öffentliche Methoden

|Methode|Rückgabe|Beschreibung|
|---|---|---|
|`init()`|void|öffentliche Methode der Klasse|
|`initRemote()`|array|öffentliche Methode der Klasse|
|`hashPass(string $pass)`|string|öffentliche Methode der Klasse|
|`get(string $table, string $where = "", string $is = "")`|array|öffentliche Methode der Klasse|
|`delete(string $table, string $where, string $is)`|void|öffentliche Methode der Klasse|
|`logout()`|void|öffentliche Methode der Klasse|
|`login(string $username_or_email, string $plain_text_password)`|string|öffentliche Methode der Klasse|
|`loginRemote(string $username_or_email, string $plain_text_password)`|array|öffentliche Methode der Klasse|
|`login2Fa(string $code)`|string|öffentliche Methode der Klasse|
|`login2FaRemote(string $uid, string $code)`|array|öffentliche Methode der Klasse|
|`authByToken(string $jwt)`|array|öffentliche Methode der Klasse|
|`me()`|array|öffentliche Methode der Klasse|
|`check()`|bool|öffentliche Methode der Klasse|
|`user(string $uid)`|array|öffentliche Methode der Klasse|
|`newUser(array $user_data, array $user_meta, bool $is_this_register = false)`|string|öffentliche Methode der Klasse|
|`editUser(string $uid, array $user_data, array $user_meta = [])`|string|öffentliche Methode der Klasse|
|`verifyEmail(string $token)`|bool|öffentliche Methode der Klasse|
|`verify2FaCode(string $code)`|bool|öffentliche Methode der Klasse|

## Interne Hilfsmethoden

|Hilfsmethode|Sichtbarkeit|
|---|---|
|`db()`|private|
|`jwtCookie()`|private|
|`session()`|private|
|`insert(string $table, array $obj)`|private|
|`edit(string $table, string $where, string $is, array $obj)`|private|
|`redirect(string $file)`|private|
|`expired(string $exp)`|private|
|`expires()`|private|
|`tfaExpires()`|private|
|`boolValue(mixed $value)`|private|
|`isHash(string $pass)`|private|
|`passwordValue(string $pass)`|private|
|`firstRow(array $data)`|private|
|`readEmailHtmlFile(string $path_with_file)`|private|
|`getUserFull(string $uid)`|private|
|`replaceMailVars(string $content, array $user, array $extra = [])`|private|
|`mail(array $mail)`|private|
|`sendVerifyMail(string $uid)`|private|
|`send2FaMail(string $uid)`|private|
|`new2FaCode()`|private|
|`newVerifyToken()`|private|
|`newJWT(string $uid)`|private|
|`newUID()`|private|
|`isNoLoginFile()`|private|
|`auth()`|private|
|`doubleUser(string $username, string $email, string $uid = "")`|private|
|`userObj(string $uid, array $user_data, bool $new = false)`|private|
|`metaObj(string $uid, array $user_meta, bool $new = false)`|private|
|`initTables()`|private|
|`loginCore(string $username_or_email, string $plain_text_password, bool $remote = false)`|private|

## Konstanten

|Sichtbarkeit|Konstante|
|---|---|
|private|`USER_TABLE_SCHEMA`|
|private|`JWT_SCHEMA`|
|private|`MAIL_VERIFY_SCHEMA`|
|private|`PWF_SCHEMA`|
|private|`TFA_SCHEMA`|
|private|`USER_META_SCHEMA`|

## Verwendung im Framework

`Auth` wird über den zentralen Loader eingebunden und ist damit projektweit verfügbar. Je nach Klasse arbeitet sie mit GBDB, Konfiguration, Dateisystem, HTTP, Sessions, Cookies oder externen APIs zusammen. Die konkrete Verantwortung bleibt aber innerhalb der Klasse gekapselt, damit Seiten und Plugins nur kurze, lesbare Aufrufe benötigen.

## Typischer Ablauf

1. `_config.inc.php` einbinden.
2. Eingaben vorbereiten und validieren.
3. passende öffentliche Methode von `Auth` aufrufen.
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
