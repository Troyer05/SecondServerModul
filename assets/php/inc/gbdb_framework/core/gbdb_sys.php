<?php

class GBDB {

    /* ============================================================
       NAME-OBFUSCATION (deterministisch) + Index-Mapping
       ============================================================ */

    private static function nameToken(string $plain, string $ns = 'g'): string {
        $plain = (string)$plain;
        $key   = (string)Vars::cryptKey();

        $data  = $ns . '|' . $plain;

        $raw  = hash_hmac('sha256', $data, $key, true);
        $b64  = base64_encode($raw);
        $safe = rtrim(strtr($b64, '+/', '-_'), '=');

        return 'gb_' . $safe;
    }

    private static function dbIndexFile(): string {
        return Vars::DB_PATH() . self::nameToken('__db_index__', 'meta') . Vars::data_extension();
    }

    private static function tableIndexFileByDbToken(string $dbToken): string {
        $dir = Vars::DB_PATH() . $dbToken . "/";
        return $dir . self::nameToken('__table_index__', 'meta') . Vars::data_extension();
    }

    private static function readIndex(string $file): array {
        $rows = self::ini($file);

        if (empty($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return [];
        }

        unset($rows[0]);
        $rows = array_values($rows);

        $map = [];

        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            if (!isset($r['plain'], $r['token'])) continue;

            $p = (string)$r['plain'];
            $t = (string)$r['token'];

            if ($p !== "" && $t !== "") {
                $map[$p] = $t;
            }
        }

        return $map;
    }

    private static function writeIndex(string $file, array $map): bool {
        $db = [];
        $db[] = [
            "id"    => -1,
            "plain" => "-header-",
            "token" => "-header-",
        ];

        $id = 0;
        foreach ($map as $plain => $token) {
            $db[] = [
                "id"    => $id++,
                "plain" => (string)$plain,
                "token" => (string)$token,
            ];
        }

        return self::writeTable($file, $db);
    }

