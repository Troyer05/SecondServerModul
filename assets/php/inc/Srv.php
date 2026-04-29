<?php

class Srv {
    /**
     * Verarbeitet die Funktion enqueue.
     * @param string $service Übergabewert.
     * @param string $action Übergabewert.
     * @param array $payload Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function enqueue(string $service, string $action, array $payload = []) {
        $job = [
            "service" => $service,
            "action" => $action,
            "payload" => json_encode($payload, JSON_UNESCAPED_UNICODE),
            "status" => "pending",
            "created" => date("Y-m-d H:i:s")
        ];

        return GBDB::insertData("main", "srv_jobs", $job);
    }

    /**
     * Verarbeitet die Funktion get jobs.
     * @return mixed Rückgabewert.
     */
    public static function getJobs() {
        return GBDB::getData("main", "srv_jobs");
    }

    /**
     * Verarbeitet die Funktion get job.
     * @param int $id Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function getJob(int $id) {
        $job = GBDB::getData("main", "srv_jobs", true, "id", (string)$id);

        return $job[0] ?? $job;
    }

    /**
     * Führt ein Script aus und gibt das Ergebnis zurück.
     * @param string $path Übergabewert.
     * @param array $params Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $path, array $params = [], array $ctx = []): array {
        $path = ltrim(str_replace(["..", "\\"], ["", "/"], $path), "/");

        if (!is_file($path)) {
            return [
                "ok" => false,
                "msg" => "Script not found on backend: " . $path
            ];
        }

        if (!is_readable($path)) {
            return [
                "ok" => false,
                "msg" => "Script not readable on backend: " . $path
            ];
        }

        $script = file_get_contents($path);

        if ($script === false) {
            return [
                "ok" => false,
                "msg" => "Script could not be read on backend: " . $path
            ];
        }

        return [
            "ok" => true,
            "result" => DB_QUERY($script, $ctx, $params)
        ];
    }

    /**
     * Verarbeitet Auth-Aktionen.
     * @param string $action Übergabewert.
     * @param array $body Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth(string $action, array $body): array {
        try {
            if ($action == "init") {
                return Auth::initRemote();
            }

            if ($action == "login") {
                return Auth::loginRemote($body["username_or_email"] ?? "", $body["plain_text_password"] ?? "");
            }

            if ($action == "token") {
                return Auth::authByToken($body["jwt"] ?? "");
            }

            if ($action == "get") {
                $table = $body["table"] ?? "";

                if ($table == "") {
                    return ["ok" => false, "msg" => "Table not provided"];
                }

                if (($body["where"] ?? "") != "") {
                    return [
                        "ok" => true,
                        "data" => Auth::get($table, $body["where"], $body["is"] ?? "")
                    ];
                }

                return [
                    "ok" => true,
                    "data" => Auth::get($table)
                ];
            }

            if ($action == "user") {
                return [
                    "ok" => true,
                    "user" => Auth::user($body["uid"] ?? "")
                ];
            }

            if ($action == "new_user") {
                $err = Auth::newUser(
                    is_array($body["user_data"] ?? null) ? $body["user_data"] : [],
                    is_array($body["user_meta"] ?? null) ? $body["user_meta"] : [],
                    (bool)($body["is_this_register"] ?? false)
                );

                return [
                    "ok" => $err == "",
                    "msg" => $err
                ];
            }

            if ($action == "edit_user") {
                $err = Auth::editUser(
                    $body["uid"] ?? "",
                    is_array($body["user_data"] ?? null) ? $body["user_data"] : [],
                    is_array($body["user_meta"] ?? null) ? $body["user_meta"] : []
                );

                return [
                    "ok" => $err == "",
                    "msg" => $err
                ];
            }

            if ($action == "delete") {
                Auth::delete($body["table"] ?? "", $body["where"] ?? "", $body["is"] ?? "");

                return [
                    "ok" => true,
                    "msg" => "Deleted"
                ];
            }

            if ($action == "verify_email") {
                return [
                    "ok" => Auth::verifyEmail($body["token"] ?? "")
                ];
            }

            if ($action == "verify_2fa") {
                return [
                    "ok" => Auth::verify2FaCode($body["code"] ?? "")
                ];
            }

            return [
                "ok" => false,
                "msg" => "Unknown auth action: " . $action
            ];
        } catch (Throwable $e) {
            return [
                "ok" => false,
                "msg" => $e->getMessage()
            ];
        }
    }

    /**
     * Verarbeitet die Funktion run one.
     * @param int $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runOne(int $id): array {
        $job = self::getJob($id);

        if (!$job || !isset($job["service"])) {
            return ["error" => "Job not found"];
        }

        $service = $job["service"];
        $action = $job["action"];
        $payload = json_decode($job["payload"] ?? "[]", true) ?: [];

        self::log($id, "info", "Run job #$id: service=$service action=$action");

        $module = self::loadModule($service);

        if (!$module) {
            self::log($id, "error", "Module '$service' konnte nicht geladen werden");
            return ["error" => "Module '$service' not found"];
        }

        if (!method_exists($module, $action)) {
            self::log($id, "error", "Action '$action' in Modul '" . get_class($module) . "' nicht gefunden");
            return ["error" => "Action '$action' not found in module"];
        }

        try {
            self::log($id, "debug", "Starte Action '$action'", $payload);

            $result = $module->$action($payload, $job);

            self::log($id, "success", "Job #$id erfolgreich beendet", $result);

            GBDB::editData("main", "srv_jobs", "id", (string)$id, [
                "status" => "done",
                "finished_at" => date("Y-m-d H:i:s")
            ]);

            return ["ok" => true, "result" => $result];
        } catch (Throwable $e) {
            self::log($id, "error", "Exception: " . $e->getMessage(), $e->getTraceAsString());

            GBDB::editData("main", "srv_jobs", "id", (string)$id, [
                "status" => "failed",
                "error_msg" => $e->getMessage(),
                "finished_at" => date("Y-m-d H:i:s")
            ]);

            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Verarbeitet die Funktion load module.
     * @param string $service Übergabewert.
     * @return mixed Rückgabewert.
     */
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

    /**
     * Verarbeitet die Funktion log.
     * @param int $jobId Übergabewert.
     * @param string $level Übergabewert.
     * @param string $message Übergabewert.
     * @param mixed $extra Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function log(int $jobId, string $level, string $message, $extra = null) {
        $dir = __DIR__ . "/../srv_logs/";

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . $jobId . ".log";

        $entry = [
            "time" => date("Y-m-d H:i:s"),
            "level" => $level,
            "msg" => $message
        ];

        if ($extra !== null) {
            $entry["extra"] = $extra;
        }

        file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    /**
     * Verarbeitet die Funktion logs.
     * @param int $jobId Übergabewert.
     * @return array Rückgabewert.
     */
    public static function logs(int $jobId): array {
        $file = __DIR__ . "/../srv_logs/" . $jobId . ".log";

        if (!is_file($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $entries = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Verarbeitet die Funktion module log.
     * @param int $jobId Übergabewert.
     * @param string $level Übergabewert.
     * @param string $message Übergabewert.
     * @param mixed $extra Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function moduleLog(int $jobId, string $level, string $message, $extra = null) {
        self::log($jobId, $level, $message, $extra);
    }
}
