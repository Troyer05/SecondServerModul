<?php

class GBDBv2 {
    private const SCHEMA_FILE = "assets/php/inc/gbdb_framework/json/schema_v2.json";

    private static string $instance = "default";

    /**
     * Gibt den Projekt-Root zurück.
     * @return string Rückgabewert.
     */
    private static function rootPath(): string {
        return dirname(__DIR__, 5);
    }

    /**
     * Gibt den Pfad zur Schema-Datei zurück.
     * @return string Rückgabewert.
     */
    private static function schemaPath(): string {
        return self::rootPath() . "/" . self::SCHEMA_FILE;
    }

    /**
     * Gibt die aktive Instanz bereinigt zurück.
     * @return string Rückgabewert.
     */
    private static function instanceName(): string {
        $instance = Format::cleanString(self::$instance);

        if ($instance === "") {
            return "default";
        }

        return $instance;
    }

    /**
     * Setzt die aktive Instanz.
     * @param string $instance Übergabewert.
     * @return void Rückgabewert.
     */
    public static function setInstance(string $instance): void {
        $instance = Format::cleanString($instance);

        if ($instance === "") {
            $instance = "default";
        }

        self::$instance = $instance;
    }

    /**
     * Alias für setInstance.
     * @param string $instance Übergabewert.
     * @return void Rückgabewert.
     */
    public static function instance(string $instance): void {
        self::setInstance($instance);
    }

    /**
     * Gibt die aktive Instanz zurück.
     * @return string Rückgabewert.
     */
    public static function getInstance(): string {
        return self::instanceName();
    }

    /**
     * Liest die Schema-Datei.
     * @return array Rückgabewert.
     */
    private static function readSchema(): array {
        $file = self::schemaPath();

        if (!is_file($file)) {
            return [];
        }

        $json = json_decode((string)@file_get_contents($file), true);

        return is_array($json) ? $json : [];
    }

    /**
     * Schreibt die Schema-Datei.
     * @param array $schema Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeSchema(array $schema): bool {
        $file = self::schemaPath();
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        ksort($schema, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($schema as $instance => $databases) {
            if (!is_array($databases)) {
                unset($schema[$instance]);
                continue;
            }

            ksort($databases, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($databases as $database => $tables) {
                if (!is_array($tables)) {
                    unset($databases[$database]);
                    continue;
                }

                ksort($tables, SORT_NATURAL | SORT_FLAG_CASE);
                $databases[$database] = $tables;
            }

            $schema[$instance] = $databases;
        }

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) {
            return false;
        }

        return @file_put_contents($file, $json . "\n", LOCK_EX) !== false;
    }

    /**
     * Setzt eine Tabelle im Schema.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param array $cols Übergabewert.
     * @return void Rückgabewert.
     */
    private static function setSchemaTable(string $database, string $table, array $cols): void {
        $instance = self::instanceName();
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if ($instance === "" || $database === "" || $table === "") {
            return;
        }

        $schema = self::readSchema();

        if (!isset($schema[$instance]) || !is_array($schema[$instance])) {
            $schema[$instance] = [];
        }

        if (!isset($schema[$instance][$database]) || !is_array($schema[$instance][$database])) {
            $schema[$instance][$database] = [];
        }

        if (!isset($schema[$instance][$database][$table]) || !is_array($schema[$instance][$database][$table])) {
            $schema[$instance][$database][$table] = [];
        }

        foreach ($cols as $col => $default) {
            if (is_int($col)) {
                $col = (string)$default;
                $default = "";
            }

            $col = trim((string)$col);

            if ($col === "" || $col === "id") {
                continue;
            }

            if (!array_key_exists($col, $schema[$instance][$database][$table])) {
                $schema[$instance][$database][$table][$col] = $default;
            }
        }

        self::writeSchema($schema);
    }

