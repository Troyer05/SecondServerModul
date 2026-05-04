<?php
if (isset($_GET["projekt"])) {
    if ($_GET["projekt"] == "new") {
        $pid = bin2hex(random_bytes(32));

        $obj = [
            "pid" => $pid,
            "name" => $_POST["name"],
            "soll" => $_POST["soll"]
        ];

        Srv::addData(DB, "projekte", $obj);

        updateCache("projekte");
        updateCache("projektindex");
        
        Ref::to("?pid=" . $pid);
    } else if ($_GET["projekt"] == "users") {
        $multi_elements = isset($_POST['multi']) ? getMultiSelect($_POST['multi']) : [];
    
        $aktuelle_uids = array_column(array_filter(PROJEKT_INDEX, function($p) use ($pid) {
            return $p["pid"] == $pid;
        }), "uid");
    
        foreach ($multi_elements as $uid) {
            if (!in_array($uid, $aktuelle_uids)) {
                Srv::addData(DB, "projektindex", [
                    "uid" => $uid,
                    "pid" => $pid
                ]);

                Srv::addData(DB, "tracks", [
                    "uid" => $uid,
                    "pid" => $pid,
                    "zeit" => "0.00"
                ]);
            }
        }
    
        foreach ($aktuelle_uids as $uid) {
            if (!in_array($uid, $multi_elements)) {
                Srv::deleteData(DB, "projektindex", "uid", $uid);
                
                foreach (PROJEKT_TRACKING as $i => $r) {
                    if ($r["uid"] == $uid && $r["pid"] == $pid) {
                        Srv::deleteData(DB, "tracks", "id", $r["id"]);
                    }
                }
            }
        }

        Srv::editData(DB, "projekte", "pid", $_POST["pid"], ["name" => $_POST["name"]]);

        updateCache("projekte");
        updateCache("projektindex");
    
        Ref::to("?pid=" . $pid);
    } else if ($_GET["projekt"] == "del") {
        foreach (PROJEKT_TRACKING as $i => $r) {
            Srv::deleteData(DB, "tracks", "pid", $pid);
        }

        foreach (PROJEKT_INDEX as $i => $r) {
            Srv::deleteData(DB, "projektindex", "pid", $pid);
        }

        Srv::deleteData(DB, "projekte", "pid", $pid);

        updateCache("projekte");
        updateCache("projektindex");

        Ref::to("projekte.php");
    }
}

if (isset($_GET["track"])) {
    if ($_GET["track"] == "sub") {
        $zeit = $_POST["zeit"];

        foreach (PROJEKT_TRACKING as $i => $r) {
            if (USER["uid"] == $r["uid"] && $pid == $r["pid"]) {
                $neu = calcZeiten($r["zeit"], $zeit);

                Srv::editData(DB, "tracks", "id", $r["id"], ["zeit" => $neu]);

                break;
            }
        }

        updateCache("projekte");
        updateCache("projektindex");
        updateCache("tracks");

        Ref::to("track.php");
    }
}
?>
