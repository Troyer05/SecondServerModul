const vscode = require("vscode");

const PURPOSE = new Map(Object.entries({
    "_...": "Normale greenQL Variable. Muss bei Deklaration und Aufruf mit _ beginnen.",
    "$...": "Konstante. Wird wie eine Variable genutzt, darf nach erster Deklaration aber nicht überschrieben werden.",
    "DECLARE": "Variable oder Konstante deklarieren.",
    "DECALRE": "Tippfehler-kompatibler Alias für DECLARE.",
    "DELACE": "Legacy-/Alias-Schreibweise, bleibt fürs Highlighting erhalten.",
    "PARAM": "Script-Parameter aus dem Runtime-Parameterobjekt lesen.",
    "NOW": "Aktueller Zeitwert.",
    "TRUE": "Boolean true.",
    "FALSE": "Boolean false.",
    "NULL": "Null-Wert.",
    "IF": "Bedingten Block starten.",
    "ELSE": "Alternativblock.",
    "FOR": "Schleife.",
    "MAP_OBJECT": "Objekt/Array iterieren.",
    "BACK": "Wert aus Funktion oder Script zurückgeben.",
    "FILE.BACK": "Wert aus einer per FILE.RUN ausgeführten Datei zurückgeben.",
    "END_PROC": "Stoppt die gesamte Script-Ausführung erfolgreich.",
    "this_f.restart": "Startet die aktuelle Funktion erneut. Sicherheitslimit ist Engine-Sache.",
    "ERROR MSG": "Fehler mit Nachricht ausgeben.",
    "MSG": "Nachricht ausgeben.",
    "OUTPUT": "Schreibt einen Eintrag in den Output-Stream. Mehrere Outputs werden nacheinander angezeigt.",
    "SET_LOGFILE": "Setzt die aktive Logdatei.",
    "LOG": "Schreibt Werte/Objekte in die aktive Logdatei.",
    "CLEAR_LOG": "Leert die aktive Logdatei.",
    "DELETE_LOG_FILE": "Löscht die aktive Logdatei und entfernt sie aus dem Context.",
    "FILE.INCLUDE": "Führt ein Script im aktuellen Context aus.",
    "FILE.RUN": "Führt ein Script separat mit optionalem Parameterobjekt aus.",
    "INCLUDE": "Highlighting-Hilfswort für FILE.INCLUDE.",
    "RUN": "Highlighting-Hilfswort für FILE.RUN.",
    "FILE": "Highlighting-Hilfswort für FILE.*.",
    "USE INSTANCE": "GBDBv2-Instanz aktivieren.",
    "ROOT INSTANCE": "GBDBv2-Instanz fokussieren.",
    "SHOW INSTANCES": "Instanzen anzeigen.",
    "GROW INSTANCE": "Instanz erstellen.",
    "DROP INSTANCE": "Instanz löschen.",
    "FORCE": "Erzwingt Löschen bei DROP INSTANCE.",
    "ROOT": "Aktive Base setzen.",
    "BRANCH": "Aktive Tabelle setzen.",
    "SHOW BASES": "Bases anzeigen.",
    "SHOW TABLES": "Tabellen anzeigen.",
    "GROW BASE": "Base erstellen.",
    "DROP BASE": "Base löschen.",
    "GROW TABLE": "Tabelle erstellen.",
    "DROP TABLE": "Tabelle löschen.",
    "ALTER TABLE ADD": "Spalte ergänzen.",
    "COLUMN": "Spaltenbegriff fürs Highlighting.",
    "DEFAULT": "Default-Wert bei Spalten.",
    "DESCRIBE": "Tabellenstruktur anzeigen.",
    "PICK": "Daten lesen.",
    "FROM": "Tabelle/Base im Query angeben.",
    "WHERE": "Filterbedingung.",
    "SORT": "Sortierung.",
    "ASC": "Aufsteigende Sortierung.",
    "DESC": "Absteigende Sortierung.",
    "LIMIT": "Ergebnislimit.",
    "MAX": "Ergebnislimit.",
    "SEED": "Datensatz einfügen.",
    "WITH": "Datenblock bei SEED/RESHAPE.",
    "RESHAPE": "Datensatz ändern.",
    "ERASE": "Datensatz löschen.",
    "DELETE": "Löschbefehl/Keyword.",
    "IN": "Kontext bei EXISTS DATA / Tabellenbezug.",
    "AS": "Alias-/Klassenzusammenhang.",
    "BEGIN": "Transaktion starten.",
    "COMMIT": "Transaktion speichern.",
    "ROLLBACK": "Transaktion zurückrollen.",
    "TRANSACTION": "Transaktionsstatus anzeigen.",
    "PACK": "Tabelle/Base optimieren/kompaktieren.",
    "PEEK": "Vorschau/Inspektion.",
    "CHECK": "Prüfung.",
    "HEALTH": "Health-Check.",
    "REPAIR": "Reparaturversuch.",
    "SNAPSHOT": "Snapshot/Backup-Kontext.",
    "META": "Metadatenblock setzen.",
    "EXPLAIN": "Query erklären.",
    "MONITOR": "Zeigt Monitoringdaten an. Ohne Parameter Übersicht, mit main.users eine konkrete Tabelle.",
    "RECOVER": "Führt WAL-/Append-Recovery für eine Tabelle aus.",
    "PAGE": "Lädt eine Tabellen-Seite: PAGE main.users PAGE 1 SIZE 50.",
    "CURSOR": "Lädt Tabellen-Daten cursor-basiert: CURSOR main.users SIZE 100.",
    "FULLTEXT": "Volltextsuche: FULLTEXT main.users SEARCH \"Max Muster\".",
    "SIZE": "Limit-/Seitengröße bei PAGE und CURSOR.",
    "SEARCH": "Suchtext-Clause bei FULLTEXT.",
    "AFTER": "Cursor-Fortsetzung mit Next-Cursor.",
    "COLUMNS": "Spaltenauswahl bei FULLTEXT.",
    "INDEX": "Index anlegen.",
    "INDEXES": "Indexe anzeigen.",
    "UNINDEX": "Index entfernen.",
    "REINDEX": "Index neu aufbauen.",
    "CONSTRAINT": "Constraint setzen.",
    "CONSTRAINTS": "Constraints anzeigen.",
    "UNIQUE": "Einmaligkeitsregel.",
    "REQUIRED": "Pflichtfeldregel.",
    "CLASS": "Klasse definieren oder aufrufen.",
    "C": "Kurzform für CLASS.",
    "CALL": "Klassenmethode oder Funktion aufrufen.",
    "PUB": "Öffentliche Sichtbarkeit.",
    "PRIV": "Private Sichtbarkeit.",
    "F": "Funktion/Methode definieren.",
    "EXISTS INSTANCE": "Prüft, ob Instanz existiert.",
    "EXISTS BASE": "Prüft, ob Base existiert.",
    "EXISTS TABLE": "Prüft, ob Tabelle in aktiver Base existiert.",
    "EXISTS DATA": "Prüft, ob ein Datensatz existiert.",
    "GRANT": "Recht/Rolle auf eine Tabelle vergeben.",
    "REVOKE": "Recht/Rolle auf einer Tabelle entziehen.",
    "ON": "ACL-Ziel bei GRANT/REVOKE."
}));

