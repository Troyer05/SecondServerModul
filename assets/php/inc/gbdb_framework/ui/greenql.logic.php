<?php
function gql_json(array $data): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function gql_clean_name(string $name): string {
    $name = trim($name);
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?? '';
}

function gql_h(mixed $value): string {
    if (is_bool($value)) $value = $value ? 'true' : 'false';

    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gql_unquote(string $value): mixed {
    $value = trim($value);

    if (strlen($value) >= 2) {
        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return stripcslashes(substr($value, 1, -1));
        }
    }

    $low = strtolower($value);

    if ($low === 'true') return true;
    if ($low === 'false') return false;
    if ($low === 'null') return null;
    if (is_numeric($value)) return $value + 0;

    return $value;
}

function gql_parse_input_value(string $value): mixed {
    $trim = trim($value);
    if ($trim === '') return '';
    return gql_unquote($trim);
}

function gql_parse_list(string $raw): array {
    $parts = preg_split('/\s*,\s*/', trim($raw));
    $out = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') continue;
        $out[] = gql_clean_name($part);
    }

    return array_values(array_filter($out));
}

function gql_parse_assignments(string $raw): array {
    $raw = trim($raw);

    if ($raw === '') return [];

    preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*("(?:\\\\.|[^"])*"|\'(?:\\\\.|[^\'])*\'|[^,]+)(?:,|$)/', $raw, $matches, PREG_SET_ORDER);

    $out = [];

    foreach ($matches as $match) {
        $key = gql_clean_name($match[1]);
        if ($key === '' || $key === 'id') continue;
        $out[$key] = gql_unquote(trim($match[2]));
    }

    return $out;
}

function gql_parse_where(string $raw): ?array {
    $raw = trim($raw);

    if ($raw === '') return null;

    if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(==|=|!=|>=|<=|>|<|~=)\s*(.+)$/', $raw, $m)) {
        return null;
    }

    return [
        'field' => gql_clean_name($m[1]),
        'op' => $m[2],
        'value' => gql_unquote($m[3])
    ];
}

function gql_row_match(array $row, ?array $where): bool {
    if ($where === null) return true;

    $field = $where['field'];
    $op = $where['op'];
    $value = $where['value'];
    $left = $row[$field] ?? null;

    switch ($op) {
        case '=':
        case '==':
            return $left == $value;
        case '!=':
            return $left != $value;
        case '>':
            return $left > $value;
        case '<':
            return $left < $value;
        case '>=':
            return $left >= $value;
        case '<=':
            return $left <= $value;
        case '~=':
            return mb_stripos((string)$left, (string)$value) !== false;
    }

    return false;
}

function gql_sort_rows(array &$rows, ?string $field, string $dir = 'ASC'): void {
    if ($field === null || $field === '') return;

    usort($rows, function ($a, $b) use ($field, $dir) {
        $av = $a[$field] ?? '';
        $bv = $b[$field] ?? '';

        if (is_numeric($av) && is_numeric($bv)) {
            $cmp = ($av <=> $bv);
        } else {
            $cmp = strnatcasecmp((string)$av, (string)$bv);
        }

        return strtoupper($dir) === 'DESC' ? -$cmp : $cmp;
    });
}

function gql_get_rows(string $db, string $table): array {
    $rows = GBDB::getData($db, $table);
    return is_array($rows) ? $rows : [];
}

function gql_get_table_keys(string $db, string $table): array {
    $keys = GBDB::getKeys($db, $table);

    if (!empty($keys)) return $keys;

    $rows = gql_get_rows($db, $table);

    if (!empty($rows) && is_array($rows[0])) {
        return array_keys($rows[0]);
    }

    return [];
}

function gql_select_rows(string $db, string $table, array $columns = ['*'], ?array $where = null, ?string $sortField = null, string $sortDir = 'ASC', ?int $limit = null): array {
    $rows = gql_get_rows($db, $table);

    $rows = array_values(array_filter($rows, function ($row) use ($where) {
        return is_array($row) && gql_row_match($row, $where);
    }));

    gql_sort_rows($rows, $sortField, $sortDir);

    if ($limit !== null && $limit >= 0) {
        $rows = array_slice($rows, 0, $limit);
    }

    $keys = gql_get_table_keys($db, $table);

    if ($columns !== ['*']) {
        $rows = array_map(function ($row) use ($columns) {
            $tmp = [];
            foreach ($columns as $col) {
                $tmp[$col] = $row[$col] ?? '';
            }
            return $tmp;
        }, $rows);

        $keys = $columns;
    }

    return ['keys' => $keys, 'rows' => $rows];
}

