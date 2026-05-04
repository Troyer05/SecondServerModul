<?php

trait GBDBv2_AdvancedTrait {
    private static array $runtimeCacheV2 = [];

    /**
     * Erzeugt einen stabilen Cache-Key für Tabellenoperationen.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param array $extra Zusatzdaten.
     * @return string Cache-Key.
     */
    private static function cacheKey(string $database, string $table, array $extra = []): string {
        return hash('sha256', json_encode([$database, $table, $extra], JSON_UNESCAPED_UNICODE) ?: '');
    }

    /**
     * Entfernt Runtime-Cache für eine Tabelle oder komplett.
     * @param string|null $database Datenbankname oder null.
     * @param string|null $table Tabellenname oder null.
     * @return void
     */
    public static function clearRuntimeCache(?string $database = null, ?string $table = null): void {
        if ($database === null || $table === null) {
            self::$runtimeCacheV2 = [];
            return;
        }

        // Tabellenfeinheit ist bewusst konservativ: Runtime-Cache ist klein und wird pro Request/Prozess gehalten.
        self::$runtimeCacheV2 = [];
    }

    /**
     * Liest Daten mit einfachem Runtime-Cache.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param bool $filter Filter aktiv.
     * @param mixed $where Spalte.
     * @param mixed $is Suchwert.
     * @param int $ttl Sekunden bis Cache abläuft.
     * @return mixed Daten.
     */
    public static function getCachedData(string $database, string $table, bool $filter = false, mixed $where = '', mixed $is = '', int $ttl = 5): mixed {
        $key = hash('sha256', json_encode([$database, $table, $filter, $where, $is], JSON_UNESCAPED_UNICODE) ?: '');
        $now = time();

        if (isset(self::$runtimeCacheV2[$key]) && ($now - (int)self::$runtimeCacheV2[$key]['ts']) <= max(1, $ttl)) {
            return self::$runtimeCacheV2[$key]['data'];
        }

        $data = self::getData($database, $table, $filter, $where, $is);
        self::$runtimeCacheV2[$key] = ['ts' => $now, 'data' => $data];
        return $data;
    }

    /**
     * Fügt viele Datensätze in einem Schritt ein.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param array $rows Datensätze.
     * @param bool $transactional true für Transaktion.
     * @return array Status mit eingefügten IDs.
     */
    public static function bulkInsert(string $database, string $table, array $rows, bool $transactional = true): array {
        $ids = [];
        $errors = [];
        $startedTx = false;

        if ($transactional && !self::inTransaction()) {
            $startedTx = self::begin();
        }

        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                $errors[] = ['index' => $i, 'error' => 'row_not_array'];
                continue;
            }

            $id = self::insertData($database, $table, $row);

            if ($id <= 0) {
                $errors[] = ['index' => $i, 'error' => 'insert_failed'];
                continue;
            }