const RUNTIME_FUNCTIONS = [
    ["hash", "Standardmäßig SHA-256 Hash oder Hash mit Algorithmus."],
    ["hash_sha256", "SHA-256 Hash."], ["hash_sha512", "SHA-512 Hash."], ["hash_md5", "MD5 Hash."],
    ["hash_adler32", "Adler32 Hash."], ["hash_crc32", "CRC32 Hash."], ["len", "Länge von String/Array."],
    ["ENV", "Liest einen Wert aus scripts/greenql/.ENV/.env.php, z.B. ENV(\"api_auth\")."],
    ["fetch_api", "Externe API abrufen: fetch_api(url, bodyObj, headerObj)."], ["api_fetch", "Alias für fetch_api()."], ["call_api", "Alias für fetch_api()."],
    ["uni_random", "Einmalige Random-Zeichenkette erzeugen."], ["spark_id", "Alias für uni_random()."], ["fresh_id", "Alias für uni_random()."],
    ["get_data", "Daten holen."], ["fetch_data", "Alias für get_data()."], ["fetch", "Alias für get_data()."],
    ["add_data", "Datensatz einfügen."], ["plant_data", "Alias für add_data()."], ["seed_data", "Alias für add_data()."],
    ["editData", "Datensatz ändern."], ["edit_data", "Datensatz ändern."], ["reshape_data", "Alias für edit_data()."],
    ["delete_data", "Datensatz löschen."], ["erase_data", "Alias für delete_data()."], ["delete_data_recursive", "Datensatz rekursiv/aliasartig löschen."],
    ["count_data", "Datensätze zählen."], ["tally_data", "Alias für count_data()."], ["last_added", "Letzten Datensatz holen."], ["last_data", "Alias für last_added()."],
    ["get_instances", "Instanzen auflisten."], ["instances", "Alias für get_instances()."], ["get_bases", "Bases einer Instanz auflisten."], ["bases", "Alias für get_bases()."],

    ["instance_exists", "Prüft, ob eine GBDBv2-Instanz existiert."], ["base_exists", "Prüft, ob eine Base existiert."],
    ["table_exists", "Prüft, ob eine Tabelle existiert."], ["data_exists", "Prüft, ob ein Datensatz anhand eines Filterobjekts existiert."],
    ["monitor", "Liest Monitoringdaten als Runtime-Funktion."], ["recover", "Führt Recovery als Runtime-Funktion aus."],
    ["page", "Lädt eine Tabellenseite als Runtime-Funktion."], ["cursor", "Lädt einen Cursor-Slice als Runtime-Funktion."],
    ["fulltext_search", "Führt Volltextsuche als Runtime-Funktion aus."],
    ["get_tables", "Tabellen einer Base auflisten."], ["tables", "Alias für get_tables()."], ["new_column", "Spalte hinzufügen."], ["sprout_column", "Alias für new_column()."],
    ["delete_column", "Spalte löschen."], ["prune_column", "Alias für delete_column()."], ["delete_instance", "Instanz löschen."], ["drop_instance", "Alias für delete_instance()."],
    ["delete_base", "Base löschen."], ["drop_base", "Alias für delete_base()."], ["delete_table", "Tabelle löschen."], ["drop_table", "Alias für delete_table()."],
    ["rename_instance", "Instanz umbenennen."], ["rename_base", "Base umbenennen."], ["rename_table", "Tabelle umbenennen."],
    ["transfer_data", "Tabelle/Daten von Objekt nach Objekt kopieren."], ["copy_data", "Alias für transfer_data()."],
    ["transfer_data_delete", "Kopieren und Quelle löschen."], ["move_data", "Alias für transfer_data_delete()."],
    ["set_data_readonly", "Datensatz readonly setzen/entfernen."], ["lock_data", "Alias für set_data_readonly()."]
];
for (const [name, purpose] of RUNTIME_FUNCTIONS) PURPOSE.set(name, purpose);

