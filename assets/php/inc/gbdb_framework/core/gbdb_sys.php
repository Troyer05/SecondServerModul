<?php

// HERZ STÜCK !!!!!! 

class GBDB {

    /* ============================================================
       NAME-OBFUSCATION (deterministisch) + Index-Mapping
       ============================================================ */

    /**
     * Deterministischer, geheimnisbasierter "Name-Token" (nicht reversibel).
     * -> stabil (immer gleich), aber ohne Key praktisch nicht erratbar.
     */
    private static function nameToken(string $plain, string $ns = 'g'): string {
        $plain = (string)$plain;
        $key   = (string)Vars::cryptKey();

        // Namespace hilft Kollisionen DB vs Table vs Meta zu vermeiden
        $data  = $ns . '|' . $plain;

        $raw = hash_hmac('sha256', $data, $key, true);
        $b64 = base64_encode($raw);

        // URL-/FS-safe Base64
        $safe = rtrim(strtr($b64, '+/', '-_'), '=');

        // Prefix, damit es nicht "zufällig wie ein normaler Name" aussieht
        return 'gb_' . $safe;
    }

    /**
     * Pfad zur globalen DB-Index-Datei (liegt im GBDB Root).
     * Dateiname ist ebenfalls tokenisiert.
     */
    private static function dbIndexFile(): string {
        return Vars::DB_PATH() . self::nameToken('__db_index__', 'meta') . Vars::data_extension();
    }

    /**
     * Pfad zur Table-Index-Datei innerhalb einer DB (Ordner).
     * Dateiname ist ebenfalls tokenisiert.
     */
    private static function tableIndexFileByDbToken(string $dbToken): string {
        $dir = Vars::DB_PATH() . $dbToken . "/";
        return $dir . self::nameToken('__table_index__', 'meta') . Vars::data_extension();
    }

    /**
     * Liest ein Index-File (als Mapping plain => token).
     * Intern ist es eine GBDB-Tabelle (Header + Zeilen).
     */
    private static function readIndex(string $file): array {
        $rows = self::ini($file);

        if (empty($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return [];
        }

        // Header entfernen
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

    /**
     * Schreibt ein Index-File (Mapping plain => token) als GBDB-Tabelle.
     */
    private static function writeIndex(string $file, array $map): bool {
        // Header
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

    /**
     * Liefert (und optional erstellt) den DB-Token für einen Klartext-DB-Namen.
     */
    private static function getDbToken(string $dbPlain, bool $ensure = false): ?string {
        $dbPlain = Format::cleanString($dbPlain);
        if ($dbPlain === "") return null;

        // Wenn crypt aus ist: "token" ist schlicht der Name
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

        // neuen Token anlegen
        $token = self::nameToken('db:' . $dbPlain, 'db');

        // Collision-Guard (sehr unwahrscheinlich, aber sauber)
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

    /**
     * Liefert (und optional erstellt) den Table-Token für einen Klartext-Tabellennamen.
     */
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

        // Collision-Guard
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

    /**
     * Entfernt eine Tabelle aus dem Table-Index einer DB (wenn crypt aktiv ist).
     */
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

    /**
     * Löscht (wenn möglich) die Table-Index-Datei einer DB.
     * Wird z.B. bei deleteAll verwendet.
     */
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
       CORE IO
       ============================================================ */

    /**
     * Erstellt den Pfad zur Datenbank / Tabelle
     * @internal Used by Framework
     */
    private static function makePath(string $database, string $table, bool $ensure = false): string {
        $table    = Format::cleanString($table);
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $dbToken = self::getDbToken($database, $ensure);
            $tbToken = self::getTableToken($database, $table, $ensure);

            // Wenn nicht vorhanden (und ensure=false), bewusst "ins Leere" zeigen,
            // damit file_exists etc. sauber false ergibt.
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


    /* ============================================================
       PUBLIC API
       ============================================================ */

    /**
     * Erstellt eine GBDB Datenbank
     */
    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);
        if ($name === "") return false;

        $base = Vars::DB_PATH();

        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }

        // crypt: Token stabil über Index (keine random-IV Encode)
        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, true)
            : $name;

        if ($dirName === null) return false;

        $path = $base . $dirName;

        if (!is_dir($path)) {
            return @mkdir($path, 0777);
        }

        // existiert schon
        return false;
    }

    /**
     * Löscht eine GBDB Datenbank (nur wenn diese leer ist)
     */
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

                // Wenn crypt aktiv ist, erlauben wir das Löschen auch,
                // wenn NUR die Table-Index-Datei vorhanden ist.
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

    /**
     * Erstellt eine GBDB Tabelle in einer GBDB Datenbank
     */
    public static function createTable(string $database, string $table, array $cols): bool {
        // ensure=true: wir wollen Namen-Token + Index anlegen, falls noch nicht da
        $file = self::makePath($database, $table, true);

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
            $ok = @unlink($file);

            // Index updaten
            if ($ok) {
                self::dropTableFromIndex($database, $table);
            }

            return $ok;
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
        $d = Vars::DB_PATH();
        if (!is_dir($d)) return [];

        // Wenn crypt aus: normaler scan
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

        // crypt an: aus Index lesen (Klartextliste)
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

    /**
     * Gibt alle Tabellen aus einer Datenbank zurück
     */
    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);
        if ($database === "") return [];

        // crypt aus: normaler dir scan
        if (!Vars::crypt_data()) {
            $databasePath = Vars::DB_PATH() . $database . "/";
            if (!is_dir($databasePath)) return [];

            $tables = [];
            $order  = $descending ? 1 : 0;
            $tmp    = scandir($databasePath, $order);

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") continue;
                if (!str_ends_with($entry, Vars::data_extension())) continue;

                $tables[] = str_replace(Vars::data_extension(), "", $entry);
            }

            return $tables;
        }

        // crypt an: token-dir + table-index lesen
        $dbToken = self::getDbToken($database, false);
        if ($dbToken === null) return [];

        $databasePath = Vars::DB_PATH() . $dbToken . "/";
        if (!is_dir($databasePath)) return [];

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        $map     = self::readIndex($idxFile);

        $tables = [];
        foreach ($map as $plain => $token) {
            $file = $databasePath . $token . Vars::data_extension();
            if (is_file($file)) {
                $tables[] = $plain;
            }
        }

        // Sortierung nach Klartext
        if ($descending) {
            rsort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            sort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $tables;
    }

    /**
     * Prüft ob value1 / value2 in einem Datensatz existieren (AND/OR)
     */
    public static function inDB2(string $database, string $table, mixed $value1, string $operator, mixed $value2): bool {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (!is_array($db) || empty($db)) {
            return false;
        }

        foreach ($db as $r) {
            if (!is_array($r)) continue;

            $foundValue1 = false;
            $foundValue2 = false;

            foreach ($r as $value) {
                if ($value == $value1) $foundValue1 = true;
                if ($value == $value2) $foundValue2 = true;
            }

            if ($operator === 'AND' && $foundValue1 && $foundValue2) return true;
            if ($operator === 'OR' && ($foundValue1 || $foundValue2)) return true;
        }

        return false;
    }

    /**
     * Löscht eine Datenbank inklusive aller Tabellen darin
     */
    public static function deleteAll(string $database): bool {
        $ok     = true;
        $tables = self::listTables($database);

        foreach ($tables as $tbl) {
            if (!self::deleteTable($database, $tbl)) {
                $ok = false;
                break;
            }
        }

        // Table-Index entfernen, sonst ist DB-Ordner "nicht leer"
        self::removeTableIndexIfExists($database);

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

        return array_keys($db[0]);
    }
}
