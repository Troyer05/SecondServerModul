<?php

class Srv {
    public static function enqueue(string $service, string $action, array $payload = []) {
        $job = [
            "service" => $service,
            "action"  => $action,
            "payload" => json_encode($payload),
            "status"  => "pending",
            "created" => date("Y-m-d H:i:s")
        ];

        return GBDB::insertData("main", "srv_jobs", $job);
    }

    public static function getJobs() {
        return GBDB::getData("main", "srv_jobs");
    }

    public static function getJob(int $id) {
        return GBDB::getData("main", "srv_jobs", true, "id", $id);
    }

    public static function runOne(int $id): array {
        $job = GBDB::getData("main", "srv_jobs", true, "id", $id);

        if (!$job || !isset($job["service"])) {
            return ["error" => "Job not found"];
        }

        $service = $job["service"];
        $action  = $job["action"];
        $payload = json_decode($job["payload"] ?? "[]", true) ?: [];

        self::log($id, "info", "Run job #$id: service=$service action=$action");

        $module = self::loadModule($service, $id);

        if (!$module) {
            self::log($id, "error", "Module '$service' konnte nicht geladen werden");
            return ["error" => "Module '$service' not found"];
        }

        if (!method_exists($module, $action)) {
            self::log($id, "error", "Action '$action' in Modul '".get_class($module)."' nicht gefunden");
            return ["error" => "Action '$action' not found in module"];
        }

        try {
            self::log($id, "debug", "Starte Action '$action'", $payload);

            $result = $module->$action($payload, $job);

            self::log($id, "success", "Job #$id erfolgreich beendet", $result);

            GBDB::editData("main", "srv_jobs", "id", $id, [
                "status"      => "done",
                "finished_at" => date("Y-m-d H:i:s")
            ]);

            return ["ok" => true, "result" => $result];

        } catch (Throwable $e) {

            self::log($id, "error", "Exception: ".$e->getMessage(), $e->getTraceAsString());

            GBDB::editData("main", "srv_jobs", "id", $id, [
                "status"      => "failed",
                "error_msg"   => $e->getMessage(),
                "finished_at" => date("Y-m-d H:i:s")
            ]);

            return ["error" => $e->getMessage()];
        }
    }


    public static function loadModule(string $service) {
        $file = __DIR__ . "/../srv_modules/" . ucfirst($service) . ".php";
        $class = "Srv_" . ucfirst($service);

        if (!file_exists($file)) {
            return null;
        }

        require_once $file;

        if (!class_exists($class)) {
            return null;
        }

        return new $class();
    }

    public static function log(int $jobId, string $level, string $message, $extra = null) {
        $dir = __DIR__ . '/../srv_logs/';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . $jobId . '.log';

        $entry = [
            "time"  => date("Y-m-d H:i:s"),
            "level" => $level,
            "msg"   => $message
        ];

        if ($extra !== null) {
            $entry["extra"] = $extra;
        }

        // JSON als einzelne Zeile schreiben
        file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    public static function moduleLog(int $jobId, string $level, string $message, $extra = null) {
        self::log($jobId, $level, $message, $extra);
    }
}
