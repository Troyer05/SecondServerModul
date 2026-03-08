<?php

class GreenQL {

    public static function cleanName(string $name): string {
        $name = trim($name);
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?? '';
    }

    public static function unquote(string $value): mixed {
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

    public static function parseList(string $raw): array {
        $parts = preg_split('/\s*,\s*/', trim($raw));
        $out = [];

        foreach ($parts as $part) {
            $part = self::cleanName((string)$part);
            if ($part === '') continue;
            $out[] = $part;
        }

        return array_values(array_filter($out));
    }

    public static function parseAssignments(string $raw): array {
        $raw = trim($raw);

        if ($raw === '') return [];

        preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*("(?:\\\\.|[^"])*"|\'(?:\\\\.|[^\'])*\'|[^,]+)(?:,|$)/', $raw, $matches, PREG_SET_ORDER);

        $out = [];

        foreach ($matches as $match) {
            $key = self::cleanName((string)$match[1]);
            if ($key === '' || $key === 'id') continue;
            $out[$key] = self::unquote(trim((string)$match[2]));
        }

        return $out;
    }

    public static function parseWhere(string $raw): ?array {
        $raw = trim($raw);

        if ($raw === '') return null;

        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(==|=|!=|>=|<=|>|<|~=)\s*(.+)$/', $raw, $m)) {
            return null;
        }