            $ids[] = $id;
        }

        if ($startedTx) {
            if (!empty($errors)) {
                self::rollback();
                return ['ok' => false, 'ids' => [], 'errors' => $errors];
            }

            $ok = self::commit();
            return ['ok' => $ok, 'ids' => $ok ? $ids : [], 'errors' => $ok ? [] : [['error' => 'commit_failed']]];
        }

        return ['ok' => empty($errors), 'ids' => $ids, 'errors' => $errors];
    }

    /**
     * Streamt Datensätze seitenweise an einen Callback.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param callable $callback Callback pro Zeile.
     * @param int $chunkSize Chunk-Größe.
     * @return array Statistik.
     */
    public static function streamRows(string $database, string $table, callable $callback, int $chunkSize = 500): array {
        $rows = self::getData($database, $table);
        $count = 0;
        $chunkSize = max(1, $chunkSize);

        foreach (array_chunk(is_array($rows) ? $rows : [], $chunkSize) as $chunk) {
            foreach ($chunk as $row) {
                if (!is_array($row)) continue;
                $callback($row, $count);
                $count++;
            }
        }

        return ['ok' => true, 'rows' => $count, 'chunk_size' => $chunkSize];
    }

    /**
     * Gibt eine Seite aus einer Tabelle zurück.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param int $page Seite ab 1.
     * @param int $perPage Datensätze pro Seite.
     * @return array Page-Ergebnis.
     */
    public static function page(string $database, string $table, int $page = 1, int $perPage = 50): array {
        $page = max(1, $page);
        $perPage = max(1, min(1000, $perPage));
        $rows = self::getData($database, $table);
        $rows = is_array($rows) ? array_values($rows) : [];
        $total = count($rows);
        $offset = ($page - 1) * $perPage;

        return [
            'ok' => true,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'pages' => (int)ceil($total / $perPage),
            'rows' => array_slice($rows, $offset, $perPage)
        ];
    }

    /**
     * Öffnet einen cursor-artigen Slice über eine Tabelle.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param int $limit Anzahl Datensätze.
     * @param string|null $cursor Cursor-Token.
     * @return array Cursor-Ergebnis.
     */
    public static function cursor(string $database, string $table, int $limit = 100, ?string $cursor = null): array {
        $limit = max(1, min(1000, $limit));
        $offset = 0;

        if ($cursor !== null && $cursor !== '') {
            $decoded = base64_decode($cursor, true);
            $json = $decoded !== false ? json_decode($decoded, true) : null;
            if (is_array($json) && isset($json['offset'])) {
                $offset = max(0, (int)$json['offset']);
            }
        }

        $rows = self::getData($database, $table);
        $rows = is_array($rows) ? array_values($rows) : [];
        $slice = array_slice($rows, $offset, $limit);
        $nextOffset = $offset + count($slice);
        $next = $nextOffset < count($rows)
            ? base64_encode(json_encode(['offset' => $nextOffset], JSON_UNESCAPED_UNICODE) ?: '')
            : null;

        return ['ok' => true, 'rows' => $slice, 'cursor' => $next, 'offset' => $offset, 'limit' => $limit];
    }

    /**
     * Sucht Volltext über eine oder mehrere Spalten.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $query Suchtext.
     * @param array $columns Spalten, leer = alle Textspalten.
     * @param int $limit Maximale Treffer.
     * @return array Treffer mit Score.
     */
    public static function fulltext_search(string $database, string $table, string $query, array $columns = [], int $limit = 50): array {
        $queryTokens = self::tokenizeText($query);
        if (empty($queryTokens)) return [];

        $rows = self::getData($database, $table);
        $hits = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) continue;

            $haystack = '';
            $useColumns = empty($columns) ? array_keys($row) : $columns;

            foreach ($useColumns as $column) {
                if ($column === 'id') continue;
                if (isset($row[$column]) && (is_scalar($row[$column]) || $row[$column] === null)) {
                    $haystack .= ' ' . (string)$row[$column];
                }
            }

            $tokens = array_count_values(self::tokenizeText($haystack));
            $score = 0;

            foreach ($queryTokens as $token) {
                $score += (int)($tokens[$token] ?? 0);
            }

            if ($score > 0) {
                $hits[] = ['score' => $score, 'row' => $row];
            }
        }

        usort($hits, fn($a, $b) => ($b['score'] <=> $a['score']));
        return array_slice($hits, 0, max(1, $limit));
    }

    /**
     * Abwärtskompatibler Alias für fulltext_search().
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $query Suchtext.
     * @param array $columns Spalten, leer = alle Textspalten.
     * @param int $limit Maximale Treffer.
     * @return array Treffer mit Score.
     */
    public static function fulltextSearch(string $database, string $table, string $query, array $columns = [], int $limit = 50): array {
        return self::fulltext_search($database, $table, $query, $columns, $limit);
    }

    /**
     * Kurzer Legacy-Alias für fulltext_search().
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $query Suchtext.
     * @param array $columns Spalten, leer = alle Textspalten.
     * @param int $limit Maximale Treffer.
     * @return array Treffer mit Score.
     */
    public static function fulltext(string $database, string $table, string $query, array $columns = [], int $limit = 50): array {
        return self::fulltext_search($database, $table, $query, $columns, $limit);
    }

    /**
     * Zerlegt Text in Such-Tokens.
     * @param string $text Text.
     * @return array Tokens.
     */
    private static function tokenizeText(string $text): array {
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];
        return array_values(array_filter($parts, function ($p) {
            return (function_exists('mb_strlen') ? mb_strlen($p, 'UTF-8') : strlen($p)) >= 2;
        }));
    }

    /**
     * Liefert einen einfachen Query-Plan für getData/PICK-artige Zugriffe.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $where Spalte.
     * @param mixed $is Wert.
     * @return array Query-Plan.
     */
    public static function queryPlan(string $database, string $table, string $where = '', mixed $is = ''): array {
        $meta = self::meta($database, $table);
        $hasIndex = $where !== '' && in_array($where, $meta['indexes'] ?? [], true);
        $appendOps = (int)($meta['append_ops'] ?? 0);

        return [
            'engine' => 'GBDB',
            'database' => $database,
            'table' => $table,
            'where' => $where,
            'uses_index' => $hasIndex && $appendOps === 0,
            'requires_append_replay' => $appendOps > 0,
            'strategy' => $hasIndex && $appendOps === 0 ? 'index_lookup' : 'append_replay_scan',
            'estimated_rows' => (int)($meta['rows'] ?? 0),
            'append_ops' => $appendOps
        ];
    }

    /**
     * Vergibt eine ACL-Berechtigung auf Tabellenebene.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $role Rolle.
     * @param string $permission Berechtigung.
     * @return bool true bei Erfolg.
     */
    public static function grantAcl(string $database, string $table, string $role, string $permission): bool {
        return self::changeAcl($database, $table, $role, $permission, true);
    }

    /**
     * Entfernt eine ACL-Berechtigung auf Tabellenebene.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $role Rolle.
     * @param string $permission Berechtigung.
     * @return bool true bei Erfolg.
     */
    public static function revokeAcl(string $database, string $table, string $role, string $permission): bool {
        return self::changeAcl($database, $table, $role, $permission, false);
    }

    /**
     * Prüft eine Tabellen-ACL.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $role Rolle.
     * @param string $permission Berechtigung.
     * @return bool true wenn erlaubt.
     */
    public static function checkAcl(string $database, string $table, string $role, string $permission): bool {
        $meta = self::meta($database, $table);
        $acl = $meta['acl'] ?? [];

        if (($acl['*'][$permission] ?? false) === true) return true;
        if (($acl[$role]['*'] ?? false) === true) return true;
        return ($acl[$role][$permission] ?? false) === true;
    }

    /**
     * Ändert eine ACL-Berechtigung.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $role Rolle.
     * @param string $permission Berechtigung.
     * @param bool $allow true zum Erlauben.
     * @return bool true bei Erfolg.
     */
    private static function changeAcl(string $database, string $table, string $role, string $permission, bool $allow): bool {
        $role = trim($role);
        $permission = trim($permission);
        if ($role === '' || $permission === '') return false;

        $metaFile = self::metaFileForTable($database, $table);
        $lockFile = self::lockFileForTable($database, $table);

        return (bool)self::withTableLock($lockFile, function () use ($metaFile, $role, $permission, $allow) {
            $meta = self::readMeta($metaFile);
            if (!isset($meta['acl']) || !is_array($meta['acl'])) $meta['acl'] = [];
            if (!isset($meta['acl'][$role]) || !is_array($meta['acl'][$role])) $meta['acl'][$role] = [];

            if ($allow) {
                $meta['acl'][$role][$permission] = true;
            } else {
                unset($meta['acl'][$role][$permission]);
                if (empty($meta['acl'][$role])) unset($meta['acl'][$role]);
            }

            return self::writeMeta($metaFile, $meta);
        });
    }

    /**
     * Schreibt einen Audit-Eintrag in eine Systemtabelle.
     * @param string $action Aktion.
     * @param array $payload Nutzdaten.
     * @param string $actor Akteur.
     * @return int Audit-ID.
     */
    public static function audit(string $action, array $payload = [], string $actor = 'system'): int {
        $db = '_gbdb_audit';
        $table = 'audit_log';

        if (!in_array($db, self::listDBs(), true)) {
            self::createDatabase($db);
        }

        if (!in_array($table, self::listTables($db), true)) {
            self::createTable($db, $table, ['ts', 'actor', 'action', 'payload']);
        }

        return self::insertData($db, $table, [
            'ts' => time(),
            'actor' => $actor,
            'action' => $action,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Exportiert alle Datensätze, die zu einem DSGVO-Identifier gehören.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $column Spalte.
     * @param mixed $value Wert.
     * @return array Exportdaten.
     */
    public static function gdprExport(string $database, string $table, string $column, mixed $value): array {
        $rows = self::getData($database, $table);
        $out = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (is_array($row) && array_key_exists($column, $row) && $row[$column] == $value) {
                $out[] = $row;
            }
        }

        self::audit('gdpr_export', compact('database', 'table', 'column'));
        return $out;
    }

    /**
     * Pseudonymisiert Felder für DSGVO-Auskunft/Löschung light.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $where Spalte.
     * @param mixed $is Wert.
     * @param array $columns Spalten zum Redacten.
     * @param string $replacement Ersatzwert.
     * @return bool true bei Erfolg.
     */
    public static function gdprRedact(string $database, string $table, string $where, mixed $is, array $columns, string $replacement = '[redacted]'): bool {
        $set = [];
        foreach ($columns as $column) {
            if ($column !== '' && $column !== 'id') $set[(string)$column] = $replacement;
        }

        if (empty($set)) return false;
        $ok = self::editData($database, $table, $where, $is, $set);
        self::audit('gdpr_redact', compact('database', 'table', 'where', 'columns'));
        return $ok;
    }

    /**
     * Wendet eine Migration genau einmal pro Tabellen-Version an.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param string $migrationId Migration-ID.
     * @param callable $callback Callback erhält database, table.
     * @return array Migrationsstatus.
     */
    public static function migrate(string $database, string $table, string $migrationId, callable $callback): array {
        $metaFile = self::metaFileForTable($database, $table);
        $lockFile = self::lockFileForTable($database, $table);

        return (array)self::withTableLock($lockFile, function () use ($database, $table, $migrationId, $callback, $metaFile) {
            $meta = self::readMeta($metaFile);
            $done = $meta['migrations'] ?? [];
            if (!is_array($done)) $done = [];

            if (in_array($migrationId, $done, true)) {
                return ['ok' => true, 'already_done' => true, 'migration' => $migrationId];
            }

            $result = $callback($database, $table);
            if ($result === false) {
                return ['ok' => false, 'migration' => $migrationId, 'error' => 'callback_failed'];
            }

            $meta = self::readMeta($metaFile);
            $done = $meta['migrations'] ?? [];
            if (!is_array($done)) $done = [];
            $done[] = $migrationId;
            $meta['migrations'] = array_values(array_unique($done));
            self::writeMeta($metaFile, $meta);

            return ['ok' => true, 'already_done' => false, 'migration' => $migrationId];
        });
    }

    /**
     * Erstellt eine Partitionstabellen-Bezeichnung.
     * @param string $table Basistabelle.
     * @param string $partition Partition.
     * @return string Tabellenname.
     */
    public static function partitionTableName(string $table, string $partition): string {
        return Format::cleanString($table) . '__p_' . Format::cleanString($partition);
    }

    /**
     * Fügt Daten in eine Partition ein.
     * @param string $database Datenbankname.
     * @param string $table Basistabelle.
     * @param string $partition Partition.
     * @param array $data Datensatz.
     * @return int ID.
     */
    public static function insertPartitioned(string $database, string $table, string $partition, array $data): int {
        $pTable = self::partitionTableName($table, $partition);

        if (!in_array($database, self::listDBs(), true)) self::createDatabase($database);
        if (!in_array($pTable, self::listTables($database), true)) self::createTable($database, $pTable, array_keys($data));

        return self::insertData($database, $pTable, $data);
    }

    /**
     * Liest Daten aus einer Partition.
     * @param string $database Datenbankname.
     * @param string $table Basistabelle.
     * @param string $partition Partition.
     * @return array Daten.
     */
    public static function getPartition(string $database, string $table, string $partition): array {
        $rows = self::getData($database, self::partitionTableName($table, $partition));
        return is_array($rows) ? $rows : [];
    }

    /**
     * Liefert den Shard-Tabellennamen für einen Key.
     * @param string $table Basistabelle.
     * @param mixed $key Shard-Key.
     * @param int $shards Shard-Anzahl.
     * @return string Shard-Tabelle.
     */
    public static function shardTableName(string $table, mixed $key, int $shards = 16): string {
        $shards = max(1, $shards);
        $slot = hexdec(substr(hash('crc32b', (string)$key), 0, 6)) % $shards;
        return Format::cleanString($table) . '__s_' . $slot;
    }

    /**
     * Fügt Daten anhand eines Shard-Keys in eine Shard-Tabelle ein.
     * @param string $database Datenbankname.
     * @param string $table Basistabelle.
     * @param mixed $key Shard-Key.
     * @param array $data Datensatz.
     * @param int $shards Shard-Anzahl.
     * @return int ID.
     */
    public static function insertSharded(string $database, string $table, mixed $key, array $data, int $shards = 16): int {
        $sTable = self::shardTableName($table, $key, $shards);

        if (!in_array($database, self::listDBs(), true)) self::createDatabase($database);
        if (!in_array($sTable, self::listTables($database), true)) self::createTable($database, $sTable, array_keys($data));

        return self::insertData($database, $sTable, $data);
    }

    /**
     * Liest Daten aus dem passenden Shard.
     * @param string $database Datenbankname.
     * @param string $table Basistabelle.
     * @param mixed $key Shard-Key.
     * @param int $shards Shard-Anzahl.
     * @return array Daten.
     */
    public static function getShard(string $database, string $table, mixed $key, int $shards = 16): array {
        $rows = self::getData($database, self::shardTableName($table, $key, $shards));
        return is_array($rows) ? $rows : [];
    }

    /**
     * Führt WAL-Recovery für eine Tabelle aus.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @return array Recovery-Status.
     */
    public static function recoverTable(string $database, string $table): array {
        return GBDBStorage::recoverWal(self::appendFileForTable($database, $table));
    }

    /**
     * Liefert Append-Log-Informationen einer Tabelle.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @param int $limit Maximale Anzahl.
     * @return array Append-Log.
     */
    public static function appendLog(string $database, string $table, int $limit = 100): array {
        $ops = self::readAppendOps(self::appendFileForTable($database, $table));
        return array_slice($ops, max(0, count($ops) - max(1, $limit)));
    }

    /**
     * Liefert Monitoring-Daten für eine Tabelle.
     * @param string $database Datenbankname.
     * @param string $table Tabellenname.
     * @return array Monitoring-Daten.
     */
    public static function monitor(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $append = self::appendFileForTable($database, $table);
        $meta = self::meta($database, $table);
        $health = self::health($database, $table);

        return [
            'ok' => is_file($file),
            'database' => $database,
            'table' => $table,
            'rows' => (int)($meta['rows'] ?? 0),
            'append_ops' => (int)($meta['append_ops'] ?? 0),
            'data_size' => is_file($file) ? (int)filesize($file) : 0,
            'append_size' => is_file($append) ? (int)filesize($append) : 0,
            'indexes' => $meta['indexes'] ?? [],
            'constraints' => $meta['constraints'] ?? [],
            'health' => $health,
            'updated_at' => $meta['updated_at'] ?? 0
        ];
    }
}
