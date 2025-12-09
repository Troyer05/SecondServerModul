<?php
include 'assets/php/inc/.config/_config.inc.php';

header("Content-Type: application/json");

$body = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER["REQUEST_METHOD"];

if (!is_array($body)) {
    resp(400, "Invalid JSON body.");
}

// ERST checken, ob do == "gtoken"
if (isset($body["do"]) && $body["do"] === "gtoken") {
    if ($body["sauth"] != "_dev") {
        $tokens = GBDB::getData("main", "t");

        do {
            $retry = false;
            $token = hash('sha256', bin2hex(random_bytes(256)));

            foreach ($tokens as $t) {
                if ($t["token"] == $token) {
                    $retry = true;
                }
            }
        } while ($retry);

        GBDB::insertData("main", "t", ["token" => $token]);
    } else {
        $token = "_dev";
    }

    resp(200, $token); // <-- beendet Script
}

general_auth($body, $method);
test_param(["do"], $body);

$do = $body["do"];

if ($body["sauth"] !== "_dev") {
    test_param(["token"], $body);
    
    if (!test_token($body["token"])) {
        resp(401, "SAuth Token invalid.");
    }
}


if ($do == "get") {
    $ts = ["db", "table"];

    test_param($ts, $body);

    if (isset($body["where"]) && isset($body["is"])) {
        $e = GBDB::getData($body["db"], $body["table"], true, $body["where"], $body["is"]);
    } else {
        $e = GBDB::getData($body["db"], $body["table"]);
    }

    resp(200, $e);
}

if ($do == "put") {
    $ts = ["db", "table", "data"];
    
    test_param($ts, $body);

    $id = GBDB::insertData($body["db"], $body["table"], $body["data"]);

    if ($id != -1) {
        $data = [
            "id" => $id,
            "inserted" => date("d.m.Y H:i:s")
        ];

        resp(200, $data);
    } else {
        resp(400, "Wrong Data provided.");
    }
}

if ($do == "delete") {
    $ts = ["db", "table", "where", "is"];

    test_param($ts, $body);

    GBDB::deleteData($body["db"], $body["table"], $body["where"], $body["is"]);

    resp(200, "Data deleted successfully.");
}

if ($do == "edit") {
    $ts = ["db", "table", "where", "is", "data"];

    test_param($ts, $body);

    $ok = GBDB::editData($body["db"], $body["table"], $body["where"], $body["is"], $body["data"]);

    if ($ok) {
        resp(200, "Data updated successfully.");
    }

    resp(400, "Edit failed.");
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

    $result = Srv::runOne((int)$body["id"]);

    resp(200, $result);
}

if ($do == "srv_status") {

    if (isset($body["id"])) {
        $job = Srv::getJob((int)$body["id"]);
        resp(200, $job);
    }

    $jobs = Srv::getJobs();
    resp(200, $jobs);
}

if ($do == "srv_logs") {
    test_param(["job_id"], $body);

    $file = __DIR__ . "/../srv_logs/" . $body["job_id"] . ".log";

    if (!file_exists($file)) {
        resp(404, "Log not found.");
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);

    // jede Zeile wieder als JSON decoden
    $entries = array_map(fn($l) => json_decode($l, true), $lines);

    resp(200, $entries);
}

if ($do == "srv_jobs") {
    resp(200, Srv::getJobs());
}