const COMMANDS = [...PURPOSE.keys()].filter(k => !k.includes("...")).sort((a,b) => b.length - a.length);
const VALUE_WORDS = new Set(["true", "false", "null", "now", "asc", "desc", "and", "or", "not", "in", "from", "where", "sort", "limit", "size", "search", "after", "columns", "max", "this", "file", "include", "run", "as", "with", "default"]);



const SEMANTIC_TOKEN_TYPES = [
    "keyword", "variable", "parameter", "function", "method", "property", "string", "number",
    "comment", "class", "type", "namespace", "operator", "macro", "enumMember"
];
const SEMANTIC_TOKEN_MODIFIERS = ["declaration", "readonly", "control", "danger", "runtime"];
const SEMANTIC_LEGEND = new vscode.SemanticTokensLegend(SEMANTIC_TOKEN_TYPES, SEMANTIC_TOKEN_MODIFIERS);
const TOKEN_TYPE = Object.fromEntries(SEMANTIC_TOKEN_TYPES.map((name, index) => [name, index]));
const TOKEN_MODIFIER = Object.fromEntries(SEMANTIC_TOKEN_MODIFIERS.map((name, index) => [name, 1 << index]));

const DECLARATION_WORDS = new Set(["DECLARE", "DECALRE", "DELACE"]);
const CONTROL_WORDS = new Set(["IF", "ELSE", "FOR", "BACK", "END_PROC"]);
const DANGER_WORDS = new Set(["ERASE", "DELETE", "DROP", "UNINDEX", "REVOKE", "ROLLBACK"]);
const COMMAND_WORDS = new Set([
    "USE", "ROOT", "BRANCH", "SHOW", "GROW", "ALTER", "DESCRIBE", "PICK", "SEED", "RESHAPE",
    "BEGIN", "COMMIT", "TRANSACTION", "PACK", "PEEK", "CHECK", "HEALTH", "REPAIR", "SNAPSHOT", "META", "EXPLAIN",
    "MONITOR", "RECOVER", "PAGE", "CURSOR", "FULLTEXT", "EXISTS", "INDEX", "INDEXES", "REINDEX", "CONSTRAINT", "CONSTRAINTS", "GRANT", "REVOKE",
    "CLASS", "C", "CALL", "PUB", "PRIV", "PUBLIC", "PRIVATE", "PROTECTED", "STATIC", "F", "FUNCTION", "FILE", "OUTPUT",
    "SET_LOGFILE", "LOG", "CLEAR_LOG", "DELETE_LOG_FILE", "ENV", "MAP_OBJECT", "MSG", "ERROR"
]);
const TYPE_WORDS = new Set(["INSTANCE", "INSTANCES", "BASE", "BASES", "TABLE", "TABLES", "COLUMN", "DATA"]);
const CLAUSE_WORDS = new Set(["FROM", "WHERE", "SORT", "ASC", "DESC", "LIMIT", "SIZE", "SEARCH", "AFTER", "COLUMNS", "MAX", "WITH", "IN", "AS", "ADD", "FORCE", "DEFAULT", "UNIQUE", "REQUIRED", "ON", "READ", "WRITE", "ADMIN"]);
const LITERAL_WORDS = new Set(["TRUE", "FALSE", "NULL", "NOW"]);
const RUNTIME_FUNCTION_SET = new Set(RUNTIME_FUNCTIONS.map(([name]) => name.toLowerCase()).concat(["param", "hash", "len"]));

