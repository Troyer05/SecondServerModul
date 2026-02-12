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
    test_param(["token"], $body);

    if ($body["sauth"] != hash('sha256', Vars::srvp_static_key())) {
        resp(401, "Static auth failed.");
    }

    if (!test_token($body["token"])) {
        resp(401, "Token auth failed.");
    }
    
    delete_token($body["token"]);
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

function _token_file_path(): string {
    $rel = "assets/DB/framework_temp/_srvtkns.cry";

    $bases = [];

    // Falls dein Framework sowas definiert
    foreach (["_ROOT", "ROOT", "BASE_PATH", "APP_ROOT"] as $c) {
        if (defined($c)) $bases[] = constant($c);
    }

    // Fallbacks
    if (!empty($_SERVER["DOCUMENT_ROOT"])) $bases[] = rtrim($_SERVER["DOCUMENT_ROOT"], "/\\") . DIRECTORY_SEPARATOR;
    $bases[] = __DIR__ . DIRECTORY_SEPARATOR; // letzter Fallback (relativ zur aktuellen Datei)

    foreach ($bases as $b) {
        $b = rtrim($b, "/\\") . DIRECTORY_SEPARATOR;
        $p = $b . str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rel);

        $dir = dirname($p);
        if (is_dir($dir) || @mkdir($dir, 0775, true)) {
            return $p;
        }
    }

    // Wenn wirklich gar nichts klappt
    return str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $rel);
}

function _tkn_key(): string {
    // Nutzt deinen Static Key als Basis und macht daraus 32 Byte Key
    return hash("sha256", Vars::srvp_static_key(), true);
}

function _tkn_encrypt(string $plain): string {
    if (!function_exists("openssl_encrypt")) {
        // Notfalls unverschlüsselt (besser als kaputt)
        return "PLAIN:" . $plain;
    }

    $key = _tkn_key();
    $iv  = random_bytes(12); // empfohlen für GCM
    $tag = "";

    $cipher = openssl_encrypt($plain, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($cipher === false) {
        return "PLAIN:" . $plain;
    }

    // Format: GBDBTKN1:<b64(iv|tag|cipher)>
    $blob = $iv . $tag . $cipher;
    
    return "GBDBTKN1:" . base64_encode($blob);
}

function _tkn_decrypt(string $raw): string {
    $raw = trim($raw);

    if ($raw === "") return "";

    if (str_starts_with($raw, "PLAIN:")) {
        return substr($raw, 6);
    }

    if (!str_starts_with($raw, "GBDBTKN1:")) {
        // unbekanntes Format -> versuchen als Plain JSON
        return $raw;
    }

    if (!function_exists("openssl_decrypt")) return "";

    $b64  = substr($raw, 8);
    $blob = base64_decode($b64, true);

    if ($blob === false || strlen($blob) < (12 + 16 + 1)) return "";

    $iv     = substr($blob, 0, 12);
    $tag    = substr($blob, 12, 16);
    $cipher = substr($blob, 28);

    $plain = openssl_decrypt($cipher, "aes-256-gcm", _tkn_key(), OPENSSL_RAW_DATA, $iv, $tag);

    return ($plain === false) ? "" : $plain;
}

function read_tokens(): array { // Auslesen der Datei "assets/DB/framework_temp/_srvtkns.cry"
    $file = _token_file_path();

    if (!file_exists($file)) {
        return [];
    }

    $fp = @fopen($file, "rb");
    if (!$fp) return [];

    try {
        // Shared lock fürs Lesen
        @flock($fp, LOCK_SH);
        $raw = stream_get_contents($fp);
        @flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }

    $json = _tkn_decrypt((string)$raw);
    if ($json === "") return [];

    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];

    // Normalisieren: nur gültige Einträge zurückgeben
    $out = [];

    foreach ($arr as $row) {
        if (is_array($row) && isset($row["token"]) && is_string($row["token"]) && $row["token"] !== "") {
            $out[] = [
                "token"   => $row["token"],
                "created" => isset($row["created"]) ? (int)$row["created"] : time()
            ];
        }
    }

    return $out;
}

function add_token(string $token): void { // Einfügen des Tokens in die Datei
    $file = _token_file_path();
    $dir  = dirname($file);

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $fp = @fopen($file, "c+"); // create if not exists
    if (!$fp) {
        resp(500, "Token storage not writable.");
    }

    try {
        @flock($fp, LOCK_EX);

        // read existing
        rewind($fp);

        $raw  = stream_get_contents($fp);
        $json = _tkn_decrypt((string)$raw);
        $arr  = json_decode($json, true);

        if (!is_array($arr)) $arr = [];

        // wenn schon vorhanden -> nix tun
        foreach ($arr as $row) {
            if (is_array($row) && isset($row["token"]) && $row["token"] === $token) {
                @flock($fp, LOCK_UN);
                return;
            }
        }

        $arr[] = [
            "token"   => $token,
            "created" => time()
        ];

        $plain = json_encode($arr, JSON_UNESCAPED_UNICODE);

        if ($plain === false) $plain = "[]";

        $enc = _tkn_encrypt($plain);

        // write back
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, $enc);
        fflush($fp);

        @flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
}

function delete_token(string $token): void { // Löschen des Tokens aus der Datei
    $file = _token_file_path();
    if (!file_exists($file)) return;

    $fp = @fopen($file, "c+");
    if (!$fp) return;

    try {
        @flock($fp, LOCK_EX);

        rewind($fp);

        $raw  = stream_get_contents($fp);
        $json = _tkn_decrypt((string)$raw);
        $arr  = json_decode($json, true);

        if (!is_array($arr)) $arr = [];

        $new = [];

        foreach ($arr as $row) {
            if (is_array($row) && isset($row["token"]) && $row["token"] === $token) {
                continue;
            }

            $new[] = $row;
        }

        $plain = json_encode($new, JSON_UNESCAPED_UNICODE);

        if ($plain === false) $plain = "[]";

        $enc = _tkn_encrypt($plain);

        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, $enc);
        fflush($fp);

        @flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }
}

function test_token(string $token): bool { // Ist $token in der Token-Datei?
    $tokens = read_tokens();

    foreach ($tokens as $t) {
        if (isset($t["token"]) && hash_equals($t["token"], $token)) {
            return true;
        }
    }

    return false;
}