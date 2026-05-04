# GBDBv2 – instanzfähige GBDB

## Zweck

`GBDBv2` erweitert GBDB um eine zusätzliche Ebene: **Instanzen**. Eine Instanz ist ein vollständig getrennter Datenraum. Dadurch kann dasselbe Framework mehrere Kunden, Projekte, Mandanten oder Testbereiche innerhalb einer Installation verwalten.

## Unterschied zu GBDB v1

| GBDB v1 | GBDBv2 |
|---|---|
| Datenbank → Tabelle → Rows | Instanz → Datenbank → Tabelle → Rows |
| gut für Einzelprojekt | gut für Mandanten und getrennte Bereiche |
| `schema.json` | `schema_v2.json` |
| keine aktive Instanz | aktive Instanz über `setInstance()` |

## Typischer Einsatz

- MuseumQR-Instanzen je Museum.
- Kundenbereiche in einem SaaS-System.
- Test-/Live-Umgebungen in einer Installation.
- getrennte Datenräume für GreenQL UI User.

## Minimalbeispiel

```php
GBDBv2::createInstance("museum_demo");
GBDBv2::setInstance("museum_demo");

GBDBv2::createDatabase("main");
GBDBv2::createTable("main", "objects", ["oid", "title", "audio", "active"]);

GBDBv2::insertData("main", "objects", [
    "oid" => "obj_001",
    "title" => "Römische Vase",
    "audio" => "vase.mp3",
    "active" => 1
]);
```

## Instanzverwaltung

```php
GBDBv2::createInstance("kunde_a");
GBDBv2::createInstance("kunde_b");

$instances = GBDBv2::listInstances();

GBDBv2::deleteInstance("kunde_b", true); // true = force
```

## Aktive Instanz setzen

```php
GBDBv2::setInstance("kunde_a");
```

Alle folgenden Datenbankoperationen laufen dann innerhalb dieser Instanz.

## Temporär in einer Instanz arbeiten

```php
$result = GBDBv2::withInstance("kunde_a", function () {
    return GBDBv2::getData("main", "settings");
});
```

Das ist sauberer, wenn ein Script zwischen mehreren Instanzen wechseln muss.

## CRUD in GBDBv2

Die CRUD-Methoden entsprechen GBDB v1:

```php
GBDBv2::setInstance("kunde_a");
GBDBv2::createDatabase("main");
GBDBv2::createTable("main", "users", ["uid", "username", "email"]);
GBDBv2::insertData("main", "users", ["uid" => "u1", "username" => "admin"]);
$user = GBDBv2::getData("main", "users", true, "uid", "u1");
```

## GreenQLv2 und Instanzen

```php
$result = GBDBv2::query('
    GROW INSTANCE kunde_a;
    USE INSTANCE kunde_a;
    GROW BASE main;
    ROOT main;
    GROW TABLE pages WITH slug, title, content;
    SEED pages WITH {"slug":"home","title":"Start","content":"Hallo"};
    PICK * FROM pages;
');
```

## Schema und Updates

GBDBv2 pflegt `schema_v2.json`. Das Schema enthält Instanz-, Datenbank- und Tabelleninformationen. Diese Struktur ist wichtig für Migrationen und Update-Systeme, weil neue Tabellen oder Spalten gezielt je Instanz ergänzt werden können.

## Indizes, Constraints, Snapshots, Health

GBDBv2 besitzt dieselben erweiterten Speicherfunktionen wie GBDB:

```php
GBDBv2::createIndex("main", "users", "email");
GBDBv2::addConstraint("main", "users", "email", "unique");
$sid = GBDBv2::snapshot("main", "users", "before_import");
$health = GBDBv2::health("main", "users");
```

Wichtig: Diese Operationen beziehen sich immer auf die aktuell aktive Instanz.

## Best Practices

- Instanznamen immer vor Userinput bereinigen oder über GreenQL/GBDBv2 reinigen lassen.
- Vor jedem Mandanten-Job explizit `setInstance()` aufrufen.
- Keine globalen Daten in Kundendaten-Instanzen ablegen; dafür besser eine Systeminstanz verwenden.
- Für GreenQL UI reservierte Instanzen nicht als normale Projektinstanzen nutzen.
- Bei Remote-Zugriff via `SrvP::setInstance()` Kontext sauber setzen.

# Erweiterte Entwicklernotizen

## Instanzen als Mandantenmodell

Eine Instanz ist nicht nur ein Ordner. Sie ist ein eigener logischer Datenraum. Das bedeutet: dieselbe Base `main` und dieselbe Tabelle `users` können in mehreren Instanzen existieren, ohne miteinander zu kollidieren.

```txt
kunde_a / main / users
kunde_b / main / users
kunde_c / main / users
```

Das ist besonders sinnvoll, wenn ein Produkt mehrfach installiert oder als SaaS angeboten wird. Jede Instanz kann ihre eigenen Settings, User, Inhalte und Logs besitzen.

## Sauberes Context-Handling

Bei GBDBv2 ist der wichtigste Fehler, versehentlich in der falschen Instanz zu arbeiten. Deshalb sollte jede Funktion, die Mandantendaten liest oder schreibt, explizit die Instanz setzen.

```php
function loadCustomerSettings(string $instance): array {
    return GBDBv2::withInstance($instance, function () {
        return GBDBv2::getData("main", "settings");
    });
}
```

So wird verhindert, dass globale statische Instanzzustände versehentlich weiterwirken.

## Remote-Kontext mit SrvP

Wenn GBDBv2 remote genutzt wird, muss die Instanz in den Context:

```php
SrvP::setInstance("kunde_a");
$data = SrvP::getData("main", "objects");
```

Oder pro Request:

```php
$data = SrvP::getData("main", "objects", false, "", "", [
    "instance" => "kunde_a"
]);
```

## Systeminstanzen

Für interne Tools wie GreenQL UI gibt es reservierte Instanzen. Solche Namen sollten nicht in Kundenauswahlen erscheinen und nicht über normale Adminfunktionen löschbar sein.

## Update- und Schema-Logik

`schema_v2.json` kann als Grundlage dienen, um je Instanz Tabellen und Spalten nachzuziehen. Ein Update-System kann also prüfen:

- Welche Instanzen existieren?
- Welche Bases fehlen?
- Welche Tabellen fehlen?
- Welche Spalten fehlen?
- Welche Defaults sollen gesetzt werden?

## Beispiel: alle Instanzen warten

```php
foreach (GBDBv2::listInstances() as $instance) {
    GBDBv2::withInstance($instance, function () {
        foreach (GBDBv2::listDBs() as $db) {
            foreach (GBDBv2::listTables($db) as $table) {
                GBDBv2::compactTable($db, $table);
            }
        }
    });
}
```

## Typische Fehler

| Fehler | Erklärung | Lösung |
|---|---|---|
| Daten erscheinen im falschen Kundenbereich | aktive Instanz wurde nicht gesetzt | `setInstance()` oder `withInstance()` verwenden. |
| `listDBs()` ist leer | Instanz existiert nicht oder wurde nicht aktiviert | `createInstance()` und `setInstance()` prüfen. |
| UI zeigt Systemdaten | Systeminstanz nicht gefiltert | GreenQL-UI-Helper-Filter nutzen. |
| Remote-Request landet lokal/default | Context fehlt | `SrvP::setInstance()` setzen. |
