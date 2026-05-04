<?php

class DatabaseBridge {
    private static string $driver = "";
    private static string $instance = "";

    /**
     * Setzt den aktiven Datenbank-Treiber manuell.
     * @param string $driver Treiber: GBDB, GBDBv2 oder SQL.
     * @return void
     */
    public static function setDriver(string $driver): void {
        $driver = strtoupper(trim($driver));
        self::$driver = in_array($driver, ["GBDB", "GBDBV2", "SQL"], true) ? $driver : "";
    }

    /**
     * Setzt die aktive GBDBv2 Instanz.
     * @param string $instance Instanzname.
     * @return void
     */
    public static function setInstance(string $instance): void {
        self::$instance = trim($instance);
        if (self::$instance !== "" && class_exists("GBDBv2")) {
            self::$driver = "GBDBV2";
            GBDBv2::setInstance(self::$instance);
        }
    }

    /**
     * Gibt den aktiven Treiber zurück.
     * @return string Rückgabewert.
     */
    private static function driver(): string {
        if (self::$driver !== "") {
            return self::$driver;
        }

        if (defined("DB_ARCH") && strtoupper((string)DB_ARCH) === "SQL") {
            return "SQL";
        }

        return self::$instance !== "" ? "GBDBV2" : "GBDB";
    }

    /**
     * Prüft, ob SQL genutzt wird.
     * @return bool Rückgabewert.
     */
    private static function isSQL(): bool {
        return self::driver() === "SQL";
    }

    /**
     * Prüft, ob GBDBv2 genutzt wird.
     * @return bool Rückgabewert.
     */
    private static function isGBDBv2(): bool {
        return self::driver() === "GBDBV2";
    }

    /**
     * Stellt sicher, dass SQL verbunden ist.
     * @return void
     */
    private static function ensureSQL(): void {
        if (self::isSQL()) {
            try {
                SQL::connect();
            } catch (Throwable $e) {
                error_log("[DatabaseBridge] SQL connection failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Synchronisiert GBDBv2 mit der aktiven Instanz.
     * @return void
     */
    private static function ensureGBDBv2(): void {
        if (self::isGBDBv2() && self::$instance !== "" && class_exists("GBDBv2")) {
            GBDBv2::setInstance(self::$instance);
        }
    }

    /**
     * Gibt Daten zurück.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param bool $filter Filter aktiv.
     * @param string $where Spalte.
     * @param mixed $is Vergleichswert.
     * @return mixed Rückgabewert.
     */
    public static function get(string $db, string $table, bool $filter = false, string $where = "", mixed $is = ""): mixed {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::getData($db, $table, $filter, $where, $is);
        }

        if (!self::isSQL()) {
            return GBDB::getData($db, $table, $filter, $where, $is);
        }

        self::ensureSQL();

        try {
            if (!$filter) {
                return SQL::select($table);
            }

            $isValue = is_string($is) ? "'" . addslashes($is) . "'" : $is;
            return SQL::select($table, "*", $where, $isValue);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL SELECT failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fügt neue Daten ein.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param array $data Daten.
     * @return mixed Rückgabewert.
     */
    public static function insert(string $db, string $table, array $data): mixed {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::insertData($db, $table, $data);
        }

        if (!self::isSQL()) {
            return GBDB::insertData($db, $table, $data);
        }

        self::ensureSQL();

        try {
            return SQL::insert($table, $data);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL INSERT failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Löscht Daten.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param string $where Spalte.
     * @param mixed $is Wert.
     * @return mixed Rückgabewert.
     */
    public static function delete(string $db, string $table, string $where, mixed $is): mixed {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::deleteData($db, $table, $where, $is);
        }

        if (!self::isSQL()) {
            return GBDB::deleteData($db, $table, $where, $is);
        }

        self::ensureSQL();

        try {
            return SQL::delete($table, $where, $is);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL DELETE failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aktualisiert Daten.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param string $where Spalte.
     * @param mixed $is Wert.
     * @param array $data Daten.
     * @return mixed Rückgabewert.
     */
    public static function update(string $db, string $table, string $where, mixed $is, array $data): mixed {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::editData($db, $table, $where, $is, $data);
        }

        if (!self::isSQL()) {
            return GBDB::editData($db, $table, $where, $is, $data);
        }

        self::ensureSQL();

        try {
            return SQL::update($table, $data, $where, $is);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL UPDATE failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Erstellt eine Base.
     * @param string $name Name.
     * @return bool Rückgabewert.
     */
    public static function createDatabase(string $name): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::createDatabase($name);
        }

        if (!self::isSQL()) {
            return GBDB::createDatabase($name);
        }

        return true;
    }

    /**
     * Löscht eine Base.
     * @param string $name Name.
     * @return bool Rückgabewert.
     */
    public static function deleteDatabase(string $name): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::deleteDatabase($name);
        }

        if (!self::isSQL()) {
            return GBDB::deleteDatabase($name);
        }

        return false;
    }

    /**
     * Erstellt eine Tabelle.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param array $columns Spalten.
     * @return bool Rückgabewert.
     */
    public static function createTable(string $db, string $table, array $columns): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::createTable($db, $table, $columns);
        }

        if (!self::isSQL()) {
            return GBDB::createTable($db, $table, $columns);
        }

        return false;
    }

    /**
     * Löscht eine Tabelle.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @return bool Rückgabewert.
     */
    public static function deleteTable(string $db, string $table): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::deleteTable($db, $table);
        }

        if (!self::isSQL()) {
            return GBDB::deleteTable($db, $table);
        }

        return false;
    }

    /**
     * Fügt eine Spalte hinzu.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param mixed $default Standardwert.
     * @return bool Rückgabewert.
     */
    public static function addColumn(string $db, string $table, string $column, mixed $default = ""): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::addColumn($db, $table, $column, $default);
        }

        if (!self::isSQL()) {
            return GBDB::addColumn($db, $table, $column, $default);
        }

        return false;
    }

    /**
     * Erstellt einen Index.
     * @param string $db Base.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @return bool Rückgabewert.
     */
    public static function createIndex(string $db, string $table, string $column): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::createIndex($db, $table, $column);
        }

        return !self::isSQL() && GBDB::createIndex($db, $table, $column);
    }

    /**
     * Startet eine Transaktion.
     * @return bool Rückgabewert.
     */
    public static function begin(): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::begin();
        }

        return !self::isSQL() && GBDB::begin();
    }

    /**
     * Speichert eine Transaktion.
     * @return bool Rückgabewert.
     */
    public static function commit(): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::commit();
        }

        return !self::isSQL() && GBDB::commit();
    }

    /**
     * Verwirft eine Transaktion.
     * @return bool Rückgabewert.
     */
    public static function rollback(): bool {
        if (self::isGBDBv2()) {
            self::ensureGBDBv2();
            return GBDBv2::rollback();
        }

        return !self::isSQL() && GBDB::rollback();
    }
}
