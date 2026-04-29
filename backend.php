<?php
include 'assets/php/inc/.config/_config.inc.php';

header("Content-Type: application/json");

$body = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER["REQUEST_METHOD"];

if (!is_array($body)) {
    resp(400, "Invalid JSON body.");
}

if (isset($body["do"]) && $body["do"] === "gtoken") {
    $token = "";

    if (($body["sauth"] ?? "") == hash("sha256", Vars::srvp_static_key())) {
        $tokens = read_tokens();

        do {
            $retry = false;
            $token = hash("sha256", bin2hex(random_bytes(256)));

            foreach ($tokens as $t) {
                if (($t["token"] ?? "") == $token) {
                    $retry = true;
                    break;
                }
            }
        } while ($retry);

        add_token($token);
    }

    resp(200, $token);
}

general_auth($body, $method);
test_param(["do"], $body);

$do = $body["do"];

if ($do == "get") {
    test_param(["db", "table"], $body);

    if (isset($body["where"]) && isset($body["is"])) {
        resp(200, DB_GET($body["db"], $body["table"], true, $body["where"], $body["is"]));
    }

    resp(200, DB_GET($body["db"], $body["table"]));
}

if ($do == "put") {
    test_param(["db", "table", "data"], $body);

    $id = DB_PUT($body["db"], $body["table"], $body["data"]);

    if ($id !== false && $id != -1) {
        resp(200, [
            "id" => $id,
            "inserted" => date("d.m.Y H:i:s")
        ]);
    }

    resp(400, "Wrong Data provided.");
}

if ($do == "delete") {
    test_param(["db", "table", "where", "is"], $body);

    DB_DELETE($body["db"], $body["table"], $body["where"], $body["is"]);
    resp(200, "Data deleted successfully.");
}

if ($do == "edit") {
    test_param(["db", "table", "where", "is", "data"], $body);

    $ok = DB_EDIT($body["db"], $body["table"], $body["where"], $body["is"], $body["data"]);

    if ($ok) {
        resp(200, "Data updated successfully.");
    }

    resp(400, "Edit failed.");
}

if ($do == "query") {
    test_param(["query"], $body);

    $ctx = isset($body["ctx"]) && is_array($body["ctx"]) ? $body["ctx"] : [];
    $params = isset($body["params"]) && is_array($body["params"]) ? $body["params"] : [];

    resp(200, DB_QUERY($body["query"], $ctx, $params));
}

if ($do == "runscript") {
    test_param(["path"], $body);

    $ctx = isset($body["ctx"]) && is_array($body["ctx"]) ? $body["ctx"] : [];
    $params = isset($body["params"]) && is_array($body["params"]) ? $body["params"] : [];

    resp(200, Srv::runScript($body["path"], $params, $ctx));
}

if ($do == "auth") {
    test_param(["action"], $body);
    resp(200, Srv::auth($body["action"], $body));
}

if ($do == "srv_enqueue") {
    test_param(["service", "action"], $body);

    $payload = $body["payload"] ?? [];
    $id = Srv::enqueue($body["service"], $body["action"], $payload);

    resp(200, [
        "job_id" => $id,
        "status" => "queued"
    ]);
}

if ($do == "srv_run_one") {
    test_param(["id"], $body);
    resp(200, Srv::runOne((int)$body["id"]));
}

if ($do == "srv_status") {
    if (isset($body["id"])) {
        resp(200, Srv::getJob((int)$body["id"]));
    }

    resp(200, Srv::getJobs());
}

if ($do == "srv_logs") {
    test_param(["job_id"], $body);
    resp(200, Srv::logs((int)$body["job_id"]));
}

if ($do == "srv_jobs") {
    resp(200, Srv::getJobs());
}

resp(404, "Unknown backend action: " . $do);
