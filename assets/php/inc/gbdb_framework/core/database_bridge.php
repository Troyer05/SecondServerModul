<?php

class DatabaseBridge {
    /**
     * Prüft ob SQL-Architektur aktiv ist
     */
    private static function isSQL(): bool {
        return (defined("DB_ARCH") && DB_ARCH === "SQL");
    }

    /**
     * Prüft ob GBDB (JSON) genutzt wird
     */
    private static function isGBDB(): bool {
        return !self::isSQL();
    }

    /**
     * Stellt sicher, dass SQL verbunden ist
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

    // ---------------------------------------------------------
    // SELECT
    // ---------------------------------------------------------

    public static function get(
        string $db,
        string $table,
        bool $filter = false,
        string $where = "",
        string|int|float $is = ""
    ) {
        if (self::isGBDB()) {
            return GBDB::getData($db, $table, $filter, $where, $is);
        }

        // SQL Mode
        self::ensureSQL();

        // Falls kein Filter → alles holen
        if (!$filter) {
            try {
                return SQL::select($table);
            } catch (Throwable $e) {
                error_log("[DatabaseBridge] SQL select failed: " . $e->getMessage());
                return [];
            }
        }

        // Filter-Mode
        try {
            // Sicheres Escaping (rudimentär, da SQL-Klasse nicht bekannt)
            $isValue = is_string($is) ? "'" . addslashes($is) . "'" : $is;
            return SQL::select($table, "*", $where, $isValue);
        } catch (Throwable $e) {
            error_log("[DatabaseBridge] SQL SELECT filter failed: " . $e->getMessage());
            return [];
        }
    }

    // ---------------------------------------------------------
    // INSERT
    // ---------------------------------------------------------

    public static function insert(string $db, string $table, array $data) {
        if (self::isGBDB()) {
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

    // ---------------------------------------------------------
    // DELETE
    // ---------------------------------------------------------

    public static function delete(string $db, string $table, string $where, string|int|float $is) {
        if (self::isGBDB()) {
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

    // ---------------------------------------------------------
    // UPDATE
    // ---------------------------------------------------------

    public static function update(
        string $db,
        string $table,
        string $where,
        string|int|float $is,
        array $data
    ) {
        if (self::isGBDB()) {
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
}
