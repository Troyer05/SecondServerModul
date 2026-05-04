<?php

trait GBDB_StorageTrait {

    /**
     * Verarbeitet die Funktion make path.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return string Rückgabewert.
     */
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


    /**
     * Verarbeitet die Funktion ini.
     * @param string $file Übergabewert.
     * @return array Rückgabewert.
     */
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


    /**
     * Verarbeitet die Funktion write table.
     * @param string $file Übergabewert.
     * @param array $db Übergabewert.
     * @return bool Rückgabewert.
     */
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

        return GBDBStorage::atomicWrite($file, $payload);
    }


    /**
     * Verarbeitet die Funktion lock file for table.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return string Rückgabewert.
     */
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


    /**
     * Verarbeitet die Funktion with table lock.
     * @param string $lockFile Übergabewert.
     * @param callable $fn Übergabewert.
     * @return mixed Rückgabewert.
     */
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


    /**
     * Verarbeitet die Funktion read meta.
     * @param string $metaFile Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readMeta(string $metaFile): array {
        $m = self::ini($metaFile);

        if (isset($m[0]) && is_array($m[0])) {
            return GBDBStorage::normalizeMeta($m[0]);
        }

        return GBDBStorage::normalizeMeta();
    }


    /**
     * Verarbeitet die Funktion write meta.
     * @param string $metaFile Übergabewert.
     * @param array $meta Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeMeta(string $metaFile, array $meta): bool {
        $meta = GBDBStorage::touchMeta($meta);
        return self::writeTable($metaFile, [ $meta ]);
    }


    /**
     * Verarbeitet die Funktion is header row.
     * @param array $row Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function isHeaderRow(array $row): bool {
        return (isset($row["id"]) && (int)$row["id"] === -1);
    }


    /**
     * Verarbeitet die Funktion ensure header.
     * @param array & $tableData Übergabewert.
     * @param array $cols Übergabewert.
     * @return void Rückgabewert.
     */
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


    /**
     * Verarbeitet die Funktion build row from header.
     * @param array $header Übergabewert.
     * @param array $data Übergabewert.
     * @param int $id Übergabewert.
     * @return array Rückgabewert.
     */
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
     * Verarbeitet die Funktion append op.
     * @param string $appendFile Übergabewert.
     * @param array $op Übergabewert.
     * @return bool Rückgabewert.
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

        $tx = "tx_" . bin2hex(random_bytes(8));

        if (!GBDBStorage::wal($appendFile, $op, "prepared", $tx)) {
            return false;
        }

        if (!GBDBStorage::appendLine($appendFile, $line)) {
            GBDBStorage::wal($appendFile, $op, "failed", $tx);
            return false;
        }

        GBDBStorage::wal($appendFile, $op, "committed", $tx);
        return true;
    }


    /**
     * Verarbeitet die Funktion read append ops.
     * @param string $appendFile Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readAppendOps(string $appendFile): array {
        GBDBStorage::recoverWal($appendFile);

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
     * Verarbeitet die Funktion apply ops.
     * @param array $base Übergabewert.
     * @param array $ops Übergabewert.
     * @return array Rückgabewert.
     */
    private static function applyOps(array $base, array $ops): array {
        if (empty($base)) return $base;

        $header = isset($base[0]) && is_array($base[0]) && self::isHeaderRow($base[0]) ? $base[0] : [];
        $normalize = function (array $row) use (&$header): array {
            if (empty($header)) return $row;

            $tmp = [];
            foreach ($header as $key => $default) {
                if ($key === "id") continue;
                $tmp[$key] = array_key_exists($key, $row) ? $row[$key] : $default;
            }

            if (isset($row["id"])) $tmp["id"] = (int)$row["id"];
            return $tmp;
        };

        $idIndex = [];
        foreach ($base as $i => $r) {
            if (!is_array($r)) continue;
            if ($i === 0 && self::isHeaderRow($r)) continue;
            if (isset($r["id"])) $idIndex[(int)$r["id"]] = $i;
        }

        foreach ($ops as $op) {
            $t = $op["op"] ?? "";

            if ($t === "ins" && isset($op["row"]) && is_array($op["row"])) {
                $row = $normalize($op["row"]);
                if (isset($row["id"])) {
                    $id = (int)$row["id"];
                    if (isset($idIndex[$id])) {
                        $base[$idIndex[$id]] = $normalize(array_merge(is_array($base[$idIndex[$id]]) ? $base[$idIndex[$id]] : [], $row));
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
                    if (empty($header) || array_key_exists($k, $header) || array_key_exists($k, $base[$idIndex[$id]])) {
                        $base[$idIndex[$id]][$k] = $v;
                    }
                }

                if (!empty($header) && is_array($base[$idIndex[$id]])) {
                    $base[$idIndex[$id]] = $normalize($base[$idIndex[$id]]);
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
}