function semanticTypeForWord(word, nextNonSpace) {
    const upper = word.toUpperCase();
    const lower = word.toLowerCase();
    if (DECLARATION_WORDS.has(upper)) return ["keyword", TOKEN_MODIFIER.declaration];
    if (CONTROL_WORDS.has(upper)) return ["keyword", TOKEN_MODIFIER.control];
    if (DANGER_WORDS.has(upper)) return ["keyword", TOKEN_MODIFIER.danger];
    if (COMMAND_WORDS.has(upper)) return ["keyword", 0];
    if (TYPE_WORDS.has(upper)) return ["type", 0];
    if (CLAUSE_WORDS.has(upper)) return ["keyword", 0];
    if (LITERAL_WORDS.has(upper)) return ["macro", 0];
    if (RUNTIME_FUNCTION_SET.has(lower) && nextNonSpace === "(") return ["function", TOKEN_MODIFIER.runtime];
    if (nextNonSpace === "(") return ["function", 0];
    return null;
}

function provideSemanticTokens(document) {
    const builder = new vscode.SemanticTokensBuilder(SEMANTIC_LEGEND);
    for (let line = 0; line < document.lineCount; line++) {
        const text = document.lineAt(line).text;
        tokenizeSemanticLine(text, line, builder);
    }
    return builder.build();
}

