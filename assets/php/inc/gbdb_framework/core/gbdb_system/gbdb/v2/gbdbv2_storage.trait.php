<?php

trait GBDBv2_StorageTrait {

    /**
     * Gibt den Pfad der aktuellen Instanz zurück.
     * @param bool $ensure Übergabewert.
     * @return string Rückgabewert.
     */
    private static function instancePath(bool $ensure = false): string {
        $instance = self::instanceName();

        if (Vars::crypt_data()) {
            $instanceToken = self::getInstanceToken($instance, $ensure);

            if ($instanceToken === null) {
                return Vars::DB_PATH() . "__missing__/";
            }

            $instance = $instanceToken;
        }

        return Vars::DB_PATH() . $instance . "/";
    }


    /**
     * Baut den Tabellenpfad.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return string Rückgabewert.
     */
    private static function makePath(string $database, string $table, bool $ensure = false): string {
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if (Vars::crypt_data()) {
            $dbToken = self::getDbToken($database, $ensure);
            $tbToken = self::getTableToken($database, $table, $ensure);

            if ($dbToken === null || $tbToken === null) {
                return Vars::DB_PATH() . "__missing__/" . "__missing__" . Vars::data_extension();
            }

            $database = $dbToken;
            $table = $tbToken;
        }

        return self::instancePath($ensure) . $database . "/" . $table . Vars::data_extension();
    }


    /**
     * Liest eine JSON/DB-Datei.
     * @param string $file Übergabewert.
     * @return array Rückgabewert.
     */
    private static function ini(string $file): array {
        if (!is_file($file)) {
            return [];
        }

        $raw = @file_get_contents($file);

        if ($raw === false) {
            error_log("[GBDBv2] Konnte Datei nicht lesen: {$file}");
            return [];
        }

        if (Vars::crypt_data()) {
            $decoded = Crypt::decode($raw);

            if ($decoded === null) {
                error_log("[GBDBv2] Crypt::decode() fehlgeschlagen für: {$file}");
                return [];
            }

            $db = json_decode($decoded, true);
        } else {
            $db = json_decode($raw, true);
        }

        return is_array($db) ? $db : [];
    }


    /**
     * Schreibt eine Tabelle atomar.
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
            error_log("[GBDBv2] json_encode() fehlgeschlagen für: {$file}");
            return false;
        }

        $payload = Vars::crypt_data() ? Crypt::encode($json) : $json;

        return GBDBStorage::atomicWrite($file, $payload);
    }


    /**
     * Gibt die Lock-Datei einer Tabelle zurück.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return string Rückgabewert.
     */
    private static function lockFileForTable(string $database, string $table, bool $ensure = false): string {
        return self::makePath($database, $table, $ensure) . ".lock";
    }


    /**
     * Gibt die Meta-Datei einer Tabelle zurück.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return string Rückgabewert.
     */
    private static function metaFileForTable(string $database, string $table, bool $ensure = false): string {
        $dataFile = self::makePath($database, $table, $ensure);
        $dir = dirname($dataFile) . "/";

        if (!Vars::crypt_data()) {
            $t = Format::cleanString($table);
            return $dir . "__meta__" . $t . Vars::data_extension();
        }

        $tbToken = self::getTableToken($database, $table, $ensure);

        if ($tbToken === null) {
            return $dir . self::nameToken("__meta__|__missing__", "meta") . Vars::data_extension();
        }

        return $dir . self::nameToken("__meta__|" . $tbToken, "meta") . Vars::data_extension();
    }


    /**
     * Gibt die Append-Datei einer Tabelle zurück.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return string Rückgabewert.
     */
    private static function appendFileForTable(string $database, string $table, bool $ensure = false): string {
        $dataFile = self::makePath($database, $table, $ensure);
        $dir = dirname($dataFile) . "/";

        if (!Vars::crypt_data()) {
            $t = Format::cleanString($table);
            return $dir . "__append__" . $t . Vars::data_extension();
        }

        $tbToken = self::getTableToken($database, $table, $ensure);

        if ($tbToken === null) {
            return $dir . self::nameToken("__append__|__missing__", "meta") . Vars::data_extension();
        }

        return $dir . self::nameToken("__append__|" . $tbToken, "meta") . Vars::data_extension();
    }