    private static function getDbToken(string $dbPlain, bool $ensure = false): ?string {
        $dbPlain = Format::cleanString($dbPlain);
        if ($dbPlain === "") return null;

        if (!Vars::crypt_data()) {
            return $dbPlain;
        }

        $idxFile = self::dbIndexFile();
        $map     = self::readIndex($idxFile);

        if (isset($map[$dbPlain])) {
            return $map[$dbPlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken('db:' . $dbPlain, 'db');

        $used = array_flip(array_values($map));
        if (isset($used[$token])) {
            $n = 2;
            do {
                $token2 = self::nameToken('db:' . $dbPlain . '#'.$n, 'db');
                $n++;
            } while (isset($used[$token2]));
            $token = $token2;
        }

        $map[$dbPlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }

    private static function getTableToken(string $dbPlain, string $tablePlain, bool $ensure = false): ?string {
        $dbPlain    = Format::cleanString($dbPlain);
        $tablePlain = Format::cleanString($tablePlain);

        if ($dbPlain === "" || $tablePlain === "") return null;

        if (!Vars::crypt_data()) {
            return $tablePlain;
        }

        $dbToken = self::getDbToken($dbPlain, $ensure);
        if ($dbToken === null) return null;

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        $map     = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            return $map[$tablePlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken('tbl:' . $dbPlain . '|' . $tablePlain, 'tbl');

        $used = array_flip(array_values($map));
        if (isset($used[$token])) {
            $n = 2;
            do {
                $token2 = self::nameToken('tbl:' . $dbPlain . '|' . $tablePlain . '#'.$n, 'tbl');
                $n++;
            } while (isset($used[$token2]));
            $token = $token2;
        }

        $map[$tablePlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }

    private static function dropTableFromIndex(string $dbPlain, string $tablePlain): void {
        if (!Vars::crypt_data()) return;

        $dbToken = self::getDbToken($dbPlain, false);
        if ($dbToken === null) return;

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        $map     = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            unset($map[$tablePlain]);
            self::writeIndex($idxFile, $map);
        }
    }

    private static function removeTableIndexIfExists(string $dbPlain): void {
        if (!Vars::crypt_data()) return;

        $dbToken = self::getDbToken($dbPlain, false);
        if ($dbToken === null) return;

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        if (is_file($idxFile)) {
            @unlink($idxFile);
        }
    }


    /* ============================================================
       CORE IO + LOCKING + META + APPEND
       ============================================================ */

    private static function makePath(string $database, string $table, bool $ensure = false): string {
        $table    = Format::cleanString($table);
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $dbToken = self::getDbToken($database, $ensure);
            $tbToken = self::getTableToken($database, $table, $ensure);

            if ($dbToken === null || $tbToken === null) {
                return Vars::DB_PATH() . "__missing__/" . "__missing__" . Vars::data_extension();
            }

            $table    = $tbToken;
            $database = $dbToken;
        }

        $table    .= Vars::data_extension();
        $database  = Vars::DB_PATH() . $database . "/";

        return $database . $table;
    }

    private static function ini(string $file): array {
        if (!is_file($file)) return [];

        $raw = @file_get_contents($file);
        if ($raw === false) {
            error_log("[GBDB] Konnte Datei nicht lesen: {$file}");
            return [];
        }

        if (Vars::crypt_data()) {
            $decoded = Crypt::decode($raw);
            if ($decoded === null) {
                error_log("[GBDB] Crypt::decode() fehlgeschlagen für: {$file}");
                return [];
            }
            $db = json_decode($decoded, true);
        } else {
            $db = json_decode($raw, true);
        }

        return is_array($db) ? $db : [];
    }

    private static function writeTable(string $file, array $db): bool {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $json = json_encode($db, Vars::jpretty());
        if ($json === false) {
            error_log("[GBDB] json_encode() fehlgeschlagen für: {$file}");
            return false;
        }

        $payload = Vars::crypt_data() ? Crypt::encode($json) : $json;

        $tmp = $file . '.' . uniqid('tmp_', true);

        if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
            error_log("[GBDB] Konnte Temp-Datei nicht schreiben: {$tmp}");
            return false;
        }

        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            error_log("[GBDB] Konnte {$tmp} nicht nach {$file} verschieben");
            return false;
        }

        return true;
    }

    private static function lockFileForTable(string $database, string $table, bool $ensure = false): string {
        return self::makePath($database, $table, $ensure) . ".lock";
    }

    /**
     * Meta-Datei pro Tabelle!
     * - plain:  __meta__<table>.json
     * - crypt:  token('__meta__|<tblToken>').db
     */
    private static function metaFileForTable(string $database, string $table, bool $ensure = false): string {
        $dataFile = self::makePath($database, $table, $ensure);
        $dir      = dirname($dataFile) . "/";

        if (!Vars::crypt_data()) {
            $t = Format::cleanString($table);
            return $dir . "__meta__" . $t . Vars::data_extension();
        }

        $tbToken = self::getTableToken($database, $table, $ensure);
        if ($tbToken === null) {
            return $dir . self::nameToken('__meta__|__missing__', 'meta') . Vars::data_extension();
        }

        return $dir . self::nameToken('__meta__|' . $tbToken, 'meta') . Vars::data_extension();
    }

    /**
     * Append-Datei pro Tabelle!
     * - plain: __append__<table>.json (optional, aber wir halten es konsistent)
     * - crypt: token('__append__|<tblToken>').db
     */
    private static function appendFileForTable(string $database, string $table, bool $ensure = false): string {
        $dataFile = self::makePath($database, $table, $ensure);
        $dir      = dirname($dataFile) . "/";

        if (!Vars::crypt_data()) {
            $t = Format::cleanString($table);
            return $dir . "__append__" . $t . Vars::data_extension();
        }

        $tbToken = self::getTableToken($database, $table, $ensure);
        if ($tbToken === null) {
            return $dir . self::nameToken('__append__|__missing__', 'meta') . Vars::data_extension();
        }

        return $dir . self::nameToken('__append__|' . $tbToken, 'meta') . Vars::data_extension();
    }

    private static function withTableLock(string $lockFile, callable $fn) {
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $h = @fopen($lockFile, "c+");
        if (!$h) {
            error_log("[GBDB] Konnte Lockfile nicht öffnen: {$lockFile}");
            return false;
        }

        try {
            if (!@flock($h, LOCK_EX)) {
                error_log("[GBDB] Konnte Lock nicht setzen: {$lockFile}");
                return false;
            }
            return $fn();
        } finally {
            @flock($h, LOCK_UN);
            @fclose($h);
        }
    }

    private static function readMeta(string $metaFile): array {
        $m = self::ini($metaFile);

        if (isset($m[0]) && is_array($m[0])) {
            return $m[0];
        }

        return [
            "last_id"    => 0,
            "rows"       => 0,
            "append_ops" => 0,
            "indexes"    => [],
            "created_at" => time(),
            "updated_at" => time(),
        ];
    }

    private static function writeMeta(string $metaFile, array $meta): bool {
        $meta["updated_at"] = time();
        return self::writeTable($metaFile, [ $meta ]);
    }

    private static function isHeaderRow(array $row): bool {
        return (isset($row["id"]) && (int)$row["id"] === -1);
    }

    private static function ensureHeader(array &$tableData, array $cols): void {
        if (!empty($tableData) && isset($tableData[0]) && is_array($tableData[0])) {
            return;
        }

        $header = ["id" => -1];
        foreach ($cols as $c) {
            $c = (string)$c;
            if ($c === "" || $c === "id") continue;
            $header[$c] = "-header-";
        }

        $tableData = [ $header ];
    }

    private static function buildRowFromHeader(array $header, array $data, int $id): array {
        $row = [];
        foreach ($header as $col => $default) {
            if ($col === "id") continue;
            $row[$col] = array_key_exists($col, $data) ? $data[$col] : $default;
        }
        $row["id"] = $id;
        return $row;
    }

    /**
     * Append: schreibt 1 Operation als Zeile.
     * - crypt=false: JSON + "\n"
     * - crypt=true: Crypt::encode(JSON) + "\n"
     */
    private static function appendOp(string $appendFile, array $op): bool {
        $dir = dirname($appendFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $json = json_encode($op, 0);
        if ($json === false) return false;

        $line = Vars::crypt_data() ? Crypt::encode($json) : $json;
        $line .= "\n";

        return (@file_put_contents($appendFile, $line, FILE_APPEND | LOCK_EX) !== false);
    }

    /**
     * Liest Append-Log Zeilen.
     * @return array<int, array> ops
     */
    private static function readAppendOps(string $appendFile): array {
        if (!is_file($appendFile)) return [];

        $fh = @fopen($appendFile, "r");
        if (!$fh) return [];

        $ops = [];

        try {
            while (!feof($fh)) {
                $line = fgets($fh);
                if ($line === false) break;

                $line = trim($line);
                if ($line === "") continue;

                $json = $line;

                if (Vars::crypt_data()) {
                    $decoded = Crypt::decode($line);
                    if ($decoded === null) {
                        error_log("[GBDB] Append decode fehlgeschlagen: {$appendFile}");
                        continue;
                    }
                    $json = $decoded;
                }

                $op = json_decode($json, true);
                if (is_array($op) && isset($op["op"])) {
                    $ops[] = $op;
                }
            }
        } finally {
            @fclose($fh);
        }

        return $ops;
    }

    /**
     * Spielt Append-Ops auf ein Base-Array (mit Header) ab.
     */
    private static function applyOps(array $base, array $ops): array {
        if (empty($base)) return $base;

        $idIndex = [];
        foreach ($base as $i => $r) {
            if (!is_array($r)) continue;
            if ($i === 0 && self::isHeaderRow($r)) continue;
            if (isset($r["id"])) $idIndex[(int)$r["id"]] = $i;
        }

        foreach ($ops as $op) {
            $t = $op["op"] ?? "";

            if ($t === "ins" && isset($op["row"]) && is_array($op["row"])) {
                $row = $op["row"];
                if (isset($row["id"])) {
                    $id = (int)$row["id"];
                    if (isset($idIndex[$id])) {
                        $base[$idIndex[$id]] = $row;
                    } else {
                        $base[] = $row;
                        $idIndex[$id] = count($base) - 1;
                    }
                }
            }

            if ($t === "upd" && isset($op["id"])) {
                $id = (int)$op["id"];
                if (!isset($idIndex[$id])) continue;
                if (!isset($op["set"]) || !is_array($op["set"])) continue;

                foreach ($op["set"] as $k => $v) {
                    if ($k === "id") continue;
                    if (array_key_exists($k, $base[$idIndex[$id]])) {
                        $base[$idIndex[$id]][$k] = $v;
                    }
                }
            }

            if ($t === "del" && isset($op["id"])) {
                $id = (int)$op["id"];
                if (!isset($idIndex[$id])) continue;

                unset($base[$idIndex[$id]]);
                $base = array_values($base);

                $idIndex = [];
                foreach ($base as $i => $r) {
                    if (!is_array($r)) continue;
                    if ($i === 0 && self::isHeaderRow($r)) continue;
                    if (isset($r["id"])) $idIndex[(int)$r["id"]] = $i;
                }
            }
        }

        return $base;
    }


    /* ============================================================
       PUBLIC API
       ============================================================ */

    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);
        if ($name === "") return false;

        $base = Vars::DB_PATH();
        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, true)
            : $name;