    /**
     * Entfernt eine Tabelle aus dem Schema.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaTable(string $database, string $table): void {
        $instance = self::instanceName();
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if ($instance === "" || $database === "" || $table === "") {
            return;
        }

        $schema = self::readSchema();

        if (isset($schema[$instance][$database][$table])) {
            unset($schema[$instance][$database][$table]);
        }

        if (isset($schema[$instance][$database]) && empty($schema[$instance][$database])) {
            unset($schema[$instance][$database]);
        }

        if (isset($schema[$instance]) && empty($schema[$instance])) {
            unset($schema[$instance]);
        }

        self::writeSchema($schema);
    }

    /**
     * Entfernt eine Datenbank aus dem Schema.
     * @param string $database Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaDatabase(string $database): void {
        $instance = self::instanceName();
        $database = Format::cleanString($database);

        if ($instance === "" || $database === "") {
            return;
        }

        $schema = self::readSchema();

        if (isset($schema[$instance][$database])) {
            unset($schema[$instance][$database]);
        }

        if (isset($schema[$instance]) && empty($schema[$instance])) {
            unset($schema[$instance]);
        }

        self::writeSchema($schema);
    }

    /**
     * Entfernt eine Instanz aus dem Schema.
     * @param string $instance Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaInstance(string $instance): void {
        $instance = Format::cleanString($instance);

        if ($instance === "") {
            return;
        }

        $schema = self::readSchema();

        if (isset($schema[$instance])) {
            unset($schema[$instance]);
            self::writeSchema($schema);
        }
    }

    /**
     * Komprimiert eine Tabelle automatisch.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return void Rückgabewert.
     */
    private static function autoCompact(string $database, string $table): void {
        self::compactTable($database, $table);
    }

    /**
     * Erzeugt einen sicheren Namen.
     * @param string $plain Übergabewert.
     * @param string $ns Übergabewert.
     * @return string Rückgabewert.
     */
    private static function nameToken(string $plain, string $ns = "g"): string {
        $plain = (string)$plain;
        $key = (string)Vars::cryptKey();

        $data = $ns . "|" . $plain;
        $raw = hash_hmac("sha256", $data, $key, true);
        $b64 = base64_encode($raw);
        $safe = rtrim(strtr($b64, "+/", "-_"), "=");

        return "gb_" . $safe;
    }

    /**
     * Gibt die globale Instanz-Index-Datei zurück.
     * @return string Rückgabewert.
     */
    private static function instanceIndexFile(): string {
        return Vars::DB_PATH() . self::nameToken("__instance_index__", "meta") . Vars::data_extension();
    }

    /**
     * Gibt die Datenbank-Index-Datei einer Instanz zurück.
     * @param string $instanceToken Übergabewert.
     * @return string Rückgabewert.
     */
    private static function dbIndexFileByInstanceToken(string $instanceToken): string {
        $dir = Vars::DB_PATH() . $instanceToken . "/";
        return $dir . self::nameToken("__db_index__", "meta") . Vars::data_extension();
    }

    /**
     * Gibt die Tabellen-Index-Datei einer Datenbank zurück.
     * @param string $instanceToken Übergabewert.
     * @param string $dbToken Übergabewert.
     * @return string Rückgabewert.
     */
    private static function tableIndexFileByTokens(string $instanceToken, string $dbToken): string {
        $dir = Vars::DB_PATH() . $instanceToken . "/" . $dbToken . "/";
        return $dir . self::nameToken("__table_index__", "meta") . Vars::data_extension();
    }