function gql_get_row_by_id(string $db, string $table, int $id): array {
    $row = GBDB::getData($db, $table, true, 'id', $id);
    return is_array($row) ? $row : [];
}

function gql_db_stats(string $db): array {
    $tables = GBDB::listTables($db);
    $rows = 0;

    foreach ($tables as $table) {
        $data = GBDB::getData($db, $table);
        if (is_array($data)) $rows += count($data);
    }

    return [
        'tables' => count($tables),
        'rows' => $rows
    ];
}

function gql_render_table_html(array $keys, array $rows, bool $withActions = false, string $db = '', string $table = ''): string {
    if (empty($keys)) return '<div class="empty-state">Keine Daten gefunden.</div>';

    $html = '<div class="result-table-wrap"><table class="data-table"><thead><tr>';

    foreach ($keys as $key) {
        $html .= '<th>' . gql_h($key) . '</th>';
    }

    if ($withActions) {
        $html .= '<th class="actions-col">Aktionen</th>';
    }

    $html .= '</tr></thead><tbody>';

    if (empty($rows)) {
        $html .= '<tr><td colspan="' . count($keys) . ($withActions ? 1 : 0) . '" class="empty-cell">Keine Treffer.</td></tr>';
    } else {
        foreach ($rows as $row) {
            $html .= '<tr>';

            foreach ($keys as $key) {
                $value = $row[$key] ?? '';
                $html .= '<td>' . gql_h($value) . '</td>';
            }

            if ($withActions) {
                $id = isset($row['id']) ? (int)$row['id'] : 0;
                $html .= '<td class="row-actions">';

                if ($id > 0) {
                    $html .= '<button class="table-mini-btn" data-entry-edit="' . $id . '">Edit</button>';
                    $html .= '<button class="table-mini-btn danger" data-entry-delete="' . $id . '">Delete</button>';
                } else {
                    $html .= '<span class="muted-inline">n/a</span>';
                }

                $html .= '</td>';
            }

            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table></div>';
    return $html;
}


function gql_render_result_html(array $result): string {
    $html = '';
    $outputs = $result['outputs'] ?? [];

    if (is_array($outputs) && !empty($outputs)) {
        $html .= '<div class="output-stream">';

        foreach ($outputs as $entry) {
            $value = is_array($entry) ? ($entry['value'] ?? '') : $entry;

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            $html .= '<div class="output-entry"><div class="output-label">OUTPUT</div><pre>' . gql_h((string)$value) . '</pre></div>';
        }

        $html .= '</div>';
    }

    $keys = $result['keys'] ?? [];
    $rows = $result['rows'] ?? [];

    if (is_array($keys) && is_array($rows) && !empty($keys)) {
        $html .= gql_render_table_html($keys, $rows);
    }

    return $html !== '' ? $html : '<div class="empty-state">Kein Tabellenergebnis.</div>';
}

function gql_render_schema_html(string $db, string $table): string {
    $keys = gql_get_table_keys($db, $table);

    if (empty($keys)) return '<div class="empty-state">Keine Spalten gefunden.</div>';

    $html = '<div class="schema-list">';

    foreach ($keys as $key) {
        $tag = $key === 'id' ? 'Auto-ID' : 'Feld';
        $html .= '<div class="schema-item">';
        $html .= '<div><strong>' . gql_h($key) . '</strong></div>';
        $html .= '<span>' . $tag . '</span>';
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}

function gql_build_entry_fields(string $db, string $table, array $record = []): string {
    $keys = gql_get_table_keys($db, $table);

    if (empty($keys)) {
        return '<div class="empty-state">Keine Felder verfügbar.</div>';
    }

    $html = '';

    foreach ($keys as $key) {
        if ($key === 'id') continue;

        $value = $record[$key] ?? '';
        $html .= '<label class="field-block">';
        $html .= '<span>' . gql_h($key) . '</span>';
        $html .= '<textarea name="fields[' . gql_h($key) . ']" rows="2" placeholder="' . gql_h($key) . '">' . gql_h((string)$value) . '</textarea>';
        $html .= '</label>';
    }

    return $html !== '' ? $html : '<div class="empty-state">Keine editierbaren Felder gefunden.</div>';
}

function gql_render_entry_form_html(string $db, string $table, array $record = []): string {
    if ($db === '' || $table === '') {
        return '<div class="empty-state">Wähle zuerst eine Tabelle.</div>';
    }

    $id = isset($record['id']) ? (int)$record['id'] : 0;
    $title = $id > 0 ? 'Entry editieren' : 'Entry anlegen';
    $html = '<form class="manager-form" id="entryManagerForm">';
    $html .= '<input type="hidden" name="entry_id" value="' . $id . '">';
    $html .= '<div class="form-hero">';
    $html .= '<div><div class="eyebrow">Entry</div><h4>' . $title . '</h4></div>';
    $html .= '<button type="button" class="secondary-btn small-btn" id="newEntryBtn">Neuer Entry</button>';
    $html .= '</div>';

    if ($id > 0) {
        $html .= '<div class="soft-note">Bearbeite Datensatz mit ID <strong>#' . $id . '</strong>.</div>';
    }

    $html .= '<div class="field-grid">';
    $html .= gql_build_entry_fields($db, $table, $record);
    $html .= '</div>';
    $html .= '<div class="action-row tight">';
    $html .= '<button type="submit" class="primary-btn">Entry speichern</button>';

    if ($id > 0) {
        $html .= '<button type="button" class="secondary-btn" id="deleteCurrentEntryBtn" data-entry-delete="' . $id . '">Entry löschen</button>';
    }

    $html .= '</div>';
    $html .= '</form>';

    return $html;
}

function gql_render_manager_html(string $db = '', string $table = '', int $entryId = 0): string {
    $dbs = GBDB::listDBs();
    $tables = $db !== '' ? GBDB::listTables($db) : [];
    $record = ($db !== '' && $table !== '' && $entryId > 0) ? gql_get_row_by_id($db, $table, $entryId) : [];
    $html = '<div class="manager-stack">';
    $html .= '<form class="manager-form" id="dbManagerForm">';
    $html .= '<div class="form-hero"><div><div class="eyebrow">Base</div><h4>Basen verwalten</h4></div></div>';
    $html .= '<label class="field-block"><span>Neue Base</span><input type="text" name="db_name" placeholder="z. B. museumqr"></label>';
    $html .= '<div class="action-row tight"><button type="submit" class="primary-btn">Base erstellen</button></div>';

    if (!empty($dbs)) {
        $html .= '<div class="pill-cloud">';

        foreach ($dbs as $item) {
            $html .= '<button type="button" class="pill-btn' . ($item === $db ? ' is-active' : '') . '" data-root-db="' . gql_h($item) . '">' . gql_h($item) . '</button>';
        }

        $html .= '</div>';
    }

    if ($db !== '') {
        $html .= '<div class="danger-zone">';
        $html .= '<div><strong>Aktive Base:</strong> ' . gql_h($db) . '</div>';
        $html .= '<button type="button" class="secondary-btn danger-btn" id="deleteDbBtn">Base löschen</button>';
        $html .= '</div>';
    }

    $html .= '</form>';
    $html .= '<form class="manager-form" id="tableManagerForm">';
    $html .= '<div class="form-hero"><div><div class="eyebrow">Table</div><h4>Tabellen verwalten</h4></div></div>';

    if ($db === '') {
        $html .= '<div class="empty-state">Lege zuerst eine Base an oder wähle links eine Base.</div>';
    } else {
        $html .= '<label class="field-block"><span>Tabellenname</span><input type="text" name="table_name" value="' . ($table !== '' ? gql_h($table) : '') . '" placeholder="z. B. exhibits"></label>';
        $html .= '<label class="field-block"><span>Spalten</span><textarea name="table_cols" rows="3" placeholder="title, artist, year, status">';
        $cols = gql_get_table_keys($db, $table);
        $cols = array_values(array_filter($cols, fn($col) => $col !== 'id'));
        $html .= gql_h(implode(', ', $cols));
        $html .= '</textarea></label>';
        $html .= '<div class="action-row tight">';
        $html .= '<button type="submit" class="primary-btn">' . ($table !== '' ? 'Tabelle umbauen' : 'Tabelle erstellen') . '</button>';

        if ($table !== '') {
            $html .= '<button type="button" class="secondary-btn danger-btn" id="deleteTableBtn">Tabelle löschen</button>';
        }

        $html .= '</div>';

        if (!empty($tables)) {
            $html .= '<div class="pill-cloud">';

            foreach ($tables as $item) {
                $html .= '<button type="button" class="pill-btn' . ($item === $table ? ' is-active' : '') . '" data-open-table="' . gql_h($item) . '">' . gql_h($item) . '</button>';
            }

            $html .= '</div>';
        }

        $html .= '<div class="soft-note">Beim Umbauen wird die Tabelle mit den angegebenen Spalten neu aufgebaut. Vorhandene Daten bleiben erhalten, passende Felder werden übernommen.</div>';
    }
    
    $html .= '</form>';
    $html .= '<div class="manager-form">';
    $html .= gql_render_entry_form_html($db, $table, $record);
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function gql_snapshot(string $db = '', string $table = '', int $entryId = 0): array {
    $dbs = GBDB::listDBs();
    $db = $db !== '' ? $db : ($dbs[0] ?? '');
    $tables = $db !== '' ? GBDB::listTables($db) : [];
    $table = $table !== '' ? $table : ($tables[0] ?? '');
    $stats = $db !== '' ? gql_db_stats($db) : ['tables' => 0, 'rows' => 0];
    $preview = ['keys' => [], 'rows' => []];

    if ($db !== '' && $table !== '') {
        $preview = gql_select_rows($db, $table, ['*'], null, 'id', 'ASC', 12);
    }

    return [
        'dbs' => $dbs,
        'db' => $db,
        'tables' => $tables,
        'table' => $table,
        'stats' => $stats,
        'preview_html' => ($db !== '' && $table !== '') ? gql_render_table_html($preview['keys'], $preview['rows'], true, $db, $table) : '<div class="empty-state">Keine Tabelle ausgewählt.</div>',
        'schema_html' => ($db !== '' && $table !== '') ? gql_render_schema_html($db, $table) : '<div class="empty-state">Keine Tabelle ausgewählt.</div>',
        'manager_html' => gql_render_manager_html($db, $table, $entryId)
    ];
}

function gql_rebuild_table(string $db, string $table, array $cols): bool {
    $table = gql_clean_name($table);
    $cols = array_values(array_filter(array_map('gql_clean_name', $cols)));
    $cols = array_values(array_filter($cols, fn($col) => $col !== '' && $col !== 'id'));

    if ($db === '' || $table === '' || empty($cols)) return false;

    if (!in_array($table, GBDB::listTables($db), true)) {
        return GBDB::createTable($db, $table, $cols);
    }

    $rows = gql_get_rows($db, $table);
    $tmp = '__gql_tmp_' . $table . '_' . date('His') . '_' . mt_rand(1000, 9999);

    if (!GBDB::createTable($db, $tmp, $cols)) return false;

    foreach ($rows as $row) {
        if (!is_array($row)) continue;

        $insert = [];

        foreach ($cols as $col) {
            $insert[$col] = $row[$col] ?? '';
        }

        GBDB::insertData($db, $tmp, $insert);
    }

    if (!GBDB::deleteTable($db, $table)) {
        GBDB::deleteTable($db, $tmp);
        return false;
    }

    if (!GBDB::createTable($db, $table, $cols)) {
        return false;
    }

    $tmpRows = gql_get_rows($db, $tmp);

    foreach ($tmpRows as $row) {
        if (!is_array($row)) continue;

        $insert = [];

        foreach ($cols as $col) {
            $insert[$col] = $row[$col] ?? '';
        }

        GBDB::insertData($db, $table, $insert);
    }

    GBDB::deleteTable($db, $tmp);
    return true;
}

function gql_execute_command(string $command, array &$ctx): array {
    return GreenQL::command($command, $ctx);
}

if (isset($_POST['greenql_action'])) {
    $ctx = [
        'db' => gql_clean_name($_POST['current_db'] ?? ''),
        'table' => gql_clean_name($_POST['current_table'] ?? '')
    ];

    $action = (string)$_POST['greenql_action'];

    if ($action === 'snapshot') {
        gql_json(gql_snapshot($ctx['db'], $ctx['table']));
    }

    if ($action === 'run_query') {
        $script = trim((string)($_POST['query'] ?? ''));
        $result = GBDB::query($script, $ctx);
        $ctx = $result['ctx'] ?? $ctx;
        $snap = gql_snapshot($ctx['db'] ?? '', $ctx['table'] ?? '');

        gql_json([
            'ok' => (bool)($result['ok'] ?? false),
            'messages' => $result['messages'] ?? [],
            'result_html' => gql_render_result_html($result),
            'snapshot' => $snap,
            'ctx' => $ctx,
            'refresh' => (bool)($result['refresh'] ?? false)
        ]);
    }

    if ($action === 'upload_gql') {
        if (!isset($_FILES['gql_file']) || !is_array($_FILES['gql_file'])) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Keine .gql Datei hochgeladen.']]]);
        }

        $file = $_FILES['gql_file'];
        $name = (string)($file['name'] ?? '');
        $tmp = (string)($file['tmp_name'] ?? '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($ext !== 'gql') {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Es sind nur .gql Dateien erlaubt.']]]);
        }

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Upload konnte nicht gelesen werden.']]]);
        }

        $script = file_get_contents($tmp);

        if ($script === false) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Datei konnte nicht gelesen werden.']]]);
        }

        $result = GBDB::query($script, $ctx);
        $ctx = $result['ctx'] ?? $ctx;
        $snap = gql_snapshot($ctx['db'] ?? '', $ctx['table'] ?? '');
        $messages = $result['messages'] ?? [];
        
        array_unshift($messages, ['ok' => true, 'text' => '.gql Script ausgeführt: ' . $name]);

        gql_json([
            'ok' => (bool)($result['ok'] ?? false),
            'messages' => $messages,
            'result_html' => gql_render_result_html($result),
            'snapshot' => $snap,
            'ctx' => $ctx,
            'uploaded_script' => $script,
            'refresh' => (bool)($result['refresh'] ?? false)
        ]);
    }

    if ($action === 'save_db') {
        $db = gql_clean_name((string)($_POST['db_name'] ?? ''));

        if ($db === '') {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Base-Name fehlt.']]]);
        }

        $ok = GBDB::createDatabase($db);

        if (!$ok) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Base konnte nicht erstellt werden. Vielleicht existiert sie schon.']]]);
        }

        $ctx['db'] = $db;
        $ctx['table'] = '';

        gql_json([
            'ok' => true,
            'messages' => [['ok' => true, 'text' => 'Base erstellt: ' . $db]],
            'snapshot' => gql_snapshot($ctx['db'], $ctx['table']),
            'ctx' => $ctx,
            'refresh' => true
        ]);
    }

    if ($action === 'delete_db') {
        $db = gql_clean_name((string)($_POST['db_name'] ?? $ctx['db']));

        if ($db === '') {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Keine Base aktiv.']]]);
        }

        $tables = GBDB::listTables($db);

        foreach ($tables as $table) {
            GBDB::deleteTable($db, $table);
        }

        $ok = GBDB::deleteDatabase($db);

        if (!$ok) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Base konnte nicht gelöscht werden.']]]);
        }

        $ctx['db'] = '';
        $ctx['table'] = '';

        gql_json([
            'ok' => true,
            'messages' => [['ok' => true, 'text' => 'Base gelöscht: ' . $db]],
            'snapshot' => gql_snapshot('', ''),
            'ctx' => $ctx,
            'refresh' => true
        ]);
    }

    if ($action === 'save_table') {
        $db = gql_clean_name((string)($_POST['db_name'] ?? $ctx['db']));
        $table = gql_clean_name((string)($_POST['table_name'] ?? ''));
        $cols = gql_parse_list((string)($_POST['table_cols'] ?? ''));

        if ($db === '' || $table === '' || empty($cols)) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Base, Tabellenname oder Spalten fehlen.']]]);
        }

        $exists = in_array($table, GBDB::listTables($db), true);
        $ok = $exists ? gql_rebuild_table($db, $table, $cols) : GBDB::createTable($db, $table, $cols);

        if (!$ok) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Tabelle konnte nicht gespeichert werden.']]]);
        }

        $ctx['db'] = $db;
        $ctx['table'] = $table;

        gql_json([
            'ok' => true,
            'messages' => [['ok' => true, 'text' => $exists ? 'Tabelle umgebaut: ' . $table : 'Tabelle erstellt: ' . $table]],
            'snapshot' => gql_snapshot($ctx['db'], $ctx['table']),
            'ctx' => $ctx,
            'refresh' => true
        ]);
    }

    if ($action === 'delete_table') {
        $db = gql_clean_name((string)($_POST['db_name'] ?? $ctx['db']));
        $table = gql_clean_name((string)($_POST['table_name'] ?? $ctx['table']));

        if ($db === '' || $table === '') {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Keine Tabelle aktiv.']]]);
        }

        $ok = GBDB::deleteTable($db, $table);

        if (!$ok) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Tabelle konnte nicht gelöscht werden.']]]);
        }

        $tables = GBDB::listTables($db);
        $ctx['table'] = $tables[0] ?? '';

        gql_json([
            'ok' => true,
            'messages' => [['ok' => true, 'text' => 'Tabelle gelöscht: ' . $table]],
            'snapshot' => gql_snapshot($db, $ctx['table']),
            'ctx' => $ctx,
            'refresh' => true
        ]);
    }

    if ($action === 'load_entry_form') {
        $db = gql_clean_name((string)($_POST['db_name'] ?? $ctx['db']));
        $table = gql_clean_name((string)($_POST['table_name'] ?? $ctx['table']));
        $id = (int)($_POST['entry_id'] ?? 0);

        gql_json([
            'ok' => true,
            'entry_form_html' => gql_render_entry_form_html($db, $table, $id > 0 ? gql_get_row_by_id($db, $table, $id) : [])
        ]);
    }

    if ($action === 'save_entry') {
        $db = gql_clean_name((string)($_POST['db_name'] ?? $ctx['db']));
        $table = gql_clean_name((string)($_POST['table_name'] ?? $ctx['table']));
        $id = (int)($_POST['entry_id'] ?? 0);
        $fields = $_POST['fields'] ?? [];

        if ($db === '' || $table === '') {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Keine Tabelle aktiv.']]]);
        }

        $payload = [];

        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                $field = gql_clean_name((string)$key);
                if ($field === '' || $field === 'id') continue;
                $payload[$field] = gql_parse_input_value((string)$value);
            }
        }

        if (empty($payload)) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Keine Entry-Daten erkannt.']]]);
        }

        if ($id > 0) {
            $ok = GBDB::editData($db, $table, 'id', $id, $payload);

            if (!$ok) {
                gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Entry konnte nicht aktualisiert werden.']]]);
            }

            $text = 'Entry aktualisiert: #' . $id;
        } else {
            $newId = GBDB::insertData($db, $table, $payload);

            if ($newId <= 0) {
                gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Entry konnte nicht angelegt werden.']]]);
            }

            $id = $newId;
            $text = 'Entry angelegt: #' . $id;
        }

        gql_json([
            'ok' => true,
            'messages' => [['ok' => true, 'text' => $text]],
            'snapshot' => gql_snapshot($db, $table, $id),
            'ctx' => ['db' => $db, 'table' => $table],
            'refresh' => true
        ]);
    }

    if ($action === 'delete_entry') {
        $db = gql_clean_name((string)($_POST['db_name'] ?? $ctx['db']));
        $table = gql_clean_name((string)($_POST['table_name'] ?? $ctx['table']));
        $id = (int)($_POST['entry_id'] ?? 0);

        if ($db === '' || $table === '' || $id <= 0) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Entry konnte nicht gefunden werden.']]]);
        }

        $ok = GBDB::deleteData($db, $table, 'id', $id);

        if (!$ok) {
            gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Entry konnte nicht gelöscht werden.']]]);
        }

        gql_json([
            'ok' => true,
            'messages' => [['ok' => true, 'text' => 'Entry gelöscht: #' . $id]],
            'snapshot' => gql_snapshot($db, $table, 0),
            'ctx' => ['db' => $db, 'table' => $table],
            'refresh' => true
        ]);
    }

    gql_json(['ok' => false, 'messages' => [['ok' => false, 'text' => 'Ungültige Aktion.']]]);
}

$currentDb = gql_clean_name($_GET['db'] ?? '');
$currentTable = gql_clean_name($_GET['t'] ?? '');
$snapshot = gql_snapshot($currentDb, $currentTable);
$currentDb = $snapshot['db'];
$currentTable = $snapshot['table'];
$stats = $snapshot['stats'];

$exampleQuery = $currentTable !== ''
    ? 'PEEK ' . $currentTable . ';'
    : 'SHOW BASES;';

?>
