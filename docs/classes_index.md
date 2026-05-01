# Klassenindex

Diese Datei listet die wichtigsten Framework-Klassen, ihre Dateien und ihren Zweck. Sie ist als Navigationshilfe für Entwickler gedacht.

| Klasse | Datei | Kurzbeschreibung |
|---|---|---|
| `Auth` | `assets/php/inc/gbdb_framework/core/auth.php` | ist das zentrale Benutzer-, Login-, JWT-, E-Mail-Verifikations- und 2FA-Modul |
| `Cache` | `assets/php/inc/gbdb_framework/core/cache.php` | ist ein sessionbasierter Zwischenspeicher für GBDB-Tabellen |
| `Converter` | `assets/php/inc/gbdb_framework/core/converter.php` | hilft bei deutscher Zahlenformatierung und Umwandlung von Preis-/Mengenwerten |
| `Cookie` | `assets/php/inc/gbdb_framework/core/cookies.php` | kapselt Cookie-Erstellung, sichere Optionen, Lesen, Löschen, Vergleiche und Refresh-Logik |
| `Crypt` | `assets/php/inc/gbdb_framework/core/crypt.php` | verschlüsselt und entschlüsselt Framework-Daten abhängig von `Vars::crypt_data()` und `Vars::cryptKey()` |
| `DatabaseBridge` | `assets/php/inc/gbdb_framework/core/database_bridge.php` | abstrahiert GBDB und SQL, damit Anwendungscode nicht permanent zwischen beiden Backends unterscheiden muss |
| `EqrAPI` | `assets/php/inc/gbdb_framework/plugins/eventqr.php` | ist die EventQR-API-Bindung |
| `FS` | `assets/php/inc/gbdb_framework/core/fs.php` | ist ein einfacheres Filesystem-Utility für Ordner, Dateien und rekursive Schreib-/Leseoperationen |
| `FileTool` | `assets/php/inc/gbdb_framework/core/file_tool.php` | ist ein robustes Dateisystem-Utility für Lesen, Schreiben, JSON, Backups, Ordnerkopien, Löschungen und Größenberechnung |
| `Format` | `assets/php/inc/gbdb_framework/core/format.php` | formatiert Datums-, Zeit- und Stringwerte für Eingabeformulare, HTML und kurze UI-Ausgaben |
| `GBDB` | `assets/php/inc/gbdb_framework/core/gbdb_sys.php` | ist die dateibasierte JSON-Datenbank des Frameworks |
| `GBDBv2` | `assets/php/inc/gbdb_framework/core/gbdb_sys_v2.php` | erweitert GBDB um Instanzen |
| `GetForm` | `assets/php/inc/gbdb_framework/core/getForm.php` | verarbeitet Request- und Formularwerte in einer zentralen Klasse |
| `GreenQL` | `assets/php/inc/gbdb_framework/core/greenql_engine.php` | ist die kleine Query-Sprache für GBDB |
| `GreenQLUIv2Helper` | `assets/php/inc/gbdb_framework/dev/gql_v2/greenql_ui_v2_helper.php` | Framework-Klasse für projektbezogene Hilfslogik. |
| `GreenQLv2` | `assets/php/inc/gbdb_framework/core/greenql_engine_v2.php` | ist die instanzfähige GreenQL-Engine |
| `Http` | `assets/php/inc/gbdb_framework/core/http.php` | bündelt einfache HTTP-GET/POST-Requests, JSON-Antworten, Redirects, Header-Auswertung und Mailversand |
| `Json` | `assets/php/inc/gbdb_framework/core/json.php` | stellt JSON-Helfer bereit, insbesondere für einheitliches Encodieren/Decodieren und Dateizugriff |
| `MqrApi` | `assets/php/inc/gbdb_framework/plugins/museumqr.php` | ist die MuseumQR-API-Bindung für Objekte, Feedback, Einstellungen, Sprachen, Touren und API-Keys |
| `ReCaptcha` | `assets/php/inc/gbdb_framework/core/recaptcha.php` | prüft Google reCAPTCHA-Antworten serverseitig mit den Keys aus `Vars` |
| `Ref` | `assets/php/inc/gbdb_framework/core/ref.php` | dient als kleine Referenz-/Hilfsklasse für wiederkehrende Ausgaben und interne Abkürzungen |
| `Route` | `assets/php/inc/gbdb_framework/core/route.php` | ist ein kleiner Router für PHP-Anwendungen |
| `SQL` | `assets/php/inc/gbdb_framework/core/sql.php` | ist die optionale SQL-Brücke |
| `Session` | `assets/php/inc/gbdb_framework/core/session.php` | Framework-Klasse für projektbezogene Hilfslogik. |
| `ShareSuiteAPI` | `assets/php/inc/gbdb_framework/plugins/sharesuite.php` | ist die API-Bindung für ShareSuite-Tabellen, Kalender, Blogs, BIB und Tickets |
| `Srv` | `assets/php/inc/Srv.php` | läuft auf dem Backend-Server |
| `SrvP` | `assets/php/inc/gbdb_framework/core/srvp.php` | ist der Client/Proxy auf Server 1 |
| `Srv_Mail` | `assets/php/srv_modules/Mail.php` | ist ein Server-Modul für Mail-Jobs, das über `Srv` geladen und ausgeführt werden kann |
| `Template` | `assets/php/inc/gbdb_framework/plugins/template.php` | ist ein Plugin-/Platzhalter für Template-Funktionen |
| `Time` | `assets/php/inc/gbdb_framework/core/time.php` | kapselt Zeit- und Datumshelfer für konsistente Ausgabe und Berechnung |
| `Tools` | `assets/php/inc/gbdb_framework/core/tools.php` | enthält allgemeine Helfer für IDs, Zufallswerte, Debug-Ausgaben und wiederkehrende kleine Framework-Aufgaben |
| `Validate` | `assets/php/inc/gbdb_framework/core/validate.php` | sammelt Validierungshelfer für typische Eingaben wie Mailadressen, Strings, Zahlen und Pflichtfelder |
| `Vars` | `assets/php/inc/gbdb_framework/ENV.php` | ist die zentrale Framework-Konfiguration |
| `mRootLicense` | `assets/php/inc/gbdb_framework/plugins/mroot.php` | verwaltet Lizenzprüfung und lokale Lizenzspeicherung gegen mRoot |
| `mRootUpdate` | `assets/php/inc/gbdb_framework/plugins/mroot.php` | prüft Updates, Changelog, Download-URL, Backups, ZIP-Extraktion, Release-Kopien und Schema-Migrationen |

## Empfehlung zum Lesen

1. Zuerst `gbdb_framework.md` lesen.
2. Danach je nach Einsatzzweck `gbdb.md`, `greenql.md`, `auth.md` oder `second_server_module.md`.
3. Für Produkt-Plugins die jeweilige API-Doku nutzen.
