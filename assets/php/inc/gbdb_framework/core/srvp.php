<?php

class SrvP {
    /**
     * Ermittelt den API-Endpunkt.
     * @return string Rückgabewert.
     */
    private static function endpoint(): string {
        return (Vars::srvp_ssl() ? "https://" : "http://") . Vars::srvp_ip() . "/backend.php";
    }

    /**
     * Sendet eine Anfrage und verarbeitet die Antwort.
     * @param array $payload Übergabewert.
     * @return array Rückgabewert.
     */
    private static function request(array $payload): array {
        $resp = Http::post(
            self::endpoint(),
            $payload,
            ["Content-Type: application/json"]
        );

        $decoded = json_decode($resp, true);

        if (!is_array($decoded)) {
            throw new Exception("Invalid JSON response: " . $resp);
        }

        return $decoded;
    }

    /**
     * Verarbeitet die Funktion get token.
     * @return string Rückgabewert.
     */
    private static function getToken(): string {
        $resp = self::request([
            "sauth" => hash("sha256", Vars::srvp_static_key()),
            "do" => "gtoken"
        ]);

        if (!isset($resp["data"]) || $resp["data"] == "") {
            throw new Exception("Token not returned by backend");
        }

        return $resp["data"];
    }

    /**
     * Verarbeitet die Funktion payload with token.
     * @param array $body Übergabewert.
     * @return array Rückgabewert.
     */
    private static function payloadWithToken(array $body): array {
        $body["sauth"] = hash("sha256", Vars::srvp_static_key());
        $body["token"] = self::getToken();

        return $body;
    }

    /**
     * Verarbeitet die Funktion get data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param bool $filter Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getData(string $db, string $table, bool $filter = false, string $where = "", string $is = ""): array {
        $body = [
            "do" => "get",
            "db" => $db,
            "table" => $table
        ];

        if ($filter) {
            $body["where"] = $where;
            $body["is"] = $is;
        }

        return self::request(self::payloadWithToken($body));
    }

    /**
     * Verarbeitet die Funktion add data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param array $data Übergabewert.
     * @return array Rückgabewert.
     */
    public static function addData(string $db, string $table, array $data): array {
        return self::request(self::payloadWithToken([
            "do" => "put",
            "db" => $db,
            "table" => $table,
            "data" => $data
        ]));
    }

    /**
     * Verarbeitet die Funktion delete data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @return array Rückgabewert.
     */
    public static function deleteData(string $db, string $table, string $where, string $is): array {
        return self::request(self::payloadWithToken([
            "do" => "delete",
            "db" => $db,
            "table" => $table,
            "where" => $where,
            "is" => $is
        ]));
    }

    /**
     * Verarbeitet die Funktion edit data.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @param array $data Übergabewert.
     * @return array Rückgabewert.
     */
    public static function editData(string $db, string $table, string $where, string $is, array $data): array {
        return self::request(self::payloadWithToken([
            "do" => "edit",
            "db" => $db,
            "table" => $table,
            "where" => $where,
            "is" => $is,
            "data" => $data
        ]));
    }

