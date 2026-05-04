<?php
if (isset($_GET["user"])) {
    if ($_GET["user"] == "add") {
        do {
            $uid = bin2hex(random_bytes(32));
            $ok = false;

            foreach (USERS as $i => $r) {
                if ($uid == $r["uid"]) {
                    $ok = true;
                    break;
                }
            }
        } while ($ok);

        foreach (USERS as $i => $r) {
            if ($r["username"] == $_POST["username"]) {
                Ref::this_file();
            }
        }

        $obj = [
            "uid" => $uid,
            "name" => $_POST["name"],
            "username" => $_POST["username"],
            "password" => hash('sha256', $_POST["pass"])
        ];

        if (isset($_POST["admin"])) {
            Srv::addData(DB, "rechte", ["uid" => $uid, "recht" => "admin"]);
        } else {
            Srv::addData(DB, "rechte", ["uid" => $uid, "recht" => "normal"]);
        }

        mkdir("assets/docs/" . DB . "/users/" . $uid, 0777);

        Srv::addData(DB, "users", $obj);

        $obj = [
            "name" => "Kalender von " . $_POST["name"],
            "kid" => $uid,
            "rechte" => ""
        ];

        Srv::addData(DB, "kalenderindex", $obj);
        Srv::createTable(DB, "kl" . $uid, ["titel", "von", "bis", "text"]);

        updateCache("users");
        updateCache("rechte");
        updateCache("kalenderindex");
        

        Ref::this_file();
    } else if ($_GET["user"] == "del") {
        Srv::deleteData(DB, "users", "uid", $_GET["uid"]);
        Srv::deleteData(DB, "rechte", "uid", $_GET["uid"]);
        Srv::deleteData(DB, "rfid", "uid", $_GET["uid"]);
        Srv::deleteData(DB, "tracks", "uid", $_GET["uid"]);
        Srv::deleteData(DB, "zeiten", "uid", $_GET["uid"]);
        Srv::deleteData(DB, "tickets", "von", $_GET["uid"]);
        Srv::deleteData(DB, "blogs", "uid", $_GET["uid"]);
        Srv::deleteData(DB, "chat", "von", $_GET["uid"]);
        Srv::deleteData(DB, "chat", "zu", $_GET["uid"]);
        Srv::deleteData(DB, "kalenderindex", "kid", $_GET["uid"]);
        Srv::deleteTable(DB, "kl" . $_GET["uid"]);
        Srv::deleteData(DB, "antrage", "von", $_GET["uid"]);

        FS::deleteFiles('assets/docs/' . DB . '/users/' . $_GET["uid"]);
        FS::deleteDirectory('assets/docs/' . DB . '/users/' . $_GET["uid"]);

        updateCache("users");
        updateCache("rechte");
        updateCache("kalenderindex");
        
        
        Ref::this_file();
    } else if ($_GET["user"] == "edit") {
        $obj = [];

        if ($_POST["pass"] != "") {
            $obj["password"] = hash('sha256', $_POST["pass"]);
        }

        $obj["name"] = $_POST["name"];

        if (isset($_POST["username"])) {
            $obj["username"] = $_POST["username"];
        }

        if (!isset($_POST["admin"]) && isset($_POST["username"])) {
            Srv::editData(DB, "rechte", "uid", $_GET["uid"], ["recht" => "normal"]);
        }

        if (isset($_POST["admin"]) && isset($_POST["username"])) {
            Srv::editData(DB, "rechte", "uid", $_GET["uid"], ["recht" => "admin"]);
        }

        Srv::editData(DB, "users", "uid", $_GET["uid"], $obj);

        updateCache("users");
        updateCache("rechte");
        updateCache("kalenderindex");
        
        Ref::this_file();
    }
}
?>
