<?php

// HERZ STÜCK !!!!!!

class GBDB {
    /**
     * Erstellt den Pfad zur Datenbank / Tabelle
     * @internal Used by Framework
     */
    private static function makePath(string $database, string $table): string {
        $table    = Format::cleanString($table);
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $table    = Crypt::encode($table);
            $database = Crypt::encode($database);
        }

        $table    .= Vars::data_extension();
        $database  = Vars::DB_PATH() . $database . "/";

        return $database . $table;
    }

    /**
     * Liest eine Tabelle sicher ein (inkl. Crypt)
     */
    private static function ini(string $file): array {
        if (!is_file($file)) {
            return [];
        }

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

        if (!is_array($db)) {
            return [];
        }

        return $db;
    }

    /**
     * Schreibt eine Tabelle sicher (atomic + optional crypt)
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

        if (Vars::crypt_data()) {
            $payload = Crypt::encode($json);
        } else {
            $payload = $json;
        }

        // Atomic write
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

    /**
     * Generiert die ID für einen nächsten Eintrag
     * @internal used by Framework
     */
    private static function genID(string $file): int {
        $database = self::ini($file);

        $id = 0;

        foreach ($database as $r) {
            if (isset($r["id"])) {
                $id = (int)$r["id"] + 1;
            }
        }

        return $id;
    }

    /**
     * Erstellt eine GBDB Datenbank
     */
    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if (Vars::crypt_data()) {
            $name = Crypt::encode($name);
        }

        $base = Vars::DB_PATH();

        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }

        $path = $base . $name;

        if (!is_dir($path)) {
            return @mkdir($path, 0777);
        }

        return false;
    }

    /**
     * Löscht eine GBDB Datenbank (nur wenn diese leer ist)
     */
    public static function deleteDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if (Vars::crypt_data()) {
            $name = Crypt::encode($name);
        }

        $path = Vars::DB_PATH() . $name;

        if (is_dir($path)) {
            // Nur löschen, wenn leer
            $files = scandir($path);

            if ($files && count(array_diff($files, ['.', '..'])) === 0) {
                return @rmdir($path);
            }
        }

        return false;
    }

    /**
     * Erstellt eine GBDB Tabelle in einer GBDB Datenbank
     */
    public static function createTable(string $database, string $table, array $cols): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            // Header-Zeile
            $header = ["id" => -1];

            foreach ($cols as $col) {
                $header[$col] = "-header-";
            }

            $data = [$header];

            return self::writeTable($file, $data);
        }

        return false;
    }

    /**
     * Löscht eine GBDB Tabelle
     */
    public static function deleteTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return false;
    }

    /**
     * Fügt Daten in eine GBDB Tabelle ein
     * @return int Neue ID oder -1 bei Fehler
     */
    public static function insertData(string $database, string $table, mixed $data): int {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return -1;
        }

        $table_data = self::ini($file);

        if (empty($table_data)) {
            // Fallback: Header anhand der Keys erstellen
            $header = ["id" => -1];

            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if ($key !== 'id') {
                        $header[$key] = "-header-";
                    }
                }
            }

            $table_data[] = $header;
        }

        // Neue ID vergeben, falls nicht vorhanden
        if (!isset($data['id'])) {
            $_id        = self::genID($file);
            $data['id'] = $_id;
        } else {
            $_id = (int)$data['id'];
        }

        // Spaltenanzahl prüfen
        if (!isset($table_data[0]) || !is_array($table_data[0])) {
            return -1;
        }

        if (count($data) !== count($table_data[0])) {
            return -1;
        }

        // Neue Zeile in richtiger Spaltenreihenfolge aufbauen
        $new_row = [];

        foreach ($table_data[0] as $col => $value) {
            $new_row[$col] = array_key_exists($col, $data) ? $data[$col] : $value;
        }

        $table_data[] = $new_row;

        if (!self::writeTable($file, $table_data)) {
            return -1;
        }

        return $_id;
    }

    /**
     * Entfernt Daten aus einer GBDB Tabelle
     */
    public static function deleteData(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (empty($db)) {
            return false;
        }

        $return = false;

        foreach ($db as $i => $r) {
            if (!isset($r[$where])) {
                continue;
            }

            if ($r[$where] == $is) {
                unset($db[$i]);
                $return = true;
            }
        }

        // Indexe neu setzen
        if ($return) {
            $db = array_values($db);
            return self::writeTable($file, $db);
        }

        return false;
    }

    /**
     * Bearbeitet Daten in einer GBDB Tabelle
     */
    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData): bool {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (empty($db)) {
            return false;
        }

        $return = false;

        foreach ($db as $i => $r) {
            if (!isset($r[$where])) {
                continue;
            }

            if ($r[$where] == $is) {
                foreach ($newData as $col => $value) {
                    if (array_key_exists($col, $db[$i])) {
                        $db[$i][$col] = $value;
                    }
                }

                $return = true;
            }
        }

        if ($return) {
            return self::writeTable($file, $db);
        }

        return false;
    }

    /**
     * Stellt alle Daten aus einer GBDB Tabelle bereit
     */
    public static function getData(
        string $database,
        string $table,
        bool $filter = false,
        mixed $where = "",
        mixed $is = ""
    ): mixed {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (empty($db)) {
            return $filter ? [] : [];
        }

        if ($filter) {
            foreach ($db as $r) {
                if (isset($r[$where]) && $r[$where] == $is) {
                    return $r;
                }
            }

            return [];
        }

        // Header entfernen
        unset($db[0]);

        $db = array_values($db);

        return $db;
    }

    /**
     * Prüft, ob ein Element existiert
     */
    public static function elementExists(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (empty($db)) {
            return false;
        }

        foreach ($db as $r) {
            if (isset($r[$where]) && $r[$where] == $is) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gibt alle Datenbanken zurück, die existieren
     */
    public static function listDBs(): array {
        $d    = Vars::DB_PATH();
        $dirs = [];

        if (!is_dir($d)) {
            return [];
        }

        $tmp = array_filter(scandir($d), function ($f) use ($d) {
            return $f !== '.' && $f !== '..' && is_dir($d . $f);
        });

        foreach ($tmp as $db_name) {
            if (Vars::crypt_data()) {
                $db_name = Crypt::decode($db_name);
            }

            if ($db_name !== null && $db_name !== "") {
                $dirs[] = $db_name;
            }
        }

        return $dirs;
    }

    /**
     * Gibt alle Tabellen aus einer Datenbank zurück
     */
    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $database = Crypt::encode($database);
        }

        $databasePath = Vars::DB_PATH() . $database . "/";

        if (!is_dir($databasePath)) {
            return [];
        }

        $tables = [];
        $order  = $descending ? 1 : 0;

        $tmp = scandir($databasePath, $order);

        foreach ($tmp as $entry) {
            if ($entry === "." || $entry === "..") {
                continue;
            }

            if (!str_ends_with($entry, Vars::data_extension())) {
                continue;
            }

            $table_name = str_replace(Vars::data_extension(), "", $entry);

            if (Vars::crypt_data()) {
                $table_name = Crypt::decode($table_name);
            }

            if ($table_name !== null && $table_name !== "") {
                $tables[] = $table_name;
            }
        }

        return $tables;
    }

    /**
     * Prüft ob value1 / value2 in einem Datensatz existieren (AND/OR)
     */
    public static function inDB2(string $database, string $table, mixed $value1, string $operator, mixed $value2): bool {
        $file        = self::makePath($database, $table);
        $jsonContent = @file_get_contents($file);

        if ($jsonContent === false) {
            return false;
        }

        $db = json_decode($jsonContent, true);

        if (Vars::crypt_data()) {
            $decoded = Crypt::decode($jsonContent);

            if ($decoded === null) {
                return false;
            }

            $db = json_decode($decoded, true);
        }

        if (!is_array($db)) {
            return false;
        }

        foreach ($db as $r) {
            $foundValue1 = false;
            $foundValue2 = false;

            foreach ($r as $value) {
                if ($value == $value1) {
                    $foundValue1 = true;
                }

                if ($value == $value2) {
                    $foundValue2 = true;
                }
            }

            if ($operator === 'AND' && $foundValue1 && $foundValue2) {
                return true;
            }

            if ($operator === 'OR' && ($foundValue1 || $foundValue2)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Löscht eine Datenbank inklusive aller Tabellen darin
     */
    public static function deleteAll(string $database): bool {
        $ok      = true;
        $tables  = self::listTables($database);

        foreach ($tables as $tbl) {
            if (!self::deleteTable($database, $tbl)) {
                $ok = false;
                break;
            }
        }

        if (!self::deleteDatabase($database)) {
            $ok = false;
        }

        return $ok;
    }

    /**
     * Gibt die nächste ID für einen Datensatz zurück
     */
    public static function nextID(string $database, string $table): int {
        $file = self::makePath($database, $table);

        if (file_exists($file)) {
            return self::genID($file);
        }

        return 0;
    }

    public static function getKeys(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (empty($db) || !isset($db[0])) {
            return [];
        }

        // Header holen
        $headerRow = $db[0]; // ["id" => -1, "name" => "-header-", ...]

        // Keys extrahieren
        return array_keys($headerRow);
    }
}