    /**
     * Führt eine GreenQL-Abfrage aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function query(string $script, array $ctx = [], array $params = []): array {
        return self::request(self::payloadWithToken([
            "do" => "query",
            "query" => $script,
            "ctx" => $ctx,
            "params" => $params
        ]));
    }

    /**
     * Führt ein Script aus und gibt das Ergebnis zurück.
     * @param string $path Übergabewert.
     * @param array $params Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $path, array $params = [], array $ctx = []): array {
        return self::request(self::payloadWithToken([
            "do" => "runscript",
            "path" => $path,
            "ctx" => $ctx,
            "params" => $params
        ]));
    }

    /**
     * Initialisiert Auth auf dem Zielserver.
     * @return array Rückgabewert.
     */
    public static function auth_init(): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "init"
        ]));
    }

    /**
     * Meldet einen Benutzer über den Zielserver an.
     * @param string $username_or_email Übergabewert.
     * @param string $plain_text_password Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_login(string $username_or_email, string $plain_text_password): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "login",
            "username_or_email" => $username_or_email,
            "plain_text_password" => $plain_text_password
        ]));
    }

    /**
     * Prüft einen Auth-Token über den Zielserver.
     * @param string $jwt Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_token(string $jwt): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "token",
            "jwt" => $jwt
        ]));
    }

    /**
     * Liest Auth-Daten über den Zielserver.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_get(string $table, string $where = "", string $is = ""): array {
        $body = [
            "do" => "auth",
            "action" => "get",
            "table" => $table
        ];

        if ($where != "") {
            $body["where"] = $where;
            $body["is"] = $is;
        }

        return self::request(self::payloadWithToken($body));
    }

    /**
     * Liest einen Benutzer über den Zielserver.
     * @param string $uid Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_user(string $uid): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "user",
            "uid" => $uid
        ]));
    }

    /**
     * Legt einen Benutzer über den Zielserver an.
     * @param array $user_data Übergabewert.
     * @param array $user_meta Übergabewert.
     * @param bool $is_this_register Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_newUser(array $user_data, array $user_meta = [], bool $is_this_register = false): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "new_user",
            "user_data" => $user_data,
            "user_meta" => $user_meta,
            "is_this_register" => $is_this_register
        ]));
    }

    /**
     * Bearbeitet einen Benutzer über den Zielserver.
     * @param string $uid Übergabewert.
     * @param array $user_data Übergabewert.
     * @param array $user_meta Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_editUser(string $uid, array $user_data, array $user_meta = []): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "edit_user",
            "uid" => $uid,
            "user_data" => $user_data,
            "user_meta" => $user_meta
        ]));
    }

    /**
     * Löscht Auth-Daten über den Zielserver.
     * @param string $table Übergabewert.
     * @param string $where Übergabewert.
     * @param string $is Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_delete(string $table, string $where, string $is): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "delete",
            "table" => $table,
            "where" => $where,
            "is" => $is
        ]));
    }

    /**
     * Verifiziert eine E-Mail über den Zielserver.
     * @param string $token Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_verifyEmail(string $token): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "verify_email",
            "token" => $token
        ]));
    }

    /**
     * Verifiziert einen 2FA-Code über den Zielserver.
     * @param string $code Übergabewert.
     * @return array Rückgabewert.
     */
    public static function auth_verify2FaCode(string $code): array {
        return self::request(self::payloadWithToken([
            "do" => "auth",
            "action" => "verify_2fa",
            "code" => $code
        ]));
    }

    /**
     * Verarbeitet die Funktion srv_enqueue.
     * @param string $service Übergabewert.
     * @param string $action Übergabewert.
     * @param array $payload Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_enqueue(string $service, string $action, array $payload = []): array {
        return self::request(self::payloadWithToken([
            "do" => "srv_enqueue",
            "service" => $service,
            "action" => $action,
            "payload" => $payload
        ]));
    }

    /**
     * Verarbeitet die Funktion srv_run_one.
     * @param int $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_run_one(int $id): array {
        return self::request(self::payloadWithToken([
            "do" => "srv_run_one",
            "id" => $id
        ]));
    }

    /**
     * Verarbeitet die Funktion srv_status.
     * @param int $id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_status(int $id = null): array {
        $body = ["do" => "srv_status"];

        if ($id !== null) {
            $body["id"] = $id;
        }

        return self::request(self::payloadWithToken($body));
    }

    /**
     * Verarbeitet die Funktion srv_logs.
     * @param int $job_id Übergabewert.
     * @return array Rückgabewert.
     */
    public static function srv_logs(int $job_id): array {
        return self::request(self::payloadWithToken([
            "do" => "srv_logs",
            "job_id" => $job_id
        ]));
    }

    /**
     * Verarbeitet die Funktion srv_jobs.
     * @return array Rückgabewert.
     */
    public static function srv_jobs(): array {
        return self::request(self::payloadWithToken([
            "do" => "srv_jobs"
        ]));
    }
}
