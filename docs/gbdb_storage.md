# GBDBStorage – Storage-, WAL-, Index- und Snapshot-Helfer

## Zweck

`GBDBStorage` ist die technische Hilfsklasse hinter GBDB und GBDBv2. Sie kapselt robuste Dateischreibvorgänge, Append/WAL-Logik, Indexdateien, Snapshots, Checksummen, Constraints und Wiederherstellung.

Diese Klasse wird normalerweise nicht direkt von Anwendungsseiten genutzt. Sie ist wichtig, wenn man GBDB intern erweitert oder Fehler in der Datenhaltung analysiert.

## Kernideen

### Atomic Write

Dateien werden nicht blind überschrieben. Stattdessen schreibt das System in temporäre Dateien und ersetzt danach atomar. Dadurch sinkt das Risiko kaputter JSON-/DB-Dateien bei Abbruch während eines Schreibvorgangs.

### WAL / Append Log

Schreiboperationen können zunächst als Operationen im Append-/WAL-Bereich landen. Beim Kompaktieren werden diese Operationen in den Hauptdatenbestand übernommen.

### Checksummen

Checksummen helfen zu erkennen, ob Daten unerwartet verändert wurden.

### Snapshots

Snapshots sichern den Zustand einer Tabelle und ihrer technischen Begleitdateien. Das ist Grundlage für Reparatur, Rollback und Migrationssicherheit.

## Direkte Beispiele

```php
GBDBStorage::atomicWrite("/path/table.db", json_encode($rows));
```

```php
$checksum = GBDBStorage::checksum($rows);
```

```php
$index = GBDBStorage::buildIndex($rows, "email");
```

## Wichtige Methoden

| Methode | Zweck |
|---|---|
| `atomicWrite()` | Sicheres Schreiben einer Datei. |
| `appendLine()` | Fügt eine Zeile robust an eine Datei an. |
| `wal()` | Schreibt einen WAL-Eintrag mit Status und Transaktions-ID. |
| `normalizeMeta()` | Erzeugt/normalisiert Meta-Struktur. |
| `touchMeta()` | Aktualisiert technische Meta-Werte. |
| `shouldCompact()` | Entscheidet, ob Append-Operationen kompakt geschrieben werden sollten. |
| `checksum()` | Erstellt Prüfsumme für Rows. |
| `buildIndex()` | Baut einen Spaltenindex aus Rows. |
| `writeIndex()` | Schreibt Indexdatei. |
| `validateConstraints()` | Prüft `unique` und `required`. |
| `snapshot()` | Erstellt Snapshot einer Tabelle. |
| `restoreSnapshot()` | Stellt Snapshot wieder her. |
| `recoverWal()` | Liest und rekonstruiert WAL-Operationen. |
| `deleteTableArtifacts()` | Entfernt Tabelle, Meta, Append, Indizes, Snapshots. |

## Intention

GBDB soll trotz Dateiablage nicht wie ein loses JSON-Spielzeug wirken. `GBDBStorage` sorgt dafür, dass Schreibvorgänge reproduzierbarer, sicherer und diagnostizierbarer sind.
