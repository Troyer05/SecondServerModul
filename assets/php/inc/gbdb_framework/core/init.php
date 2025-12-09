<?php

class Init {

    private static function createDir(string $path): bool {
        if (!is_dir($path)) {
            return mkdir($path, 0777, true);
        }
        return true;
    }

    private static function createFile(string $path, string $content = ""): bool {
        if (!file_exists($path)) {
            return file_put_contents($path, $content) !== false;
        }
        return true;
    }

    public static function framework(): array {
        $dirs = [
            "assets",
            "assets/css",
            "assets/DB",
            "assets/DB/framework_temp",
            "assets/DB/GBDB",
            "assets/img",
            "assets/img/greenql_ui",
            "assets/js",
            "assets/php",
            "assets/php/inc",
            "assets/php/inc/.config",
            "assets/php/inc/gbdb_framework",
            "assets/php/inc/gbdb_framework/core",
            "assets/php/inc/gbdb_framework/plugins",
            "assets/php/srv_logs",
            "assets/php/srv_modules",
            "assets/tool_apis",
            "assets/wasm",
        ];

        $result = [];

        foreach ($dirs as $dir) {
            $ok = self::createDir($dir);
            $result[$dir] = $ok ? "created or exists" : "failed";
        }

        return $result;
    }

    public static function GBDB(): array {
        $result = [];

        // DB erstellen
        try {
            GBDB::createDatabase("main");
            $result["database"] = "created or exists";
        } catch (Throwable $e) {
            $result["database"] = "error: " . $e->getMessage();
        }

        // Tabellen
        try {
            GBDB::createTable("main", "srvjobs", ["service", "action", "payload", "status", "created"]);
            $result["srvjobs"] = "created or exists";
        } catch (Throwable $e) {
            $result["srvjobs"] = "error: " . $e->getMessage();
        }

        try {
            GBDB::createTable("main", "t", ["token"]);
            $result["t"] = "created or exists";
        } catch (Throwable $e) {
            $result["t"] = "error: " . $e->getMessage();
        }

        return $result;
    }
}
