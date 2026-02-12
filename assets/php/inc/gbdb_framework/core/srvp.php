<?php

class SrvP {

    // ---------------------------------------------------
    // INTERNAL HELPERS
    // ---------------------------------------------------

    private static function endpoint(): string {
        return (Vars::srvp_ssl() ? "https://" : "http://") . Vars::srvp_ip() . "/api.php";
    }

    private static function request(array $payload): array {
        // JSON BODY SICHER SENDEN
        $json = ($payload);

        $resp = Http::post(
            self::endpoint(),
            $json,
            [ "Content-Type: application/json" ]
        );

        $decoded = json_decode($resp, true);

        if ($decoded === null) {
            throw new Exception("Invalid JSON response: " . $resp);
        }

        return $decoded;
    }

    private static function getToken(): string {
        $resp = self::request([
            "sauth" => hash('sha256', Vars::srvp_static_key()),
            "do"    => "gtoken"
        ]);

        if (!isset($resp["data"])) {
            throw new Exception("Token not returned by API");
        }

        return $resp["data"];
    }

    private static function payloadWithToken(array $body): array {
        $body["sauth"] = hash('sha256', Vars::srvp_static_key());
        $body["token"] = self::getToken();

        return $body;
    }

    // ---------------------------------------------------
    // BASE CRUD
    // ---------------------------------------------------

    public static function getData(string $db, string $table, bool $filter = false, string $where = "", string $is = ""): array {

        $body = [
            "do"    => "get",
            "db"    => $db,
            "table" => $table
        ];

        if ($filter) {
            $body["where"] = $where;
            $body["is"]    = $is;
        }

        return self::request(self::payloadWithToken($body));
    }

    public static function addData(string $db, string $table, array $data): array {
        $body = [
            "do"    => "put",
            "db"    => $db,
            "table" => $table,
            "data"  => $data
        ];

        return self::request(self::payloadWithToken($body));
    }

    public static function deleteData(string $db, string $table, string $where, string $is): array {
        $body = [
            "do"    => "delete",
            "db"    => $db,
            "table" => $table,
            "where" => $where,
            "is"    => $is
        ];

        return self::request(self::payloadWithToken($body));
    }

    public static function editData(string $db, string $table, string $where, string $is, array $data): array {
        $body = [
            "do"    => "edit",
            "db"    => $db,
            "table" => $table,
            "where" => $where,
            "is"    => $is,
            "data"  => $data
        ];

        return self::request(self::payloadWithToken($body));
    }

    // ---------------------------------------------------
    // SRV JOB SYSTEM
    // ---------------------------------------------------

    public static function srv_enqueue(string $service, string $action, array $payload = []): array {
        $body = [
            "do"      => "srv_enqueue",
            "service" => $service,
            "action"  => $action,
            "payload" => $payload
        ];

        return self::request(self::payloadWithToken($body));
    }

    public static function srv_run_one(int $id): array {
        $body = [
            "do" => "srv_run_one",
            "id" => $id
        ];

        return self::request(self::payloadWithToken($body));
    }

    public static function srv_status(int $id = null): array {
        $body = [
            "do" => "srv_status"
        ];

        if ($id !== null) {
            $body["id"] = $id;
        }

        return self::request(self::payloadWithToken($body));
    }

    public static function srv_logs(int $job_id): array {
        $body = [
            "do"     => "srv_logs",
            "job_id" => $job_id
        ];

        return self::request(self::payloadWithToken($body));
    }

    public static function srv_jobs(): array {
        $body = [
            "do" => "srv_jobs"
        ];

        return self::request(self::payloadWithToken($body));
    }
}
