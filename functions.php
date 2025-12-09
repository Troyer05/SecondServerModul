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
        if (Crypt::decode($_token["token"]) == $token) {
            GBDB::deleteData("main", "t", "token", Crypt::encode($token));
            return true;
        }
    }

    return false;
}

function DB_GET($db, $table, $filter = false, $where = "", $is = "") {
    if (DB_ARCH === "SQL") {
        SQL::connect();
        if ($filter) {
            return SQL::select($table, "*", $where, "'$is'");
        }
        return SQL::select($table);
    }

    return GBDB::getData($db, $table, $filter, $where, $is);
}

function DB_PUT($db, $table, $data) {
    if (DB_ARCH === "SQL") {
        SQL::connect();
        return SQL::insert($table, $data);
    }
    
    return GBDB::insertData($db, $table, $data);
}

function DB_EDIT($db, $table, $where, $is, $data) {
    if (DB_ARCH === "SQL") {
        SQL::connect();
        return SQL::update($table, $data, $where, $is);
    }
    
    return GBDB::editData($db, $table, $where, $is, $data);
}

function DB_DELETE($db, $table, $where, $is) {
    if (DB_ARCH === "SQL") {
        SQL::connect();
        return SQL::delete($table, $where, $is);
    }
    
    return GBDB::deleteData($db, $table, $where, $is);
}