function tokenizeSemanticLine(text, line, builder) {
    let i = 0;
    const push = (start, len, type, modifier = 0) => {
        if (len > 0 && TOKEN_TYPE[type] !== undefined) builder.push(line, start, len, TOKEN_TYPE[type], modifier);
    };

    while (i < text.length) {
        const ch = text[i];
        const next = text[i + 1];

        if (ch === "#" || (ch === "/" && next === "/") || (ch === "-" && next === "-")) {
            push(i, text.length - i, "comment");
            return;
        }

        if (ch === '"' || ch === "'" || ch === "`") {
            const quote = ch;
            const start = i;
            i++;
            let escaped = false;
            while (i < text.length) {
                const c = text[i];
                if (escaped) escaped = false;
                else if (c === "\\") escaped = true;
                else if (c === quote) { i++; break; }
                i++;
            }
            const after = text.slice(i).trimStart();
            if (after.startsWith(":")) {
                push(start + 1, Math.max(0, i - start - 2), "property");
            } else {
                push(start, i - start, "string");
            }
            continue;
        }

        if (/[0-9]/.test(ch)) {
            const start = i;
            i++;
            while (i < text.length && /[0-9A-Fa-f.x]/.test(text[i])) i++;
            push(start, i - start, "number");
            continue;
        }

        if (ch === "$") {
            const start = i;
            i++;
            while (i < text.length && /[A-Za-z0-9_]/.test(text[i])) i++;
            push(start, i - start, "variable", TOKEN_MODIFIER.readonly);
            continue;
        }

        if (/[A-Za-z_]/.test(ch)) {
            const start = i;
            i++;
            while (i < text.length && /[A-Za-z0-9_]/.test(text[i])) i++;
            const word = text.slice(start, i);
            const rest = text.slice(i);
            const nextNonSpace = (rest.match(/^\s*(.)/) || [])[1] || "";
            const prev = text.slice(0, start).trimEnd();
            const nextTrim = rest.trimStart();

            if (word.startsWith("_")) {
                push(start, i - start, "variable");
            } else if (/^(PUB|PRIV|PUBLIC|PRIVATE|PROTECTED|STATIC)$/i.test(word)) {
                push(start, i - start, "keyword", TOKEN_MODIFIER.declaration);
            } else if (nextTrim.startsWith(":")) {
                push(start, i - start, "property");
            } else if (/\.$/.test(prev)) {
                push(start, i - start, "method");
            } else {
                const mapped = semanticTypeForWord(word, nextNonSpace);
                if (mapped) push(start, i - start, mapped[0], mapped[1]);
                else if (/\b(C|CLASS)\s+$/i.test(prev)) push(start, i - start, "class");
                else if (/\b(F|FUNCTION)\s+$/i.test(prev)) push(start, i - start, "function", TOKEN_MODIFIER.declaration);
                else if (/\b(?:INSTANCE|BASE|TABLE|FROM|IN|ROOT|BRANCH|DESCRIBE|PACK|SEED|RESHAPE)\s+$/i.test(prev)) push(start, i - start, "namespace");
                else push(start, i - start, "property");
            }
            continue;
        }

        if (/[=<>!~+\-*/%|&.]/.test(ch)) {
            push(i, 1, "operator");
        }
        i++;
    }
}

function activate(context) {
    const diagnostics = vscode.languages.createDiagnosticCollection("greenql");
    context.subscriptions.push(diagnostics);

    context.subscriptions.push(vscode.languages.registerDocumentSemanticTokensProvider("greenql", {
        provideDocumentSemanticTokens: provideSemanticTokens
    }, SEMANTIC_LEGEND));

    context.subscriptions.push(vscode.languages.registerCompletionItemProvider("greenql", {
        provideCompletionItems(document, position) {
            const before = document.lineAt(position).text.substring(0, position.character);
            const items = [];
            addContextSnippets(items, before);
            for (const [word, detail] of PURPOSE.entries()) {
                if (word.includes("...")) continue;
                const item = new vscode.CompletionItem(word, word.includes("(") || /^[a-z_]+$/.test(word) ? vscode.CompletionItemKind.Function : vscode.CompletionItemKind.Keyword);
                item.detail = detail;
                item.documentation = new vscode.MarkdownString(`**${word}**\n\n${detail}`);
                items.push(item);
            }
            return items;
        }
    }, " ", ";", "(", "[", ".", "/", "$", "_"));

    context.subscriptions.push(vscode.languages.registerHoverProvider("greenql", {
        provideHover(document, position) {
            const range = document.getWordRangeAtPosition(position, /[A-Za-z_.$][A-Za-z0-9_.$]*/);
            if (!range) return;
            const word = document.getText(range);
            const upper = word.toUpperCase();
            const lower = word.toLowerCase();
            const detail = PURPOSE.get(word) || PURPOSE.get(upper) || PURPOSE.get(lower);
            if (!detail) return;
            return new vscode.Hover(new vscode.MarkdownString(`**greenQL: ${word}**\n\n${detail}`), range);
        }
    }));

    const runValidation = document => validateDocument(document, diagnostics);
    if (vscode.window.activeTextEditor) runValidation(vscode.window.activeTextEditor.document);
    context.subscriptions.push(vscode.workspace.onDidOpenTextDocument(runValidation));
    context.subscriptions.push(vscode.workspace.onDidSaveTextDocument(runValidation));
    context.subscriptions.push(vscode.workspace.onDidChangeTextDocument(event => runValidation(event.document)));
    context.subscriptions.push(vscode.window.onDidChangeActiveTextEditor(editor => { if (editor) runValidation(editor.document); }));
}

