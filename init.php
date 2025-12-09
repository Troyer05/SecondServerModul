<?php

class Init {

    /**
     * Erstellt die komplette Framework-Ordnerstruktur
     */
    public static function framework() {
        $dirs = [
            "assets",
            "assets/css",
            "assets/DB",
            "assets/DB/framework_temp",
            "assets/DB/GBDB",
            "assets/DB/GBDB/main",
            "assets/img",
            "assets/img/greenql_ui",
            "assets/js",
            "assets/php",
            "assets/php/inc",
            "assets/php/inc/.config",
            "assets/php/inc/gbdb_framework",
            "assets/php/inc/gbdb_framework/core",
            "assets/php/inc/gbdb_framework/plugins",
            "assets/php/inc/gbdb_framework/ui",
            "assets/php/srv_logs",
            "assets/php/srv_modules",
            "assets/tool_apis",
            "assets/wasm"
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }

    /**
     * Erstellt die Standard-Datenbank
     */
    public static function GBDB() {
        GBDB::createDatabase("main");

        GBDB::createTable("main", "srvjobs", ["service", "action", "payload", "status", "created"]);
        GBDB::createTable("main", "t", ["token"]);
    }


    /**
     * Liest das große JSON ein und generiert alle Dateien neu
     *
     * @param string $jsonPath Pfad zur Datei (z. B. "framework_files.json")
     */
    public static function fromJson(string $jsonPath) {

        if (!file_exists($jsonPath)) {
            throw new Exception("JSON file not found: $jsonPath");
        }

        $json = json_decode(file_get_contents($jsonPath), true);

        if (!is_array($json)) {
            throw new Exception("JSON invalid or empty.");
        }

        if (!isset($json["files"])) {
            throw new Exception("JSON missing 'files' field.");
        }

        foreach ($json["files"] as $path => $b64content) {

            // Normalize path: replace backslashes with forward slashes
            $norm = str_replace("\\", "/", $path);

            // Create folder if needed
            $dir = dirname($norm);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            // Decode Base64
            $content = base64_decode($b64content);

            if ($content === false) {
                throw new Exception("Failed to decode Base64 for file: $norm");
            }

            // Write file
            file_put_contents($norm, $content);
        }

        return true;
    }


    /**
     * Führt komplette Installation aus:
     * - Ordnerstruktur
     * - Dateien aus JSON
     * - DB & Tabellen
     */
    public static function install(string $jsonFile) {
        self::framework();

        sleep(5);

        self::fromJson($jsonFile);

        sleep(5);
        
        self::GBDB();
    }
}

Init::install("framework_files.json");

unlink("framework_files.json");
unlink("init.php");
