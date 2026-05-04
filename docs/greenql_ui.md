# GreenQL UI / GreenQL UI v2

## Zweck

Die GreenQL UI ist eine Weboberfläche, um GreenQL-Scripte auszuführen, Datenstrukturen einzusehen und je nach Rolle Datenbankbereiche zu verwalten. Die v2-Variante nutzt `GreenQLUIv2Helper` und ist auf GBDBv2-/Instanzbetrieb ausgelegt.

## Dateien

| Datei | Zweck |
|---|---|
| `gbdb_ui.php?tool=greenql` | ältere/klassische UI über die zentrale GBDB UI. |
| `gbdb_ui.php?tool=greenql_v2` | neue UI mit Instanzen, Userverwaltung und Rechteprüfung über die zentrale GBDB UI. |
| `assets/css/gbdb/greenql_ui.v2.css` | Styles der v2 UI. |
| `assets/css/gbdb/gbdb_ui.css` | globale Navigation und Dashboard der gebündelten GBDB UI. |
| `assets/php/inc/gbdb_framework/ui/greenql_v2_helper.php` | Backend-Helper für Auth, Rechte, Script-Ausführung. |
| `assets/php/inc/gbdb_framework/ui/greenql_v2.logic.php` | Controller-/Hilfslogik der v2 UI. |
| `assets/php/inc/gbdb_framework/ui/greenql_v2.page.php` | HTML-Template der v2 UI. |

## Systemdaten

Die UI v2 nutzt eine interne Systeminstanz:

```txt
__greenql_ui_v2_system
```

Darin werden UI-User in `system/users` gespeichert. Diese interne Instanz ist reserviert und soll nicht wie normale Projekt-/Kundeninstanzen behandelt werden.

## Userverwaltung

Ein User besitzt typischerweise:

| Feld | Bedeutung |
|---|---|
| `username` | Loginname. |
| `password` | gehashter Passwortwert. |
| `role` | Rolle wie `admin`, `write`, `structure`, `read`. |
| `instances` | erlaubte Instanzen, `*` für alle. |
| `bases` | erlaubte Bases, `*` für alle. |
| `active` | Login erlaubt oder gesperrt. |

## Rollenidee

| Rolle | Intention |
|---|---|
| `admin` | Vollzugriff auf UI-User, Struktur, Daten und Scripte. |
| `structure` | darf Struktur verändern, z. B. Tabellen/Bases anlegen. |
| `write` | darf Daten schreiben/ändern, aber nicht zwingend Struktur ändern. |
| `read` | darf lesen und eingeschränkt ausführen. |

## Zugriff auf Instanzen und Bases

Die UI prüft mit Methoden wie:

```php
GreenQLUIv2Helper::canAccessInstance("kunde_a");
GreenQLUIv2Helper::canAccessDb("kunde_a", "main");
```

Dadurch können User nur bestimmte Mandanten oder Datenbanken sehen.

## Login und Boot

```php
// Root-Einstieg:
// /gbdb_ui.php?tool=greenql_v2

GreenQLUIv2Helper::boot();

if (!GreenQLUIv2Helper::loggedIn()) {
    // Loginmaske anzeigen
}
```

`boot()` initialisiert Session, CSRF und die interne User-Tabelle.

## CSRF

```php
$token = GreenQLUIv2Helper::csrf();
$ok = GreenQLUIv2Helper::checkCsrf($_POST["csrf"] ?? "");
```

Jede schreibende Aktion sollte CSRF-validiert werden.

## Script-Ausführung

```php
$result = GreenQLUIv2Helper::runScript($script, "kunde_a", [
    "uid" => "u1"
]);
```

Vor der Ausführung werden Berechtigungen geprüft. Außerdem blockiert der Helper reservierte Namen/Systembereiche.

## Parameterformat

Die UI kann rohe Parameter aus einem Textfeld parsen:

```json
{"uid":"u1","active":true}
```

Oder einfache key/value-ähnliche Eingaben, abhängig von `parseParams()`.

## Schutz reservierter Namen

`reservedInstance()` und `reservedName()` verhindern, dass interne Namen wie Systeminstanzen oder Frameworktabellen normal manipuliert werden.

## Intention

Die UI soll ein Developer-/Admin-Werkzeug sein, aber nicht versehentlich interne Systemdaten offenlegen. Deshalb filtert sie Systemrows, schützt reservierte Namen und trennt UI-Userrechte von normalen Projektusern.

## Best Practices

- Nach Login keine interne User-DB automatisch anzeigen.
- Userverwaltung als eigener Bereich, nicht als normale Datenbanknavigation.
- Bei Script-Ausführung immer Instanzkontext setzen.
- Für Kunden-/Projektuser keine `admin`-Rolle vergeben.
- Systeminstanzen niemals in normalen Listen anzeigen.

# Bedien- und Sicherheitskonzept

## Warum eine eigene UI-Userverwaltung?

Die GreenQL UI ist ein Entwickler- und Adminwerkzeug. Sie darf nicht automatisch dieselben User verwenden wie eine Produktanwendung. Ein MuseumQR-Besucher, EventQR-Kunde oder normaler App-User soll niemals dadurch Zugriff auf Datenbank-/Scriptfunktionen bekommen.

Darum besitzt die UI v2 eine getrennte interne Userverwaltung.

## Empfohlener UI-Ablauf

1. `GreenQLUIv2Helper::boot()` initialisiert Systeminstanz und Session.
2. Wenn noch kein UI-User existiert, wird ein Setup-/Admin-User erzeugt.
3. Login prüft Username, Passwort und Aktivstatus.
4. Nach Login wird nicht automatisch eine Systemdatenbank angezeigt.
5. User wählt Instanz und Base entsprechend seiner Rechte.
6. Scriptausführung prüft Rolle, Instanz, Base und reservierte Namen.

## Rechtebeispiele

### Volladmin

```txt
role: admin
instances: *
bases: *
```

### Kunde darf nur eigene Instanz lesen/schreiben

```txt
role: write
instances: kunde_a
bases: main,content
```

### Entwickler darf Struktur in Testinstanz ändern

```txt
role: structure
instances: dev,test
bases: *
```

## Script-Schutz

Die UI sollte gefährliche Befehle nicht nur optisch markieren, sondern serverseitig prüfen. Besonders kritisch sind:

- `DROP INSTANCE`
- `DROP BASE`
- `DROP TABLE`
- Änderungen an Systeminstanzen
- Zugriff auf interne Userdaten
- Dateioperationen mit ungeprüften Pfaden

## UX-Hinweise

Eine gute GreenQL UI sollte nicht wie ein roher Texteditor wirken. Hilfreich sind:

- getrennte Output-Einträge nacheinander wie ein Log,
- Syntax Highlighting mit unterschiedlichen Farben für Befehle, Variablen, Konstanten, Strings, Zahlen, Kommentare,
- sichtbarer aktiver Instanz-/Base-Kontext,
- klare Fehlermeldungen mit Befehlsnummer,
- Userverwaltung als eigener Bereich,
- keine automatische Anzeige interner Systemtabellen nach Login.

## Debugging

Wenn ein Script nicht läuft:

1. Prüfen, ob die aktive Instanz stimmt.
2. Prüfen, ob Userrolle den Befehl erlaubt.
3. Script in kleinere Teile splitten.
4. `OUTPUT` und `LOG()` setzen.
5. Bei Datenbankproblemen `HEALTH` ausführen.