function addContextSnippets(items, before) {
    function sn(label, body, detail) {
        const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Snippet);
        item.insertText = new vscode.SnippetString(body);
        item.detail = detail;
        items.push(item);
    }
    if (/\bDECLARE\s+$/i.test(before)) sn("_variable", "_${1:name} = ${2:value};", "Variable deklarieren");
    if (/\bDECLARE\s+$/i.test(before)) sn("$CONSTANT", "\\$${1:NAME} = ${2:value};", "Konstante deklarieren");
    if (/\b(?:GROW|DROP|SHOW|USE|ROOT|EXISTS)\s+$/i.test(before)) {
        sn("INSTANCE", "INSTANCE ${1:main};", "Instance Kontext");
        sn("BASE", "BASE ${1:main};", "Base Kontext");
        sn("TABLE", "TABLE ${1:users};", "Table Kontext");
        sn("DATA", "DATA IN ${1:users} WHERE ${2:id} = ${3:_id};", "Data Exists Kontext");
    }
    if (/\bFILE\.$/i.test(before)) {
        sn("INCLUDE", "INCLUDE \"${1:scripts/include.gql}\";", "Script einbinden");
        sn("RUN", "RUN \"${1:scripts/job.gql}\" WITH [\"${2:key}\": ${3:_value}];", "Script separat ausführen");
        sn("BACK", "BACK ${1:_value};", "FILE.RUN Rückgabe");
    }
    if (/\b(?:MONITOR|RECOVER|PAGE|CURSOR|FULLTEXT)\s*$/i.test(before)) {
        sn("main.users", "${1:main}.${2:users};", "Qualifizierter Tabellenbezug");
    }
    if (/\bFULLTEXT\s+[A-Za-z0-9_.-]+\s+$/i.test(before)) {
        sn("SEARCH", "SEARCH \"${1:Suchtext}\" LIMIT ${2:50};", "Volltextsuche");
    }
    if (/\bCURSOR\s+[A-Za-z0-9_.-]+\s+$/i.test(before)) {
        sn("SIZE", "SIZE ${1:100};", "Cursor-Größe");
    }
    if (/\bPAGE\s+[A-Za-z0-9_.-]+\s+$/i.test(before)) {
        sn("PAGE", "PAGE ${1:1} SIZE ${2:50};", "Page-Größe");
    }
}

function validateDocument(document, diagnostics) {
    if (document.languageId !== "greenql") return;
    const items = [];
    const constants = new Map();
    const lines = document.getText().split(/\r?\n/);

    for (let lineIndex = 0; lineIndex < lines.length; lineIndex++) {
        const raw = lines[lineIndex];
        const line = stripStringsAndComments(raw);
        findDeclarationIssues(line, lineIndex, items, constants);
        findFunctionParamIssues(line, lineIndex, items);
        findBareFunctionVariableIssues(line, lineIndex, items);
    }
    diagnostics.set(document.uri, items);
}

