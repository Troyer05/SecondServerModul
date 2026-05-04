<?php
if (isset($_GET["login"])) {
    $user = $_POST["user"];
    $pass = hash('sha256', $_POST["pass"]);
    $ok = false;
    $uid = "";

    foreach (USERS as $i => $r) {
        if ($user == $r["username"]) {
            if ($pass == $r["password"]) {
                $uid = $r["uid"];
                $ok = true;

                break;
            }
        }
    }

    if ($ok) {
        COOKIE::set("u12UnS", $uid);
        COOKIE::set("u13UnS", $pass);

        Ref::to("main.php");
    }

    Ref::to("login.php?err=1");
}

if (isset($_GET["reg"])) {
    $ok = true;

    do {
        $retry = false;
        $uid = bin2hex(random_bytes(32));

        foreach (USERS as $i => $r) {
            if ($r["uid"] == $uid) {
                $retry = true;
            }
        }
    } while ($retry);

    foreach (USERS as $i => $r) {
        if ($r["username"] == $username) {
            $ok = false;
            break;
        }
    }

    if (!$ok) {
        Ref::to("?err=1");
    }

    $name = $_POST["name"];
    $username = $_POST["user"];
    $password = hash('sha256', $_POST["pass"]);

    $obj = [
        "uid" => $uid,
        "name" => $name,
        "username" => $username,
        "password" => $password,
        "datum" => date("d.m.Y")
    ];

    Srv::addData(DB, "register", $obj);

    updateCache("register");

    Ref::to("wfr.php");
}

if (isset($_GET["accept"])) {
    $uid = $_GET["accept"];
    $user = Srv::getData(DB, "register", true, "uid", $uid);

    $obj = [
        "uid" => $user["uid"],
        "name" => $user["name"],
        "username" => $user["username"],
        "password" => $user["password"]
    ];

    Srv::addData(DB, "users", $obj);
    Srv::addData(DB, "rechte", ["uid" => $uid, "recht" => "normal"]);
    
    Srv::deleteData(DB, "register", "uid", $uid);

    mkdir("assets/docs/" . DB . "/users/" . $uid, 0777);

    updateCache("register");
    updateCache("users");

    Ref::this_file();
}

if (isset($_GET["decline"])) {
    $uid = $_GET["decline"];
    $user = Srv::getData(DB, "register", true, "uid", $uid);

    updateCache("register");
    updateCache("users");

    Srv::deleteData(DB, "register", "uid", $uid);
    Ref::this_file();
}
?>
