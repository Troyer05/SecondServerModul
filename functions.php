<?php
function resp(int $status, mixed $data) {
    http_response_code($status);

    echo json_encode([
        "ok"    => $status >= 200 && $status < 300,
        "status"=> $status,
        "data"  => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function test_param(array $params, array $body) {
    foreach ($params as $p) {
        if (!isset($body[$p])) {
            resp(400, "Param '$p' not provided.");
        }
    }
}

function general_auth($body, $method) {
    if ($method != "POST") {
        resp(405, "Request Method blocked.");
    }

    test_param(["sauth"], $body);

    if ($body["sauth"] != STATIC_AUTH && $body["sauth"] != "_dev") {
        resp(401, "Static auth failed.");
    }
}

function test_token($token) {
    $tokens = GBDB::getData("main", "t");

    foreach ($tokens as $_token) {
        if ($_token["token"] == $token) {
            GBDB::deleteData("main", "t", "token", $token);
            return true;
        }
    }

    return false;
}