    /**
     * Führt eine Aktion mit Tabellen-Lock aus.
     * @param string $lockFile Übergabewert.
     * @param callable $fn Übergabewert.
     * @return mixed Rückgabewert.
     */
    private static function withTableLock(string $lockFile, callable $fn): mixed {
        $dir = dirname($lockFile);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $handle = @fopen($lockFile, "c+");

        if (!$handle) {
            error_log("[GBDBv2] Konnte Lockfile nicht öffnen: {$lockFile}");
            return false;
        }

        try {
            if (!@flock($handle, LOCK_EX)) {
                error_log("[GBDBv2] Konnte Lock nicht setzen: {$lockFile}");
                return false;
            }

            return $fn();
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }


    /**
     * Liest die Meta-Daten einer Tabelle.
     * @param string $metaFile Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readMeta(string $metaFile): array {
        $meta = self::ini($metaFile);

        if (isset($meta[0]) && is_array($meta[0])) {
            return GBDBStorage::normalizeMeta($meta[0]);
        }

        return GBDBStorage::normalizeMeta();
    }


    /**
     * Schreibt die Meta-Daten einer Tabelle.
     * @param string $metaFile Übergabewert.
     * @param array $meta Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeMeta(string $metaFile, array $meta): bool {
        $meta = GBDBStorage::touchMeta($meta);
        return self::writeTable($metaFile, [$meta]);
    }


    /**
     * Prüft, ob eine Zeile eine Header-Zeile ist.
     * @param array $row Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function isHeaderRow(array $row): bool {
        return isset($row["id"]) && (int)$row["id"] === -1;
    }


    /**
     * Stellt sicher, dass eine Tabelle einen Header hat.
     * @param array $tableData Übergabewert.
     * @param array $cols Übergabewert.
     * @return void Rückgabewert.
     */
    private static function ensureHeader(array &$tableData, array $cols): void {
        if (!empty($tableData) && isset($tableData[0]) && is_array($tableData[0])) {
            return;
        }

        $header = ["id" => -1];

        foreach ($cols as $col) {
            $col = (string)$col;

            if ($col === "" || $col === "id") {
                continue;
            }

            $header[$col] = "-header-";
        }

        $tableData = [$header];
    }


    /**
     * Baut eine Tabellenzeile aus Header und Daten.
     * @param array $header Übergabewert.
     * @param array $data Übergabewert.
     * @param int $id Übergabewert.
     * @return array Rückgabewert.
     */
    private static function buildRowFromHeader(array $header, array $data, int $id): array {
        $row = [];

        foreach ($header as $col => $default) {
            if ($col === "id") {
                continue;
            }

            $row[$col] = array_key_exists($col, $data) ? $data[$col] : $default;
        }

        $row["id"] = $id;

        return $row;
    }


    /**
     * Fügt eine Append-Operation hinzu.
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

        if ($json === false) {
            return false;
        }

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
     * Liest alle Append-Operationen.
     * @param string $appendFile Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readAppendOps(string $appendFile): array {
        GBDBStorage::recoverWal($appendFile);

        if (!is_file($appendFile)) {
            return [];
        }

        $handle = @fopen($appendFile, "r");

        if (!$handle) {
            return [];
        }

        $ops = [];

        try {
            while (!feof($handle)) {
                $line = fgets($handle);

                if ($line === false) {
                    break;
                }

                $line = trim($line);

                if ($line === "") {
                    continue;
                }

                $json = $line;

                if (Vars::crypt_data()) {
                    $decoded = Crypt::decode($line);

                    if ($decoded === null) {
                        error_log("[GBDBv2] Append decode fehlgeschlagen: {$appendFile}");
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
            @fclose($handle);
        }

        return $ops;
    }


    /**
     * Wendet Append-Operationen auf Basisdaten an.
     * @param array $base Übergabewert.
     * @param array $ops Übergabewert.
     * @return array Rückgabewert.
     */
    private static function applyOps(array $base, array $ops): array {
        if (empty($base)) {
            return $base;
        }

        $header = isset($base[0]) && is_array($base[0]) && self::isHeaderRow($base[0]) ? $base[0] : [];
        $normalize = function (array $row) use (&$header): array {
            if (empty($header)) {
                return $row;
            }

            $tmp = [];

            foreach ($header as $key => $default) {
                if ($key === "id") {
                    continue;
                }

                $tmp[$key] = array_key_exists($key, $row) ? $row[$key] : $default;
            }

            if (isset($row["id"])) {
                $tmp["id"] = (int)$row["id"];
            }

            return $tmp;
        };

        $idIndex = [];

        foreach ($base as $i => $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($i === 0 && self::isHeaderRow($row)) {
                continue;
            }

            if (isset($row["id"])) {
                $idIndex[(int)$row["id"]] = $i;
            }
        }

        foreach ($ops as $op) {
            $type = $op["op"] ?? "";

            if ($type === "ins" && isset($op["row"]) && is_array($op["row"])) {
                $row = $normalize($op["row"]);

                if (isset($row["id"])) {
                    $id = (int)$row["id"];

                    if (isset($idIndex[$id])) {
                        $old = is_array($base[$idIndex[$id]]) ? $base[$idIndex[$id]] : [];
                        $base[$idIndex[$id]] = $normalize(array_merge($old, $row));
                    } else {
                        $base[] = $row;
                        $idIndex[$id] = count($base) - 1;
                    }
                }
            }

            if ($type === "upd" && isset($op["id"])) {
                $id = (int)$op["id"];

                if (!isset($idIndex[$id])) {
                    continue;
                }

                if (!isset($op["set"]) || !is_array($op["set"])) {
                    continue;
                }

                foreach ($op["set"] as $key => $value) {
                    if ($key === "id") {
                        continue;
                    }

                    if (empty($header) || array_key_exists($key, $header) || array_key_exists($key, $base[$idIndex[$id]])) {
                        $base[$idIndex[$id]][$key] = $value;
                    }
                }

                if (!empty($header) && is_array($base[$idIndex[$id]])) {
                    $base[$idIndex[$id]] = $normalize($base[$idIndex[$id]]);
                }
            }

            if ($type === "del" && isset($op["id"])) {
                $id = (int)$op["id"];

                if (!isset($idIndex[$id])) {
                    continue;
                }

                unset($base[$idIndex[$id]]);
                $base = array_values($base);

                $idIndex = [];

                foreach ($base as $i => $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    if ($i === 0 && self::isHeaderRow($row)) {
                        continue;
                    }

                    if (isset($row["id"])) {
                        $idIndex[(int)$row["id"]] = $i;
                    }
                }
            }
        }

        return $base;
    }
}
