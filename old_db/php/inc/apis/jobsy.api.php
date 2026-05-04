<?php
if (isset($_GET["set"])) {
    if ($_GET["set"] == "uid") {
        Srv::editData(DB, "jobsy", "id", 0, ["uid" => $_POST["uid"]]);
        
        updateCache("jobsy");

        Ref::this_file();
    }

    if ($_GET["set"] == "mails") {
        Jobsy::send(JOBSY_DB, "edit", "mails", "id", 0, ["zusage" => $_POST["zusage"], "absage" => $_POST["absage"]]);
        Ref::this_file();
    }

    if ($_GET["set"] == "stelle") {
        $obj = [
            "sid" => uniqid(),
            "name" => $_POST["stelle"],
            "created_at" => date("d.m.Y"),
            "created_from" => "ShareSuite Anbindung",
            "status" => "Aktiv"
        ];

        Jobsy::send(JOBSY_DB, "add", "stellen", 0, 0, $obj);
        Ref::this_file();
    }

    if ($_GET["set"] == "frage") {
        $obj = [
            "text" => $_POST["frage"],
            "min_score" => $_POST["score"],
        ];

        Jobsy::send(JOBSY_DB, "add", "fragen", 0, 0, $obj);
        Ref::this_file();
    }

    if ($_GET["set"] == "comment") {
        Jobsy::send(JOBSY_DB, "edit", "bewerbungen", "bid", $_GET["bid"], ["kommentar" => $_POST["text"]]);
        Ref::to("?bid=" . $_GET["bid"]);
    }
}

if (isset($_GET["del"])) {
    if ($_GET["del"] == "stelle") {
        Jobsy::send(JOBSY_DB, "delete", "stellen", "sid", $_GET["sid"]);
        Ref::this_file();
    }

    if ($_GET["del"] == "frage") {
        Jobsy::send(JOBSY_DB, "delete", "fragen", "id", $_GET["id"]);
        Ref::this_file();
    }
}

if (isset($_GET["deakt"])) {
    Jobsy::send(JOBSY_DB, "edit", "stellen", "sid", $_GET["deakt"], ["status" => "Inaktiv"]);
    Ref::this_file();
}

if (isset($_GET["akt"])) {
    Jobsy::send(JOBSY_DB, "edit", "stellen", "sid", $_GET["akt"], ["status" => "Aktiv"]);
    Ref::this_file();
}
?>