    /**
     * Liest eine Index-Datei.
     * @param string $file Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readIndex(string $file): array {
        $rows = self::ini($file);

        if (empty($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return [];
        }

        unset($rows[0]);

        $rows = array_values($rows);
        $map = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (!isset($row["plain"], $row["token"])) {
                continue;
            }

            $plain = (string)$row["plain"];
            $token = (string)$row["token"];

            if ($plain !== "" && $token !== "") {
                $map[$plain] = $token;
            }
        }

        return $map;
    }

    /**
     * Schreibt eine Index-Datei.
     * @param string $file Übergabewert.
     * @param array $map Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeIndex(string $file, array $map): bool {
        $db = [];

        $db[] = [
            "id" => -1,
            "plain" => "-header-",
            "token" => "-header-"
        ];

        $id = 0;

        foreach ($map as $plain => $token) {
            $db[] = [
                "id" => $id++,
                "plain" => (string)$plain,
                "token" => (string)$token
            ];
        }

        return self::writeTable($file, $db);
    }

    /**
     * Gibt den Token einer Instanz zurück.
     * @param string $instancePlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getInstanceToken(string $instancePlain, bool $ensure = false): ?string {
        $instancePlain = Format::cleanString($instancePlain);

        if ($instancePlain === "") {
            return null;
        }

        if (!Vars::crypt_data()) {
            return $instancePlain;
        }

        $idxFile = self::instanceIndexFile();
        $map = self::readIndex($idxFile);

        if (isset($map[$instancePlain])) {
            return $map[$instancePlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken("inst:" . $instancePlain, "inst");
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken("inst:" . $instancePlain . "#" . $n, "inst");
                $n++;
            } while (isset($used[$token2]));

            $token = $token2;
        }

        $map[$instancePlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }

    /**
     * Gibt den Token einer Datenbank zurück.
     * @param string $dbPlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getDbToken(string $dbPlain, bool $ensure = false): ?string {
        $instancePlain = self::instanceName();
        $dbPlain = Format::cleanString($dbPlain);

        if ($instancePlain === "" || $dbPlain === "") {
            return null;
        }

        if (!Vars::crypt_data()) {
            return $dbPlain;
        }

        $instanceToken = self::getInstanceToken($instancePlain, $ensure);

        if ($instanceToken === null) {
            return null;
        }

        $idxFile = self::dbIndexFileByInstanceToken($instanceToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$dbPlain])) {
            return $map[$dbPlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken("db:" . $instancePlain . "|" . $dbPlain, "db");
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken("db:" . $instancePlain . "|" . $dbPlain . "#" . $n, "db");
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

    /**
     * Gibt den Token einer Tabelle zurück.
     * @param string $dbPlain Übergabewert.
     * @param string $tablePlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getTableToken(string $dbPlain, string $tablePlain, bool $ensure = false): ?string {
        $instancePlain = self::instanceName();
        $dbPlain = Format::cleanString($dbPlain);
        $tablePlain = Format::cleanString($tablePlain);

        if ($instancePlain === "" || $dbPlain === "" || $tablePlain === "") {
            return null;
        }

        if (!Vars::crypt_data()) {
            return $tablePlain;
        }

        $instanceToken = self::getInstanceToken($instancePlain, $ensure);
        $dbToken = self::getDbToken($dbPlain, $ensure);

        if ($instanceToken === null || $dbToken === null) {
            return null;
        }

        $idxFile = self::tableIndexFileByTokens($instanceToken, $dbToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            return $map[$tablePlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken("tbl:" . $instancePlain . "|" . $dbPlain . "|" . $tablePlain, "tbl");
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken("tbl:" . $instancePlain . "|" . $dbPlain . "|" . $tablePlain . "#" . $n, "tbl");
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

    /**
     * Entfernt eine Tabelle aus dem Tabellen-Index.
     * @param string $dbPlain Übergabewert.
     * @param string $tablePlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropTableFromIndex(string $dbPlain, string $tablePlain): void {
        if (!Vars::crypt_data()) {
            return;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);
        $dbToken = self::getDbToken($dbPlain, false);

        if ($instanceToken === null || $dbToken === null) {
            return;
        }

        $idxFile = self::tableIndexFileByTokens($instanceToken, $dbToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            unset($map[$tablePlain]);
            self::writeIndex($idxFile, $map);
        }
    }

    /**
     * Entfernt eine Datenbank aus dem Datenbank-Index.
     * @param string $dbPlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropDatabaseFromIndex(string $dbPlain): void {
        if (!Vars::crypt_data()) {
            return;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);

        if ($instanceToken === null) {
            return;
        }

        $idxFile = self::dbIndexFileByInstanceToken($instanceToken);
        $map = self::readIndex($idxFile);

        if (isset($map[$dbPlain])) {
            unset($map[$dbPlain]);
            self::writeIndex($idxFile, $map);
        }
    }

    /**
     * Entfernt eine Instanz aus dem Instanz-Index.
     * @param string $instancePlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropInstanceFromIndex(string $instancePlain): void {
        if (!Vars::crypt_data()) {
            return;
        }

        $idxFile = self::instanceIndexFile();
        $map = self::readIndex($idxFile);

        if (isset($map[$instancePlain])) {
            unset($map[$instancePlain]);
            self::writeIndex($idxFile, $map);
        }
    }

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
        $tmp = $file . "." . uniqid("tmp_", true);

        if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
            error_log("[GBDBv2] Konnte Temp-Datei nicht schreiben: {$tmp}");
            return false;
        }

        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            error_log("[GBDBv2] Konnte {$tmp} nicht nach {$file} verschieben");
            return false;
        }

        return true;
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
            return $meta[0];
        }

        return [
            "last_id" => 0,
            "rows" => 0,
            "append_ops" => 0,
            "indexes" => [],
            "created_at" => time(),
            "updated_at" => time()
        ];
    }

    /**
     * Schreibt die Meta-Daten einer Tabelle.
     * @param string $metaFile Übergabewert.
     * @param array $meta Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeMeta(string $metaFile, array $meta): bool {
        $meta["updated_at"] = time();
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

        return @file_put_contents($appendFile, $line, FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Liest alle Append-Operationen.
     * @param string $appendFile Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readAppendOps(string $appendFile): array {
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

                    if (array_key_exists($key, $base[$idIndex[$id]])) {
                        $base[$idIndex[$id]][$key] = $value;
                    }
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

    /**
     * Erstellt eine Instanz.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createInstance(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        $base = Vars::DB_PATH();

        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }

        $dirName = Vars::crypt_data()
            ? self::getInstanceToken($name, true)
            : $name;

        if ($dirName === null) {
            return false;
        }

        $path = $base . $dirName;

        if (!is_dir($path)) {
            return @mkdir($path, 0777, true);
        }

        return true;
    }

    /**
     * Löscht eine Instanz.
     * @param string $name Übergabewert.
     * @param bool $force Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteInstance(string $name, bool $force = false): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        $old = self::getInstance();
        self::setInstance($name);

        if ($force) {
            $databases = self::listDBs();

            foreach ($databases as $database) {
                self::deleteAll($database);
            }
        }

        $dirName = Vars::crypt_data()
            ? self::getInstanceToken($name, false)
            : $name;

        if ($dirName === null) {
            self::setInstance($old);
            return false;
        }

        $path = Vars::DB_PATH() . $dirName;

        $ok = false;

        if (is_dir($path)) {
            $files = scandir($path);
            $rest = $files ? array_diff($files, [".", ".."]) : [];

            if (Vars::crypt_data()) {
                $idx = basename(self::dbIndexFileByInstanceToken($dirName));
                $rest = array_values($rest);

                if (count($rest) === 1 && $rest[0] === $idx) {
                    @unlink($path . "/" . $idx);
                    $rest = [];
                }
            }

            if (count($rest) === 0) {
                $ok = @rmdir($path);
            }
        }

        if ($ok) {
            self::dropInstanceFromIndex($name);
            self::dropSchemaInstance($name);
        }

        self::setInstance($old);

        return $ok;
    }

    /**
     * Listet alle Instanzen.
     * @return array Rückgabewert.
     */
    public static function listInstances(): array {
        $base = Vars::DB_PATH();

        if (!is_dir($base)) {
            return [];
        }

        if (!Vars::crypt_data()) {
            $instances = [];
            $tmp = scandir($base);

            if (!$tmp) {
                return [];
            }

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") {
                    continue;
                }

                if (is_dir($base . $entry)) {
                    $instances[] = $entry;
                }
            }