        return [
            'field' => self::cleanName((string)$m[1]),
            'op' => (string)$m[2],
            'value' => self::unquote((string)$m[3])
        ];
    }

    public static function rowMatch(array $row, ?array $where): bool {
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

    public static function sortRows(array &$rows, ?string $field, string $dir = 'ASC'): void {
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

    public static function getRows(string $db, string $table): array {
        $rows = GBDB::getData($db, $table);
        return is_array($rows) ? $rows : [];
    }

    public static function getTableKeys(string $db, string $table): array {
        $keys = GBDB::getKeys($db, $table);

        if (!empty($keys)) return $keys;

        $rows = self::getRows($db, $table);

        if (!empty($rows) && is_array($rows[0])) {
            return array_keys($rows[0]);
        }

        return [];
    }

    public static function selectRows(string $db, string $table, array $columns = ['*'], ?array $where = null, ?string $sortField = null, string $sortDir = 'ASC', ?int $limit = null): array {
        $rows = self::getRows($db, $table);

        $rows = array_values(array_filter($rows, function ($row) use ($where) {
            return is_array($row) && self::rowMatch($row, $where);
        }));

        self::sortRows($rows, $sortField, $sortDir);

        if ($limit !== null && $limit >= 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        $keys = self::getTableKeys($db, $table);

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

        return [
            'keys' => $keys,
            'rows' => $rows
        ];
    }

    public static function stats(string $db): array {
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

    public static function command(string $command, array &$ctx = []): array {
        $command = trim($command);

        if ($command === '') {
            return [
                'ok' => true,
                'message' => ''
            ];
        }

        if (str_ends_with($command, ';')) {
            $command = rtrim(substr($command, 0, -1));
        }

        if (preg_match('/^ROOT\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $ctx['db'] = self::cleanName((string)$m[1]);
            $ctx['table'] = '';

            return [
                'ok' => true,
                'message' => 'Base fokussiert: ' . $ctx['db'],
                'refresh' => true,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^BRANCH\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $ctx['table'] = self::cleanName((string)$m[1]);

            return [
                'ok' => true,
                'message' => 'Tabelle fokussiert: ' . $ctx['table'],
                'refresh' => true,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^SHOW\s+BASES$/i', $command)) {
            $rows = [];

            foreach (GBDB::listDBs() as $db) {
                $stats = self::stats($db);
                $rows[] = [
                    'base' => $db,
                    'tables' => $stats['tables'],
                    'rows' => $stats['rows']
                ];
            }

            return [
                'ok' => true,
                'message' => count($rows) . ' Basen gefunden.',
                'keys' => ['base', 'tables', 'rows'],
                'rows' => $rows,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^SHOW\s+TABLES(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $db = self::cleanName((string)($m[1] ?? ($ctx['db'] ?? '')));

            if ($db === '') {
                return [
                    'ok' => false,
                    'message' => 'Keine Base aktiv.',
                    'ctx' => $ctx
                ];
            }

            $rows = [];

            foreach (GBDB::listTables($db) as $table) {
                $rows[] = [
                    'table' => $table,
                    'fields' => count(self::getTableKeys($db, $table)),
                    'rows' => count(self::getRows($db, $table))
                ];
            }

            $ctx['db'] = $db;

            return [
                'ok' => true,
                'message' => count($rows) . ' Tabellen in ' . $db . '.',
                'keys' => ['table', 'fields', 'rows'],
                'rows' => $rows,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^GROW\s+BASE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $db = self::cleanName((string)$m[1]);

            if ($db === '') {
                return [
                    'ok' => false,
                    'message' => 'Ungültiger Base-Name.',
                    'ctx' => $ctx
                ];
            }

            $ok = GBDB::createDatabase($db);

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Base konnte nicht erstellt werden.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = '';

            return [
                'ok' => true,
                'message' => 'Base erstellt: ' . $db,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^DROP\s+BASE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $db = self::cleanName((string)$m[1]);
            $ok = GBDB::deleteDatabase($db);

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Base konnte nicht gelöscht werden. Sie muss leer sein.',
                    'ctx' => $ctx
                ];
            }

            if (($ctx['db'] ?? '') === $db) {
                $ctx['db'] = '';
                $ctx['table'] = '';
            }

            return [
                'ok' => true,
                'message' => 'Base gelöscht: ' . $db,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^GROW\s+TABLE\s+([a-zA-Z0-9_\-]+)\s*\(([^\)]+)\)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $cols = self::parseList((string)$m[2]);
            $db = self::cleanName((string)($m[3] ?? ($ctx['db'] ?? '')));

            if ($db === '') {
                return [
                    'ok' => false,
                    'message' => 'Keine Base aktiv.',
                    'ctx' => $ctx
                ];
            }

            if ($table === '' || empty($cols)) {
                return [
                    'ok' => false,
                    'message' => 'Tabelle oder Felder ungültig.',
                    'ctx' => $ctx
                ];
            }

            $ok = GBDB::createTable($db, $table, $cols);

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Tabelle konnte nicht erstellt werden.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;

            return [
                'ok' => true,
                'message' => 'Tabelle erstellt: ' . $table,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^DROP\s+TABLE\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $db = self::cleanName((string)($m[2] ?? ($ctx['db'] ?? '')));

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            $ok = GBDB::deleteTable($db, $table);

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Tabelle konnte nicht gelöscht werden.',
                    'ctx' => $ctx
                ];
            }

            if (($ctx['db'] ?? '') === $db && ($ctx['table'] ?? '') === $table) {
                $tables = GBDB::listTables($db);
                $ctx['table'] = $tables[0] ?? '';
            }

            return [
                'ok' => true,
                'message' => 'Tabelle gelöscht: ' . $table,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^DESCRIBE\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $db = self::cleanName((string)($m[2] ?? ($ctx['db'] ?? '')));

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            $keys = self::getTableKeys($db, $table);
            $rows = [];

            foreach ($keys as $key) {
                $rows[] = [
                    'field' => $key,
                    'kind' => $key === 'id' ? 'auto' : 'mixed'
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;

            return [
                'ok' => true,
                'message' => 'Schema geladen: ' . $table,
                'keys' => ['field', 'kind'],
                'rows' => $rows,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^PACK\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $db = self::cleanName((string)($m[2] ?? ($ctx['db'] ?? '')));

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            $ok = GBDB::compactTable($db, $table);

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Compact fehlgeschlagen.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;

            return [
                'ok' => true,
                'message' => 'Tabelle gepackt: ' . $table,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^PEEK\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $db = self::cleanName((string)($m[2] ?? ($ctx['db'] ?? '')));
            $limit = isset($m[3]) ? (int)$m[3] : 50;

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;
            $result = self::selectRows($db, $table, ['*'], null, 'id', 'ASC', $limit);

            return [
                'ok' => true,
                'message' => 'Vorschau: ' . $table,
                'keys' => $result['keys'],
                'rows' => $result['rows'],
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^PICK\s+(.+?)\s+FROM\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+WHERE\s+(.+?))?(?:\s+SORT\s+([a-zA-Z0-9_\-]+)\s+(ASC|DESC))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $m)) {
            $colsRaw = trim((string)$m[1]);
            $table = self::cleanName((string)$m[2]);
            $db = self::cleanName((string)($m[3] ?? ($ctx['db'] ?? '')));
            $where = isset($m[4]) ? self::parseWhere((string)$m[4]) : null;
            $sortField = isset($m[5]) ? self::cleanName((string)$m[5]) : null;
            $sortDir = strtoupper((string)($m[6] ?? 'ASC'));
            $limit = isset($m[7]) ? (int)$m[7] : 50;
            $columns = $colsRaw === '*' ? ['*'] : self::parseList($colsRaw);

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;
            $result = self::selectRows($db, $table, empty($columns) ? ['*'] : $columns, $where, $sortField, $sortDir, $limit);

            return [
                'ok' => true,
                'message' => count($result['rows']) . ' Treffer aus ' . $table . '.',
                'keys' => $result['keys'],
                'rows' => $result['rows'],
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^SEED\s+([a-zA-Z0-9_\-]+)\s+WITH\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $assignments = self::parseAssignments((string)$m[2]);
            $db = self::cleanName((string)($m[3] ?? ($ctx['db'] ?? '')));

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            if (empty($assignments)) {
                return [
                    'ok' => false,
                    'message' => 'Keine Daten gefunden.',
                    'ctx' => $ctx
                ];
            }

            $id = GBDB::insertData($db, $table, $assignments);

            if ($id <= 0) {
                return [
                    'ok' => false,
                    'message' => 'Insert fehlgeschlagen.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;

            return [
                'ok' => true,
                'message' => 'Datensatz angelegt. Neue ID: ' . $id,
                'insert_id' => $id,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^RESHAPE\s+([a-zA-Z0-9_\-]+)\s+WITH\s+(.+?)\s+WHERE\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $assignments = self::parseAssignments((string)$m[2]);
            $where = self::parseWhere((string)$m[3]);
            $db = self::cleanName((string)($m[4] ?? ($ctx['db'] ?? '')));

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            if (empty($assignments) || $where === null) {
                return [
                    'ok' => false,
                    'message' => 'WITH oder WHERE ungültig.',
                    'ctx' => $ctx
                ];
            }

            if (!in_array($where['op'], ['=', '=='], true)) {
                return [
                    'ok' => false,
                    'message' => 'RESHAPE unterstützt aktuell nur WHERE feld = wert.',
                    'ctx' => $ctx
                ];
            }

            $ok = GBDB::editData($db, $table, $where['field'], $where['value'], $assignments);

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Update fehlgeschlagen.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;

            return [
                'ok' => true,
                'message' => 'Datensatz aktualisiert.',
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^ERASE\s+FROM\s+([a-zA-Z0-9_\-]+)\s+WHERE\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::cleanName((string)$m[1]);
            $where = self::parseWhere((string)$m[2]);
            $db = self::cleanName((string)($m[3] ?? ($ctx['db'] ?? '')));

            if ($db === '' || $table === '') {
                return [
                    'ok' => false,
                    'message' => 'Base oder Tabelle fehlt.',
                    'ctx' => $ctx
                ];
            }

            if ($where === null) {
                return [
                    'ok' => false,
                    'message' => 'WHERE ungültig.',
                    'ctx' => $ctx
                ];
            }

            if (!in_array($where['op'], ['=', '=='], true)) {
                return [
                    'ok' => false,
                    'message' => 'ERASE unterstützt aktuell nur WHERE feld = wert.',
                    'ctx' => $ctx
                ];
            }

            $ok = GBDB::deleteData($db, $table, $where['field'], $where['value']);

            if (!$ok) {
                return [
                    'ok' => false,
                    'message' => 'Löschen fehlgeschlagen.',
                    'ctx' => $ctx
                ];
            }

            $ctx['db'] = $db;
            $ctx['table'] = $table;

            return [
                'ok' => true,
                'message' => 'Datensatz entfernt.',
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        return [
            'ok' => false,
            'message' => 'Befehl nicht erkannt: ' . $command,
            'ctx' => $ctx
        ];
    }

    public static function run(string $script, array $ctx = []): array {
        $commands = preg_split('/;+\s*/', trim($script));
        $messages = [];
        $results = [];
        $lastKeys = [];
        $lastRows = [];
        $refresh = false;
        $okAll = true;

        foreach ($commands as $command) {
            $command = trim((string)$command);
            if ($command === '') continue;

            $result = self::command($command, $ctx);

            if (($result['message'] ?? '') !== '') {
                $messages[] = [
                    'ok' => (bool)($result['ok'] ?? false),
                    'text' => (string)$result['message']
                ];
            }

            if (isset($result['keys'], $result['rows'])) {
                $lastKeys = $result['keys'];
                $lastRows = $result['rows'];
                $results[] = [
                    'command' => $command,
                    'keys' => $result['keys'],
                    'rows' => $result['rows']
                ];
            }

            if (!empty($result['refresh'])) {
                $refresh = true;
            }

            if (empty($result['ok'])) {
                $okAll = false;
                break;
            }
        }

        return [
            'ok' => $okAll,
            'messages' => $messages,
            'results' => $results,
            'keys' => $lastKeys,
            'rows' => $lastRows,
            'ctx' => [
                'db' => self::cleanName((string)($ctx['db'] ?? '')),
                'table' => self::cleanName((string)($ctx['table'] ?? ''))
            ],
            'refresh' => $refresh
        ];
    }
}
