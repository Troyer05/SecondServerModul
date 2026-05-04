<?php

trait GBDBv2_InstanceSchemaTrait {

    /**
     * Gibt den Projekt-Root zurück.
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
     * Führt einen Callback in einer temporären Instanz aus und setzt danach zurück.
     * @param string $instance Instanzname.
     * @param callable $callback Callback.
     * @return mixed Rückgabewert des Callbacks.
     */
    public static function withInstance(string $instance, callable $callback): mixed {
        $old = self::getInstance();
        self::setInstance($instance);

        try {
            return $callback();
        } finally {
            self::setInstance($old);
        }
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

        return GBDBStorage::atomicWrite($file, $json . "\n");
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
        $appendFile = self::appendFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $meta = self::readMeta($metaFile);

        if (GBDBStorage::shouldCompact($meta, $appendFile)) {
            self::compactTable($database, $table);
        }
    }
}