            sort($instances, SORT_NATURAL | SORT_FLAG_CASE);

            return $instances;
        }

        $map = self::readIndex(self::instanceIndexFile());
        $out = [];

        foreach ($map as $plain => $token) {
            if (is_dir($base . $token)) {
                $out[] = $plain;
            }
        }

        sort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    /**
     * Erstellt eine Datenbank innerhalb der aktiven Instanz.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        self::createInstance(self::instanceName());

        $instancePath = self::instancePath(true);

        if (!is_dir($instancePath)) {
            @mkdir($instancePath, 0777, true);
        }

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, true)
            : $name;

        if ($dirName === null) {
            return false;
        }

        $path = $instancePath . $dirName;

        if (!is_dir($path)) {
            return @mkdir($path, 0777, true);
        }

        return true;
    }

    /**
     * Löscht eine leere Datenbank.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") {
            return false;
        }

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, false)
            : $name;

        if ($dirName === null) {
            return false;
        }

        $path = self::instancePath(false) . $dirName;

        if (is_dir($path)) {
            $files = scandir($path);

            if ($files) {
                $rest = array_diff($files, [".", ".."]);

                if (Vars::crypt_data()) {
                    $instanceToken = self::getInstanceToken(self::instanceName(), false);

                    if ($instanceToken !== null) {
                        $idx = basename(self::tableIndexFileByTokens($instanceToken, $dirName));
                        $rest = array_values($rest);

                        if (count($rest) === 1 && $rest[0] === $idx) {
                            @unlink($path . "/" . $idx);
                            $rest = [];
                        }
                    }
                }

                if (count($rest) === 0) {
                    $ok = @rmdir($path);

                    if ($ok) {
                        self::dropDatabaseFromIndex($name);
                    }

                    return $ok;
                }
            }
        }

        return false;
    }

    /**
     * Erstellt eine Tabelle.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param array $cols Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createTable(string $database, string $table, array $cols): bool {
        self::createDatabase($database);

        $file = self::makePath($database, $table, true);
        $lockFile = self::lockFileForTable($database, $table, true);
        $metaFile = self::metaFileForTable($database, $table, true);
        $appendFile = self::appendFileForTable($database, $table, true);

        $res = (bool)self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $cols) {
            if (file_exists($file)) {
                return false;
            }

            $header = ["id" => -1];

            foreach ($cols as $col) {
                $col = (string)$col;

                if ($col === "" || $col === "id") {
                    continue;
                }

                $header[$col] = "-header-";
            }

            if (!self::writeTable($file, [$header])) {
                return false;
            }

            self::writeMeta($metaFile, [
                "last_id" => 0,
                "rows" => 0,
                "append_ops" => 0,
                "indexes" => [],
                "created_at" => time(),
                "updated_at" => time()
            ]);

            if (!is_file($appendFile)) {
                @file_put_contents($appendFile, "", LOCK_EX);
            }

            return true;
        });

        if ($res) {
            self::setSchemaTable($database, $table, $cols);
            self::autoCompact($database, $table);
        }

        return $res;
    }

    /**
     * Fügt eine Spalte hinzu.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param string $column Übergabewert.
     * @param mixed $default Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function addColumn(string $database, string $table, string $column, mixed $default = ""): bool {
        $column = trim($column);

        if ($column === "" || $column === "id") {
            return false;
        }

        $file = self::makePath($database, $table);
        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = (bool)self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $column, $default) {
            if (!file_exists($file)) {
                return false;
            }

            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            if (empty($full) || !isset($full[0]) || !is_array($full[0]) || !self::isHeaderRow($full[0])) {
                return false;
            }

            $header = $full[0];

            if (array_key_exists($column, $header)) {
                return true;
            }

            $newHeader = [];

            foreach ($header as $key => $value) {
                $newHeader[$key] = $value;

                if ($key === "id") {
                    $newHeader[$column] = "-header-";
                }
            }

            if (!array_key_exists($column, $newHeader)) {
                $newHeader[$column] = "-header-";
            }

            $rebuilt = [$newHeader];
            $rows = 0;

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($i === 0) {
                    continue;
                }

                if (!array_key_exists($column, $row)) {
                    $row[$column] = $default;
                }

                $tmp = [];

                foreach ($newHeader as $key => $_) {
                    if ($key === "id") {
                        continue;
                    }

                    $tmp[$key] = array_key_exists($key, $row) ? $row[$key] : $default;
                }

                $tmp["id"] = isset($row["id"]) ? (int)$row["id"] : 0;
                $rebuilt[] = $tmp;
                $rows++;
            }

            if (!self::writeTable($file, $rebuilt)) {
                return false;
            }

            @file_put_contents($appendFile, "", LOCK_EX);

            $meta = self::readMeta($metaFile);
            $meta["rows"] = $rows;
            $meta["append_ops"] = 0;
            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            self::setSchemaTable($database, $table, [$column => $default]);
            self::autoCompact($database, $table);
        }

        return $res;
    }

    /**
     * Löscht eine Tabelle.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);
        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = (bool)self::withTableLock($lockFile, function () use ($database, $table, $file, $metaFile, $appendFile, $lockFile) {
            if (!file_exists($file)) {
                return false;
            }

            $ok = @unlink($file);

            if ($ok) {
                if (is_file($metaFile)) {
                    @unlink($metaFile);
                }

                if (is_file($appendFile)) {
                    @unlink($appendFile);
                }

                self::dropTableFromIndex($database, $table);

                if (is_file($lockFile)) {
                    @unlink($lockFile);
                }
            }

            return $ok;
        });

        if ($res) {
            self::dropSchemaTable($database, $table);
        }

        return $res;
    }

    /**
     * Fügt Daten ein.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $data Übergabewert.
     * @return int Rückgabewert.
     */
    public static function insertData(string $database, string $table, mixed $data): int {
        if (!is_array($data)) {
            return -1;
        }

        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return -1;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $data) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                self::ensureHeader($base, array_keys($data));

                if (!self::writeTable($file, $base)) {
                    return -1;
                }
            }

            $header = $base[0];
            $meta = self::readMeta($metaFile);
            $next = (int)($meta["last_id"] ?? 0) + 1;

            $id = isset($data["id"]) ? (int)$data["id"] : $next;

            if ($id <= 0) {
                $id = $next;
            }

            $row = self::buildRowFromHeader($header, $data, $id);

            $ok = self::appendOp($appendFile, [
                "op" => "ins",
                "row" => $row,
                "ts" => time()
            ]);

            if (!$ok) {
                return -1;
            }

            $meta["last_id"] = max((int)($meta["last_id"] ?? 0), $id);
            $meta["rows"] = (int)($meta["rows"] ?? 0) + 1;
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + 1;

            self::writeMeta($metaFile, $meta);

            return $id;
        });

        if (is_int($res) && $res > 0) {
            self::autoCompact($database, $table);
        }

        return is_int($res) ? $res : -1;
    }

    /**
     * Löscht Daten.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteData(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return false;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $where, $is) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            $hasHeader = isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]);
            $ids = [];

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($hasHeader && $i === 0) {
                    continue;
                }

                if (isset($row[$where]) && $row[$where] == $is && isset($row["id"])) {
                    $ids[] = (int)$row["id"];
                }
            }

            if (empty($ids)) {
                return false;
            }

            foreach ($ids as $id) {
                if (!self::appendOp($appendFile, [
                    "op" => "del",
                    "id" => $id,
                    "ts" => time()
                ])) {
                    return false;
                }
            }

            $meta = self::readMeta($metaFile);
            $meta["rows"] = max(0, (int)($meta["rows"] ?? 0) - count($ids));
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            self::autoCompact($database, $table);
        }

        return (bool)$res;
    }

    /**
     * Bearbeitet Daten.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @param mixed $newData Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData): bool {
        if (!is_array($newData)) {
            return false;
        }

        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return false;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $where, $is, $newData) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            $header = $full[0];
            $hasHeader = isset($header) && is_array($header) && self::isHeaderRow($header);

            $set = [];

            foreach ($newData as $key => $value) {
                if ($key === "id") {
                    continue;
                }

                if ($hasHeader && array_key_exists($key, $header)) {
                    $set[$key] = $value;
                }
            }

            if (empty($set)) {
                return false;
            }

            $ids = [];

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($hasHeader && $i === 0) {
                    continue;
                }

                if (isset($row[$where]) && $row[$where] == $is && isset($row["id"])) {
                    $ids[] = (int)$row["id"];
                }
            }

            if (empty($ids)) {
                return false;
            }

            foreach ($ids as $id) {
                if (!self::appendOp($appendFile, [
                    "op" => "upd",
                    "id" => $id,
                    "set" => $set,
                    "ts" => time()
                ])) {
                    return false;
                }
            }

            $meta = self::readMeta($metaFile);
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            self::autoCompact($database, $table);
        }

        return (bool)$res;
    }

    /**
     * Holt Daten.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $filter Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return mixed Rückgabewert.
     */
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
            return [];
        }

        $appendFile = self::appendFileForTable($database, $table);
        $ops = self::readAppendOps($appendFile);
        $full = self::applyOps($base, $ops);

        $hasHeader = isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]);

        if ($filter) {
            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($hasHeader && $i === 0) {
                    continue;
                }

                if (isset($row[$where]) && $row[$where] == $is) {
                    return $row;
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

    /**
     * Prüft, ob ein Element existiert.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function elementExists(string $database, string $table, mixed $where, mixed $is): bool {
        $row = self::getData($database, $table, true, $where, $is);
        return is_array($row) && !empty($row);
    }

    /**
     * Listet Datenbanken der aktiven Instanz.
     * @return array Rückgabewert.
     */
    public static function listDBs(): array {
        $instancePath = self::instancePath(false);

        if (!is_dir($instancePath)) {
            return [];
        }

        if (!Vars::crypt_data()) {
            $dirs = [];
            $tmp = scandir($instancePath);

            if (!$tmp) {
                return [];
            }

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") {
                    continue;
                }

                if (is_dir($instancePath . $entry)) {
                    $dirs[] = $entry;
                }
            }

            sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);

            return $dirs;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);

        if ($instanceToken === null) {
            return [];
        }

        $map = self::readIndex(self::dbIndexFileByInstanceToken($instanceToken));
        $out = [];

        foreach ($map as $plain => $token) {
            if (is_dir($instancePath . $token)) {
                $out[] = $plain;
            }
        }

        sort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    /**
     * Listet Tabellen einer Datenbank.
     * @param string $database Übergabewert.
     * @param bool $descending Übergabewert.
     * @return array Rückgabewert.
     */
    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);

        if ($database === "") {
            return [];
        }

        $ext = Vars::data_extension();

        if (!Vars::crypt_data()) {
            $databasePath = self::instancePath(false) . $database . "/";

            if (!is_dir($databasePath)) {
                return [];
            }

            $tables = [];
            $order = $descending ? 1 : 0;
            $tmp = scandir($databasePath, $order);

            if (!$tmp) {
                return [];
            }

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") {
                    continue;
                }

                if (str_ends_with($entry, ".lock")) {
                    continue;
                }

                if (!str_ends_with($entry, $ext)) {
                    continue;
                }

                if (str_starts_with($entry, "__meta__")) {
                    continue;
                }

                if (str_starts_with($entry, "__append__")) {
                    continue;
                }

                if (str_starts_with($entry, "__idx__")) {
                    continue;
                }

                if (str_starts_with($entry, "__idxa__")) {
                    continue;
                }

                $tables[] = str_replace($ext, "", $entry);
            }

            return $tables;
        }

        $instanceToken = self::getInstanceToken(self::instanceName(), false);
        $dbToken = self::getDbToken($database, false);

        if ($instanceToken === null || $dbToken === null) {
            return [];
        }

        $databasePath = self::instancePath(false) . $dbToken . "/";

        if (!is_dir($databasePath)) {
            return [];
        }

        $map = self::readIndex(self::tableIndexFileByTokens($instanceToken, $dbToken));
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
     * Komprimiert eine Tabelle.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function compactTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return false;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);

            if (empty($ops)) {
                return true;
            }

            $full = self::applyOps($base, $ops);

            if (!self::writeTable($file, $full)) {
                return false;
            }

            $tmp = $appendFile . "." . uniqid("tmp_", true);

            if (@file_put_contents($tmp, "", LOCK_EX) === false) {
                return false;
            }

            if (!@rename($tmp, $appendFile)) {
                @unlink($tmp);
                return false;
            }

            $meta = self::readMeta($metaFile);

            $maxId = (int)($meta["last_id"] ?? 0);
            $rows = 0;

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($i === 0 && self::isHeaderRow($row)) {
                    continue;
                }

                $rows++;

                if (isset($row["id"])) {
                    $maxId = max($maxId, (int)$row["id"]);
                }
            }

            $meta["rows"] = $rows;
            $meta["last_id"] = $maxId;
            $meta["append_ops"] = 0;

            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }

    /**
     * Löscht eine komplette Datenbank innerhalb der aktiven Instanz.
     * @param string $database Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteAll(string $database): bool {
        $ok = true;
        $tables = self::listTables($database);

        foreach ($tables as $table) {
            if (!self::deleteTable($database, $table)) {
                $ok = false;
                break;
            }
        }

        if (!self::deleteDatabase($database)) {
            $ok = false;
        }

        if ($ok) {
            self::dropSchemaDatabase($database);
        }

        return $ok;
    }

    /**
     * Gibt die nächste ID zurück.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return int Rückgabewert.
     */
    public static function nextID(string $database, string $table): int {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return 0;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($metaFile) {
            $meta = self::readMeta($metaFile);
            return (int)($meta["last_id"] ?? 0) + 1;
        });

        return is_int($res) ? $res : 0;
    }

    /**
     * Gibt die Keys einer Tabelle zurück.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getKeys(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (empty($db) || !isset($db[0]) || !is_array($db[0])) {
            return [];
        }

        return array_keys($db[0]);
    }

    /**
     * Führt eine GreenQL-Abfrage aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function query(string $script, array $ctx = [], array $params = []): array {
        $ctx["instance"] = self::instanceName();
        return GreenQLv2::run($script, $ctx, $params);
    }

    /**
     * Führt ein GreenQL-Script aus.
     * @param string $path Übergabewert.
     * @param array $params Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $path, array $params = [], array $ctx = []): array {
        if (!file_exists($path)) {
            return [
                "ok" => false,
                "messages" => [
                    ["ok" => false, "text" => "Script nicht gefunden: " . $path]
                ],
                "results" => [],
                "keys" => [],
                "rows" => [],
                "ctx" => [
                    "instance" => self::instanceName(),
                    "db" => GreenQLv2::cleanName((string)($ctx["db"] ?? "")),
                    "table" => GreenQLv2::cleanName((string)($ctx["table"] ?? ""))
                ],
                "vars" => [],
                "refresh" => false
            ];
        }

        $script = file_get_contents($path);

        if ($script === false) {
            return [
                "ok" => false,
                "messages" => [
                    ["ok" => false, "text" => "Script konnte nicht gelesen werden: " . $path]
                ],
                "results" => [],
                "keys" => [],
                "rows" => [],
                "ctx" => [
                    "instance" => self::instanceName(),
                    "db" => GreenQLv2::cleanName((string)($ctx["db"] ?? "")),
                    "table" => GreenQLv2::cleanName((string)($ctx["table"] ?? ""))
                ],
                "vars" => [],
                "refresh" => false
            ];
        }

        $ctx["instance"] = self::instanceName();

        return self::query($script, $ctx, $params);
    }
}
