<?php

class GBDB {
    /**
     * Erstellt den Path zu der Datenabnk / Tabelle
     * @internal Used by Framework
     */
    private static function makePath(string $database, string $table): string {
        $table = Format::cleanString($table);
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $table = Crypt::encode($table);
            $database = Crypt::encode($database);
        }

        $table .= Vars::data_extension();
        $database = Vars::DB_PATH() . $database . "/";
        
        return $database . $table;
    }

    /**
     * Generiert die ID für einen nächsten Eintrag
     * @internal used by Framework
     */
    private static function genID(string $file): int {
        $database = self::ini($file);

        $id = 0;

        foreach ($database as $i => $r) {
            $id = $r["id"] + 1;
        }

        return $id;
    }

    /**
     * Stellt den Inhalt einer Tabelle für PHP zur Verfügung
     * @internal used by Framework
     */
    private static function ini(string $file): mixed {
        $db = [];
        $tmp = file_get_contents($file, true);
        $db = json_decode($tmp, true);

        if (Vars::crypt_data()) {
            $db = json_decode(Crypt::decode($tmp), true);
        }

        return $db;
    }

    /**
     * Erstellt eine GBDB Datenbank
     * @param string $name Name der Datenank (Alles was kein Buchstabe und keine Zahl ist, wird ignoriert)
     * @return bool true wenn es keine Probleme gab
     */
    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if (Vars::crypt_data()) {
            $name = Crypt::encode($name);
        }

        if (!is_dir(Vars::DB_PATH())) {
            mkdir(Vars::DB_PATH(), 0777);
        }

        if (!is_dir(Vars::DB_PATH() . $name)) {
            mkdir(Vars::DB_PATH() . $name, 0777);
            return true;
        }

        return false;
    }

    /**
     * Löscht eine GBDB Datenbank (nur wenn diese leer ist)
     * @param string $name name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if (Vars::crypt_data()) {
            $name = Crypt::encode($name);
        }

        if (is_dir(Vars::DB_PATH() . $name)) {
            rmdir(Vars::DB_PATH() . $name);
            return true;
        }

        return false;
    }

    /**
     * Erstelt eine DBDB Tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table Name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param array $cols Namen der Spalten (@example ["name", "notiz"])
     * @return bool true wenn es keine Probleme gab
     */
    public static function createTable(string $database, string $table, array $cols): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            $columns = '[{"id": -1, ';
            $n = count($cols);
            $i = 0;

            foreach ($cols as $col) {
                $columns .= '"' . $col . '": "-header-", ';
            }

            $columns = rtrim($columns, ', ');
            $columns .= '}]';

            if (Vars::crypt_data()) {
                $columns = Crypt::encode($columns);
            } else {
                $columns = json_encode(json_decode($columns), Vars::jpretty());
            }

            file_put_contents($file, $columns);

            return true;
        }

        return false;
    }
    
    /**
     * Löscht eine GBDB tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);

        if (file_exists($file)) {
            unlink($file);
            return true;
        }

        return false;
    }

    /**
     * Fügt Daten in eine GBDB tabelle in einer GBDB Datenbank ein
     * @param string $database name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $data Daten die eingefügt werden sollen (@example ["name" => "Max Mustermann", "notiz" => "Testeintrag"])
     * @return bool true wenn es keine Probleme gab
     */
    public static function insertData(string $database, string $table, mixed $data): int {
        $file = self::makePath($database, $table);
    
        if (file_exists($file)) {
            $table_data = json_decode(file_get_contents($file), true);
    
            if (Vars::crypt_data()) {
                $table_data = json_decode(Crypt::decode(file_get_contents($file)), true);
            }
    
            if (empty($table_data)) {
                foreach ($data as $key => $value) {
                    $table_data[0][$key] = null;
                }
            }
    
            if (!isset($data['id'])) {
                $_id = self::genID($file);
                $data['id'] = $_id;
            }
    
            if (count($data) !== count($table_data[0])) {
                return -1;
            }
    
            $new_row = [];

            foreach ($table_data[0] as $col => $value) {
                $new_row[$col] = isset($data[$col]) ? $data[$col] : $value;
            }
    
            $table_data[] = $new_row;

            if (Vars::crypt_data()) {
                $new_data_json = Crypt::encode(json_encode($table_data));
            } else {
                $new_data_json = json_encode($table_data, Vars::jpretty());
            }

            file_put_contents($file, $new_data_json);
    
            return $_id;
        }
    
        return -1;
    }    

    /**
     * Entfernt Daten aus einer GBDB Tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteData(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            // $where = Crypt::encode($where);
            // $is = Crypt::encode($is);
        }

        $return = false; 

        foreach ($db as $i => $r) {
            if ($r[$where] == $is) {
                unset($db[$i]);
                $return = true;
            }
        }

        $db = array_values($db);

        if ($return) {
            if (Vars::crypt_data()) {
                file_put_contents($file, Crypt::encode(json_encode($db, Vars::jpretty())));
            } else {
                file_put_contents($file, json_encode($db, Vars::jpretty()));
            }
        }

        return $return;
    }

    /**
     * Bearbeitet Daten aus einer GBDB Tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @param mixed $newData Neue Daten (@example ["Henry Henryson"])
     * @return bool true wenn es keine Probleme gab
     */
    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData): bool {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            // $where = Crypt::encode($where);
            // $is = Crypt::encode($is);
        }

        $return = false;

        foreach ($db as $i => $r) {
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
            if (Vars::crypt_data()) {
                file_put_contents($file, Crypt::encode(json_encode($db, Vars::jpretty())));
            } else {
                file_put_contents($file, json_encode($db, Vars::jpretty()));
            }
        }

        return $return;
    }

    /**
     * Stellt alle Daten aus einer GBDB tabelle bereit
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param bool $filter (Optional, Standard: false) Soll gefiltert werden?
     * @param mixed $where (Optional) In welcher Spalte.... 
     * @param mixed $is (Optional) .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @return mixed Daten aus der Tabelle
     */
    public static function getData(string $database, string $table, bool $filter = false, mixed $where = "", mixed $is = ""): mixed {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            if ($filter) {
                // $where = Crypt::encode($where);
                // $is = Crypt::encode($is);
            }
        }

        if ($filter) {
            foreach ($db as $i => $r) {
                if ($r[$where] == $is) {
                    return $db[$i];
                }
            }

            return [];
        } else {
            unset($db[0]);
            $db = array_values($db);
        }

        return $db;
    }

    /**
     * Überprüft, ob ein Element in einer GBDB Tabelle vorhanden ist
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @return bool true, wenn das Element vorhanden ist
     */
    public static function elementExists(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            // $where = Crypt::encode($where);
            // $is = Crypt::encode($is);
        }

        foreach ($db as $i => $r) {
            if ($r[$where] == $is) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gibt alle Datenbanken zurück die existieren
     * @return array Datenbanken, String Array
     */
    public static function listDBs(): array {
        $d = Vars::DB_PATH();
        $dirs = [];

        $tmp = array_filter(scandir($d), function ($f) use($d) {
            return is_dir($d . $f);
        });

        for ($i = 0; $i < count($tmp); $i++) {
            if ($tmp[$i] != "." && $tmp[$i] != "..") {
                $db_name = $tmp[$i];

                if (Vars::crypt_data()) {
                    $db_name = Crypt::decode($db_name);
                }

                array_push($dirs, $db_name);
            }
        }

        return $dirs;
    }

    /**
     * Gibt alle Tabellen aus einer Datenbank zurück, die existieren
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param bool $descending (Optional, Standart: false) Soll DESCENDING Sortierung verwendet werden?
     * @return array Tabellen, String Array
     */
    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $database = Crypt::encode($database);
        }

        $database = Vars::DB_PATH() . $database . "/";
        $tables = [];
        $desc = 0;

        if ($descending) {
            $desc = 1;
        }

        $tmp = scandir($database, $desc);
        
        for ($i = 0; $i < count($tmp); $i++) {
            if ($tmp[$i] != "." && $tmp[$i] != "..") {
                $table_name = str_replace(Vars::data_extension(), "", $tmp[$i]);

                if (Vars::crypt_data()) {
                    $table_name = Crypt::decode($table_name);
                }

                array_push($tables, $table_name);
            }
        }

        return $tables;
    }

    /**
     * Überprüft ob ein- oder zwei Values jewailig des Operatores zueinandner in einem Datensatz vorhanden sind
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @todo Dokumentation vervollständigen
     * 
    */
    public static function inDB2(string $database, string $table, mixed $value1, string $operator, mixed $value2): bool {
        $file = self::makePath($database, $table);
        $jsonContent = file_get_contents($file);
        $db = json_decode($jsonContent, true);

        if (Vars::crypt_data()) {
            $db = json_decode(Crypt::decode($jsonContent), true);
            // $value1 = Crypt::encode($value1);
            // $value2 = Crypt::encode($value2);
        }

        foreach ($db as $r) {
            $foundValue1 = false;
            $foundValue2 = false;

            foreach ($r as $key => $value) {
                if ($value == $value1) {
                    $foundValue1 = true;
                }

                if ($value == $value2) {
                    $foundValue2 = true;
                }
            }

            if ($operator == 'AND') {
                if ($foundValue1 && $foundValue2) {
                    return true;
                }
            } else if ($operator == 'OR') {
                if ($foundValue1 || $foundValue2) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Löscht eine Datenbank inklusive aller Tabellen darin
     * @param string $database Name der Datenbank
     * @return true wenn es keine Probleme gab
    */
    public static function deleteAll(string $database): bool {
        $ok = true;
        $tables = self::listTables($database);

        for ($i = 0; $i < count($tables); $i++) {
            if (!self::deleteTable($database, $tables[$i])) {
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
     * Gibt die ID, welche für den nächsten Datensatz vorgehesehn ist
     * @param string $database Datenbank
     * @param string $table Tabelle
     * @return int Die Vorgesehene ID
     */
    public static function nextID(string $database, string $table): int {
        $file = self::makePath($database, $table);
    
        if (file_exists($file)) {
            return self::genID($file);
        }
    
        return 0;
    }
}
