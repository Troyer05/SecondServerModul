# greenQL Language Support 1.6.0

VS-Code-Extension für **greenQL / GreenQLv2**.

Die Extension ist jetzt stärker an `gql.txt` und den Dokumentationen in `docs/` ausgerichtet und kombiniert:

- TextMate-Grammar für normales VS-Code-Syntax-Highlighting
- Semantic-Token-Highlighting für deutlich weniger „weiße“ Tokens
- Hovers / Completions für greenQL-Befehle und Runtime-Funktionen
- Snippets für typische greenQL-, GBDB- und Advanced-Engine-Kommandos
- Diagnostics für typische Variablenfehler
- eigenes Theme `greenQL Bunt`

## Neu in 1.6.0

- Semantic Highlighting ergänzt, damit auch frei stehende Identifier, Objekt-Keys, Tabellen-/Base-Namen, Runtime-Funktionen und Advanced-Kommandos sauber eingefärbt werden.
- TextMate-Grammar neu sortiert und erweitert nach `gql.txt`.
- Bessere Erkennung für qualifizierte Tabellen wie `main.users`.
- Bessere Farben für:
  - `_variablen`
  - `$KONSTANTEN`
  - Objekt-Keys
  - Runtime-Funktionen
  - Commands
  - Clauses
  - Advanced-Engine-Kommandos
  - Datenbank-/Tabellenbezüge
- ACL-Wörter ergänzt: `GRANT`, `REVOKE`, `ON`, `READ`, `WRITE`, `ADMIN`.
- Theme um `semanticTokenColors` erweitert.

## Unterstützte Dateien

- `.gql`
- `.greenql`
- `.gq`
- `.gbdb`
- `.gdb`

## Wichtige Syntaxbereiche

```gql
DECLARE _name = param("demo_name");
DECLARE $APP = "greenQL";

USE INSTANCE demo;
ROOT main;
BRANCH users;

IF (_name == NULL) {
    _name = "Admin";
}

SEED users WITH uid="u001", name=_name, active=true;
PICK * FROM users WHERE active = true SORT name ASC LIMIT 10;

MONITOR main.users;
PAGE main.users PAGE 1 SIZE 50;
CURSOR main.users SIZE 100;
FULLTEXT main.users SEARCH "Admin" COLUMNS name,note LIMIT 25;

DECLARE _exists = table_exists("main", "users");
DECLARE _hits = fulltext_search("main", "users", "Admin", ["name", "note"], 25);
OUTPUT(_hits);
```

## Variablen-Regel

Normale Variablen müssen mit `_` beginnen:

```gql
DECLARE _title = "MuseumQR";
OUTPUT(_title);
```

Konstanten beginnen mit `$`:

```gql
DECLARE $APP_NAME = "greenQL";
LOG($APP_NAME);
```

## Runtime-Funktionen

Unterstützt werden unter anderem:

- `hash()`, `hash_sha256()`, `hash_sha512()`, `hash_md5()`
- `fetch_api()`, `api_fetch()`, `call_api()`
- `uni_random()`, `spark_id()`, `fresh_id()`
- `get_data()`, `add_data()`, `edit_data()`, `delete_data()`
- `count_data()`, `last_added()`
- `get_instances()`, `get_bases()`, `get_tables()`
- `instance_exists()`, `base_exists()`, `table_exists()`, `data_exists()`
- `monitor()`, `recover()`, `page()`, `cursor()`, `fulltext_search()`

## Empfehlung

Aktiviere für die beste Darstellung das Theme:

```text
Preferences: Color Theme → greenQL Bunt
```

Ohne dieses Theme funktionieren Grammar und Semantic Tokens ebenfalls, aber die Farben hängen dann vom aktuell aktiven VS-Code-Theme ab.


## ENV Highlighting

Die Extension erkennt `ENV("api_auth")` als Runtime-Funktion. GreenQL liest diesen Wert aus `scripts/greenql/.ENV/.env.php`.

```gql
DECLARE _api_auth = ENV("api_auth");
OUTPUT(_api_auth);
```
