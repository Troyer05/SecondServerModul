<?php

trait GBDB_SchemaTrait {
    
    /**
     * Verarbeitet die Funktion root path.
     * @return string Rückgabewert.
     */
    private static function rootPath(): string {
        $dir = __DIR__;

        while ($dir !== dirname($dir)) {
            if (is_dir($dir . "/assets/php/inc/gbdb_framework")) {
                return $dir;
            }

            $dir = dirname($dir);
        }

        return dirname(__DIR__, 7);
    }


    /**
     * Verarbeitet die Funktion schema path.
     * @return string Rückgabewert.
     */
    private static function schemaPath(): string {
        return self::rootPath() . "/" . self::SCHEMA_FILE;
    }


    /**
     * Verarbeitet die Funktion read schema.
     * @return array Rückgabewert.
     */
    private static function readSchema(): array {
        $file = self::schemaPath();

        if (!is_file($file)) return [];

        $json = json_decode((string)@file_get_contents($file), true);
        return is_array($json) ? $json : [];
    }


    /**
     * Verarbeitet die Funktion write schema.
     * @param array $schema Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeSchema(array $schema): bool {
        $file = self::schemaPath();
        $dir = dirname($file);

        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        ksort($schema, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($schema as $db => $tables) {
            if (!is_array($tables)) {
                unset($schema[$db]);
                continue;
            }

            ksort($tables, SORT_NATURAL | SORT_FLAG_CASE);
            $schema[$db] = $tables;
        }

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) return false;

        return GBDBStorage::atomicWrite($file, $json . "\n");
    }


    /**
     * Verarbeitet die Funktion set schema table.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param array $cols Übergabewert.
     * @return void Rückgabewert.
     */
    private static function setSchemaTable(string $database, string $table, array $cols): void {
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if ($database === "" || $table === "") return;

        $schema = self::readSchema();

        if (!isset($schema[$database]) || !is_array($schema[$database])) $schema[$database] = [];
        if (!isset($schema[$database][$table]) || !is_array($schema[$database][$table])) $schema[$database][$table] = [];

        foreach ($cols as $col => $default) {
            if (is_int($col)) {
                $col = (string)$default;
                $default = "";
            }

            $col = trim((string)$col);

            if ($col === "" || $col === "id") continue;

            if (!array_key_exists($col, $schema[$database][$table])) {
                $schema[$database][$table][$col] = $default;
            }
        }

        self::writeSchema($schema);
    }


    /**
     * Verarbeitet die Funktion drop schema table.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaTable(string $database, string $table): void {
        $database = Format::cleanString($database);
        $table = Format::cleanString($table);

        if ($database === "" || $table === "") return;

        $schema = self::readSchema();

        if (isset($schema[$database][$table])) unset($schema[$database][$table]);
        if (isset($schema[$database]) && empty($schema[$database])) unset($schema[$database]);

        self::writeSchema($schema);
    }


    /**
     * Verarbeitet die Funktion drop schema database.
     * @param string $database Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropSchemaDatabase(string $database): void {
        $database = Format::cleanString($database);

        if ($database === "") return;

        $schema = self::readSchema();

        if (isset($schema[$database])) {
            unset($schema[$database]);
            self::writeSchema($schema);
        }
    }


    /**
     * Verarbeitet die Funktion auto compact.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return void Rückgabewert.
     */
    private static function autoCompact(string $database, string $table): void {
        $appendFile = self::appendFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $meta = self::readMeta($metaFile);

        if (GBDBStorage::shouldCompact($meta, $appendFile)) {
            self::compactTable($database, $table);
        }
    }
}
