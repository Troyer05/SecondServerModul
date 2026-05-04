<?php
declare(strict_types=1);
require_once __DIR__ . "/_shared.php";
require_once __DIR__ . "/greenql.logic.php";
?>

<!doctype html>
<html lang="de">
<head>
    <link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQL Studio</title>
    <link rel="stylesheet" href="assets/css/gbdb/greenql_ui.css">
</head>
<body>
    <?php gbdbui_nav('greenql'); ?>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand-card">
                <div class="eyebrow">greenbucket®</div>
                <h1>GreenQL Studio</h1>
                <p>GreenQL UI</p>
            </div>

            <div class="side-section">
                <div class="side-head">
                    <span>Basen</span>
                    <span class="badge"><?php echo count($snapshot['dbs']); ?></span>
                </div>

                <div class="db-tree">
                    <?php if (empty($snapshot['dbs'])) { ?>
                        <div class="empty-side">Noch keine Base vorhanden.</div>
                    <?php } ?>

                    <?php foreach ($snapshot['dbs'] as $db) { ?>
                        <?php $activeDb = $db === $currentDb; ?>
                        <div class="db-node <?php echo $activeDb ? 'is-active' : ''; ?>">
                            <a class="db-link" href="?tool=greenql&db=<?php echo urlencode($db); ?>">
                                <span><?php echo gql_h($db); ?></span>
                                <span class="mini-pill"><?php echo count(GBDB::listTables($db)); ?></span>
                            </a>

                            <?php if ($activeDb) { ?>
                                <div class="table-list">
                                    <?php $dbTables = GBDB::listTables($db); ?>
                                    <?php if (empty($dbTables)) { ?>
                                        <div class="empty-side small">Keine Tabellen.</div>
                                    <?php } ?>
                                    <?php foreach ($dbTables as $table) { ?>
                                        <a class="table-link <?php echo $table === $currentTable ? 'is-active' : ''; ?>" href="?tool=greenql&db=<?php echo urlencode($db); ?>&t=<?php echo urlencode($table); ?>">
                                            <?php echo gql_h($table); ?>
                                        </a>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="side-section cheat-sheet">
                <div class="side-head">
                    <span>GreenQL</span>
                </div>
                <div class="cheat-list">
                    <button class="ghost-chip" data-query="SHOW BASES;">SHOW BASES</button>
                    <button class="ghost-chip" data-query="SHOW TABLES;">SHOW TABLES</button>
                    <button class="ghost-chip" data-query="GROW BASE demo;">GROW BASE</button>
                    <button class="ghost-chip" data-query="GROW TABLE users (name, email, role);">GROW TABLE</button>
                    <button class="ghost-chip" data-query="PEEK <?php echo gql_h($currentTable !== '' ? $currentTable : 'users'); ?>;">PEEK</button>
                    <button class="ghost-chip" data-query="PICK * FROM <?php echo gql_h($currentTable !== '' ? $currentTable : 'users'); ?> LIMIT 20;">PICK</button>
                    <button class="ghost-chip" data-query="SEED <?php echo gql_h($currentTable !== '' ? $currentTable : 'users'); ?> WITH name='Markus', email='markus@example.com', role='admin';">SEED</button>
                    <button class="ghost-chip" data-query="RESHAPE <?php echo gql_h($currentTable !== '' ? $currentTable : 'users'); ?> WITH role='editor' WHERE id = 1;">RESHAPE</button>
                    <button class="ghost-chip" data-query="ERASE FROM <?php echo gql_h($currentTable !== '' ? $currentTable : 'users'); ?> WHERE id = 1;">ERASE</button>
                    <button class="ghost-chip" data-query="PACK <?php echo gql_h($currentTable !== '' ? $currentTable : 'users'); ?>;">PACK</button>
                </div>
            </div>
        </aside>

        <main class="workspace">
            <section class="topbar">
                <div>
                    <div class="eyebrow">Studio</div>
                    <h2><?php echo $currentDb !== '' ? gql_h($currentDb) : 'Keine Base gewählt'; ?></h2>
                    <p><?php echo $currentTable !== '' ? 'Aktive Tabelle: ' . gql_h($currentTable) : 'Wähle links eine Base oder starte im UI-Mode mit einer neuen Struktur.'; ?></p>
                </div>

                <div class="stat-row">
                    <div class="stat-card">
                        <span>Tabellen</span>
                        <strong id="statTables"><?php echo (int)$stats['tables']; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Zeilen</span>
                        <strong id="statRows"><?php echo (int)$stats['rows']; ?></strong>
                    </div>
                    <div class="stat-card glow">
                        <span>Mode</span>
                        <strong id="activeModeLabel">UI</strong>
                    </div>
                </div>
            </section>

            <section class="panel mode-toolbar">
                <div class="mode-toolbar-copy">
                    <div class="eyebrow">Workflow</div>
                    <h3>Zwischen UI Mode und Query Mode wechseln</h3>
                    <p>Query Mode: GreenQL Scripting / UI Mode: Clicking</p>
                </div>
                <div class="mode-switch" id="modeSwitch">
                    <button type="button" class="mode-btn is-active" data-mode-switch="ui">UI Mode</button>
                    <button type="button" class="mode-btn" data-mode-switch="query">Query Mode</button>
                </div>
            </section>

            <section class="mode-view is-active" data-mode-view="ui">
                <section class="content-grid manager-grid">
                    <div class="panel manager-panel">
                        <div class="panel-head">
                            <div>
                                <div class="eyebrow">UI Manager</div>
                                <h3>Forge</h3>
                            </div>
                        </div>

                        <div id="managerBox"><?php echo $snapshot['manager_html']; ?></div>
                    </div>

                    <div class="panel ui-help-panel">
                        <div class="panel-head">
                            <div>
                                <div class="eyebrow">UI Flow</div>
                                <h3>So arbeitest du hier</h3>
                            </div>
                        </div>

                        <div class="hint-box flow-box">
                            <div class="hint-title">Empfohlener Ablauf</div>
                            <code>1. Base anlegen oder links auswählen</code>
                            <code>2. Tabelle mit Spalten definieren</code>
                            <code>3. Entries anlegen, editieren, löschen</code>
                            <code>4. Unten Live-Preview und Schema prüfen</code>
                            <code>5. Für Serienaktionen in den Query Mode wechseln</code>
                        </div>
                    </div>
                </section>
            </section>

            <section class="mode-view" data-mode-view="query">
                <section class="hero-grid">
                    <div class="panel command-panel">
                        <div class="panel-head">
                            <div>
                                <div class="eyebrow">Console</div>
                                <h3>Query Deck</h3>
                            </div>
                            <button class="secondary-btn" id="runExamples">Beispiel laden</button>
                        </div>

                        <div class="editor-wrap">
                            <textarea id="queryEditor" spellcheck="false"><?php echo gql_h($exampleQuery); ?></textarea>
                        </div>

                        <div class="action-row">
                            <button class="primary-btn" id="runQueryBtn">Ausführen</button>
                            <button class="secondary-btn" id="clearQueryBtn">Leeren</button>
                        </div>

                        <form id="gqlUploadForm" class="upload-box" enctype="multipart/form-data">
                            <label class="field-block compact">
                                <span>.gql Script hochladen</span>
                                <input type="file" name="gql_file" id="gqlFileInput" accept=".gql">
                            </label>
                            <div class="action-row tight">
                                <button type="submit" class="secondary-btn">Upload + Ausführen</button>
                            </div>
                        </form>

                        <div class="hint-box">
                            <div class="hint-title">Syntax-Idee</div>
                            <code>ROOT demo;</code>
                            <code>GROW TABLE users (name, email, role);</code>
                            <code>PICK name, email FROM users WHERE role ~= 'admin' SORT name ASC LIMIT 10;</code>
                            <code>declare _status = "aktiv";</code>
                            <code># Kommentare starten mit #</code>
                        </div>
                    </div>

                    <div class="panel result-panel">
                        <div class="panel-head">
                            <div>
                                <div class="eyebrow">Output</div>
                                <h3>Result Stream</h3>
                            </div>
                        </div>

                        <div id="messageStack" class="message-stack"></div>
                        <div id="resultBox" class="result-box">
                            <div class="empty-state">Noch keine Query ausgeführt.</div>
                        </div>
                    </div>
                </section>
            </section>

            <section class="content-grid data-grid">
                <div class="panel preview-panel">
                    <div class="panel-head">
                        <div>
                            <div class="eyebrow">Preview</div>
                            <h3>Live-Daten</h3>
                        </div>
                        <?php if ($currentTable !== '') { ?>
                            <a class="secondary-btn link-btn" href="?tool=greenql&db=<?php echo urlencode($currentDb); ?>&t=<?php echo urlencode($currentTable); ?>">Aktualisieren</a>
                        <?php } ?>
                    </div>

                    <div id="previewBox"><?php echo $snapshot['preview_html']; ?></div>
                </div>

                <div class="panel schema-panel">
                    <div class="panel-head">
                        <div>
                            <div class="eyebrow">Schema</div>
                            <h3>Struktur</h3>
                        </div>
                    </div>

                    <div id="schemaBox"><?php echo $snapshot['schema_html']; ?></div>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.GreenQLState = {
            currentDb: <?php echo json_encode($currentDb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
            currentTable: <?php echo json_encode($currentTable, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
            mode: <?php echo json_encode(($_GET['mode'] ?? 'ui') === 'query' ? 'query' : 'ui', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        };
    </script>
    <script src="assets/js/greenql_ui.js"></script>
</body>
</html>