function stripStringsAndComments(line) {
    let out = "";
    let quote = null;
    let escaped = false;
    for (let i = 0; i < line.length; i++) {
        const ch = line[i], next = line[i + 1];
        if (!quote && ch === "#") return out + " ".repeat(line.length - out.length);
        if (!quote && ch === "/" && next === "/") return out + " ".repeat(line.length - out.length);
        if (!quote && ch === "-" && next === "-") return out + " ".repeat(line.length - out.length);
        if (quote) {
            out += " ";
            if (escaped) escaped = false;
            else if (ch === "\\") escaped = true;
            else if (ch === quote) quote = null;
            continue;
        }
        if (ch === "\"" || ch === "'" || ch === "`") { quote = ch; out += " "; continue; }
        out += ch;
    }
    return out;
}

function findDeclarationIssues(line, lineIndex, items, constants) {
    const regex = /\b(?:DECLARE|DECALRE|DELACE)\s+([^\s=;]+)/gi;
    let match;
    while ((match = regex.exec(line))) {
        const name = match[1];
        const start = match.index + match[0].lastIndexOf(name);
        if (name.startsWith("$")) {
            const key = name.toLowerCase();
            if (constants.has(key)) pushIssue(items, lineIndex, start, name.length, `Konstante ${name} wurde bereits deklariert und sollte nicht überschrieben werden.`, vscode.DiagnosticSeverity.Warning);
            constants.set(key, true);
        } else if (!/^_[A-Za-z][A-Za-z0-9_]*$/.test(name)) {
            pushIssue(items, lineIndex, start, name.length, "greenQL Variablen müssen bei Deklaration mit _ beginnen. Konstanten beginnen mit $.");
        }
    }
}

function findFunctionParamIssues(line, lineIndex, items) {
    const regex = /\b(?:F|FUNCTION)\s+[A-Za-z_][A-Za-z0-9_]*\s*\(([^)]*)\)/gi;
    let match;
    while ((match = regex.exec(line))) {
        const inner = match[1];
        const offset = match.index + match[0].indexOf(inner);
        for (const p of inner.split(",")) {
            const name = p.trim();
            if (!name) continue;
            const clean = name.replace(/=.*/, "").trim();
            if (!clean.startsWith("_") && !clean.startsWith("$")) pushIssue(items, lineIndex, offset + inner.indexOf(clean), clean.length, "Funktionsparameter müssen mit _ beginnen oder als $Konstante gelesen werden.");
        }
    }
}

function findBareFunctionVariableIssues(line, lineIndex, items) {
    const regex = /\b([A-Za-z][A-Za-z0-9_]*)\b/g;
    let match;
    while ((match = regex.exec(line))) {
        const word = match[1];
        const lower = word.toLowerCase();
        if (VALUE_WORDS.has(lower) || COMMANDS.some(c => c.toLowerCase() === lower) || RUNTIME_FUNCTIONS.some(([f]) => f.toLowerCase() === lower)) continue;
        const after = line.slice(match.index + word.length);
        const before = line.slice(Math.max(0, match.index - 12), match.index);
        if (/^\s*\(/.test(after)) continue;
        if (/\b(FROM|TABLE|BASE|INSTANCE|ROOT|BRANCH|SHOW|GROW|DROP|DESCRIBE|PACK|SEED|RESHAPE|ERASE|DELETE|INDEX|CONSTRAINT)\s+$/i.test(before)) continue;
        if (/^\s*=/.test(after) || /\bWHERE\s+$/i.test(before) || /\bSORT\s+$/i.test(before)) continue;
        // Nur sehr vorsichtig warnen: klassischer Fehler ist ein nackter Wert rechts von =
        if (/=\s*$/.test(before)) pushIssue(items, lineIndex, match.index, word.length, "Variablen-Aufrufe müssen mit _ beginnen; Konstanten mit $. Literale als String in Anführungszeichen setzen.", vscode.DiagnosticSeverity.Warning);
    }
}

function pushIssue(items, line, start, len, message, severity = vscode.DiagnosticSeverity.Error) {
    items.push(new vscode.Diagnostic(new vscode.Range(line, Math.max(0, start), line, Math.max(0, start + len)), message, severity));
}

function deactivate() {}
module.exports = { activate, deactivate };