        if ($dirName === null) return false;

        $path = $base . $dirName;

        if (!is_dir($path)) {
            return @mkdir($path, 0777);
        }

        return false;
    }

    public static function deleteDatabase(string $name): bool {
        $name = Format::cleanString($name);
        if ($name === "") return false;

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, false)
            : $name;

        if ($dirName === null) return false;

        $path = Vars::DB_PATH() . $dirName;

        if (is_dir($path)) {
            $files = scandir($path);

            if ($files) {
                $rest = array_diff($files, ['.', '..']);

                if (Vars::crypt_data()) {
                    $idx = basename(self::tableIndexFileByDbToken($dirName));
                    $rest = array_values($rest);

                    if (count($rest) === 1 && $rest[0] === $idx) {
                        @unlink($path . "/" . $idx);
                        return @rmdir($path);
                    }
                }

                if (count($rest) === 0) {
                    return @rmdir($path);
                }
            }
        }

        return false;
    }

    public static function createTable(string $database, string $table, array $cols): bool {
        $file       = self::makePath($database, $table, true);
        $lockFile   = self::lockFileForTable($database, $table, true);
        $metaFile   = self::metaFileForTable($database, $table, true);
        $appendFile = self::appendFileForTable($database, $table, true);

        return (bool) self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $cols) {
            if (file_exists($file)) return false;

            $header = ["id" => -1];
            foreach ($cols as $col) {
                $col = (string)$col;
                if ($col === "" || $col === "id") continue;
                $header[$col] = "-header-";
            }

            if (!self::writeTable($file, [$header])) {
                return false;
            }

            self::writeMeta($metaFile, [
                "last_id"     => 0,
                "rows"        => 0,
                "append_ops"  => 0,
                "indexes"     => [],
                "created_at"  => time(),
                "updated_at"  => time(),
            ]);

            // Append: leer (line-based). Keine Crypt::encode hier, jede Zeile wird einzeln encoded.
            if (!is_file($appendFile)) {
                @file_put_contents($appendFile, "", LOCK_EX);
            }

            return true;
        });
    }

    public static function deleteTable(string $database, string $table): bool {
        $file       = self::makePath($database, $table);
        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        return (bool) self::withTableLock($lockFile, function () use ($database, $table, $file, $metaFile, $appendFile, $lockFile) {

            if (!file_exists($file)) return false;

            $ok = @unlink($file);

            if ($ok) {
                if (is_file($metaFile)) @unlink($metaFile);
                if (is_file($appendFile)) @unlink($appendFile);

                self::dropTableFromIndex($database, $table);

                if (is_file($lockFile)) @unlink($lockFile);
            }

            return $ok;
        });
    }

    /**
     * INSERT = append-only
     * @return int neue ID oder -1
     */
    public static function insertData(string $database, string $table, mixed $data): int {
        if (!is_array($data)) return -1;

        $file = self::makePath($database, $table);
        if (!file_exists($file)) return -1;

        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $data) {

            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                self::ensureHeader($base, array_keys($data));
                if (!self::writeTable($file, $base)) return -1;
            }

            $header = $base[0];

            $meta = self::readMeta($metaFile);
            $next = (int)($meta["last_id"] ?? 0) + 1;

            $id = isset($data["id"]) ? (int)$data["id"] : $next;
            if ($id <= 0) $id = $next;

            $row = self::buildRowFromHeader($header, $data, $id);

            $ok = self::appendOp($appendFile, [
                "op"  => "ins",
                "row" => $row,
                "ts"  => time(),
            ]);

            if (!$ok) return -1;

            $meta["last_id"] = max((int)($meta["last_id"] ?? 0), $id);
            $meta["rows"]    = (int)($meta["rows"] ?? 0) + 1;
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + 1;

            self::writeMeta($metaFile, $meta);

            return $id;
        });

        return is_int($res) ? $res : -1;
    }

    public static function deleteData(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);
        if (!file_exists($file)) return false;

        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $where, $is) {

            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $ops  = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            $hasHeader = (isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]));
            $changed = false;

            $ids = [];

            foreach ($full as $i => $r) {
                if (!is_array($r)) continue;
                if ($hasHeader && $i === 0) continue;

                if (isset($r[$where]) && $r[$where] == $is && isset($r["id"])) {
                    $ids[] = (int)$r["id"];
                }
            }

            if (empty($ids)) return false;

            foreach ($ids as $id) {
                if (!self::appendOp($appendFile, [
                    "op" => "del",
                    "id" => $id,
                    "ts" => time(),
                ])) {
                    return false;
                }
                $changed = true;
            }

            if ($changed) {
                $meta = self::readMeta($metaFile);
                $meta["rows"] = max(0, (int)($meta["rows"] ?? 0) - count($ids));
                $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);
                self::writeMeta($metaFile, $meta);
            }

            return $changed;
        });

        return (bool)$res;
    }

    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData): bool {
        if (!is_array($newData)) return false;

        $file = self::makePath($database, $table);
        if (!file_exists($file)) return false;

        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $where, $is, $newData) {

            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $ops  = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            $header = $full[0];
            $hasHeader = (isset($header) && is_array($header) && self::isHeaderRow($header));

            $set = [];
            foreach ($newData as $k => $v) {
                if ($k === "id") continue;
                if ($hasHeader && array_key_exists($k, $header)) {
                    $set[$k] = $v;
                }
            }
            if (empty($set)) return false;

            $ids = [];

            foreach ($full as $i => $r) {
                if (!is_array($r)) continue;
                if ($hasHeader && $i === 0) continue;

                if (isset($r[$where]) && $r[$where] == $is && isset($r["id"])) {
                    $ids[] = (int)$r["id"];
                }
            }

            if (empty($ids)) return false;

            foreach ($ids as $id) {
                if (!self::appendOp($appendFile, [
                    "op"  => "upd",
                    "id"  => $id,
                    "set" => $set,
                    "ts"  => time(),
                ])) {
                    return false;
                }
            }

            $meta = self::readMeta($metaFile);
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }

    public static function getData(
        string $database,
        string $table,
        bool $filter = false,
        mixed $where = "",
        mixed $is = ""
    ): mixed {
        $file = self::makePath($database, $table);
        $base = self::ini($file);

        if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
            return $filter ? [] : [];
        }

        $appendFile = self::appendFileForTable($database, $table);
        $ops        = self::readAppendOps($appendFile);

        $full = self::applyOps($base, $ops);

        $hasHeader = (isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]));

        if ($filter) {
            foreach ($full as $i => $r) {
                if (!is_array($r)) continue;
                if ($hasHeader && $i === 0) continue;

                if (isset($r[$where]) && $r[$where] == $is) {
                    return $r;
                }
            }
            return [];
        }

        if ($hasHeader) {
            unset($full[0]);
            $full = array_values($full);
        }

        return $full;
    }

    public static function elementExists(string $database, string $table, mixed $where, mixed $is): bool {
        $r = self::getData($database, $table, true, $where, $is);
        return is_array($r) && !empty($r);
    }

    public static function listDBs(): array {
        $d = Vars::DB_PATH();
        if (!is_dir($d)) return [];

        if (!Vars::crypt_data()) {
            $dirs = [];
            $tmp = array_filter(scandir($d), function ($f) use ($d) {
                return $f !== '.' && $f !== '..' && is_dir($d . $f);
            });
            foreach ($tmp as $db_name) {
                $dirs[] = $db_name;
            }
            return $dirs;
        }

        $idxFile = self::dbIndexFile();
        $map     = self::readIndex($idxFile);

        $out = [];
        foreach ($map as $plain => $token) {
            if (is_dir($d . $token)) {
                $out[] = $plain;
            }
        }

        return $out;
    }

    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);
        if ($database === "") return [];

        $ext = Vars::data_extension();

        if (!Vars::crypt_data()) {
            $databasePath = Vars::DB_PATH() . $database . "/";
            if (!is_dir($databasePath)) return [];

            $tables = [];
            $order  = $descending ? 1 : 0;
            $tmp    = scandir($databasePath, $order);

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") continue;

                // lock files raus
                if (str_ends_with($entry, ".lock")) continue;

                if (!str_ends_with($entry, $ext)) continue;

                // meta/append/idx raus (prefix)
                if (str_starts_with($entry, "__meta__")) continue;
                if (str_starts_with($entry, "__append__")) continue;
                if (str_starts_with($entry, "__idx__")) continue;
                if (str_starts_with($entry, "__idxa__")) continue;

                $tables[] = str_replace($ext, "", $entry);
            }

            return $tables;
        }

        $dbToken = self::getDbToken($database, false);
        if ($dbToken === null) return [];

        $databasePath = Vars::DB_PATH() . $dbToken . "/";
        if (!is_dir($databasePath)) return [];

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        $map     = self::readIndex($idxFile);

        $tables = [];
        foreach ($map as $plain => $token) {
            $file = $databasePath . $token . $ext;
            if (is_file($file)) {
                $tables[] = $plain;
            }
        }

        if ($descending) {
            rsort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            sort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $tables;
    }

    /**
     * Compaction: Base-Snapshot neu bauen und Append leeren.
     */
    public static function compactTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);
        if (!file_exists($file)) return false;

        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile) {

            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $ops  = self::readAppendOps($appendFile);
            if (empty($ops)) return true;

            $full = self::applyOps($base, $ops);

            if (!self::writeTable($file, $full)) return false;

            // Append leeren (line-based => einfach leer schreiben)
            $tmp = $appendFile . '.' . uniqid('tmp_', true);
            if (@file_put_contents($tmp, "", LOCK_EX) === false) return false;
            if (!@rename($tmp, $appendFile)) {
                @unlink($tmp);
                return false;
            }

            $meta = self::readMeta($metaFile);

            $maxId = (int)($meta["last_id"] ?? 0);
            $rows = 0;

            foreach ($full as $i => $r) {
                if (!is_array($r)) continue;
                if ($i === 0 && self::isHeaderRow($r)) continue;

                $rows++;
                if (isset($r["id"])) $maxId = max($maxId, (int)$r["id"]);
            }

            $meta["rows"] = $rows;
            $meta["last_id"] = $maxId;
            $meta["append_ops"] = 0;

            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }

    public static function deleteAll(string $database): bool {
        $ok     = true;
        $tables = self::listTables($database);

        foreach ($tables as $tbl) {
            if (!self::deleteTable($database, $tbl)) {
                $ok = false;
                break;
            }
        }

        self::removeTableIndexIfExists($database);

        if (!self::deleteDatabase($database)) {
            $ok = false;
        }

        return $ok;
    }

    public static function nextID(string $database, string $table): int {
        $file = self::makePath($database, $table);
        if (!file_exists($file)) return 0;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($metaFile) {
            $meta = self::readMeta($metaFile);
            return (int)($meta["last_id"] ?? 0) + 1;
        });

        return is_int($res) ? $res : 0;
    }

    public static function getKeys(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (empty($db) || !isset($db[0]) || !is_array($db[0])) {
            return [];
        }

        return array_keys($db[0]);
    }
}

// Notiz von mir (Markus Müller, entwickler des ganzen mülls): "Alles, hauptsache kein SQL"
