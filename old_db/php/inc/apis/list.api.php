<?php
if (isset($_GET["table"])) {
    if ($_GET["table"] == "del") {
        $tid = $_GET["tid"];

        Srv::deleteTable(DB, "tbl" . $tid);
        Srv::deleteTable(DB, "tbls" . $tid);
        Srv::deleteData(DB, "navindex", "dest", "table.php?tid=" . $tid);
        Srv::deleteData(DB, "tableindex", "tid", $tid);

        updateCache("tableindex");
        updateCache("navindex");

        Ref::this_file();
    } else if ($_GET["table"] == "edit") {
        $tid = $_GET["tid"];

        Srv::editData(DB, "tableindex", "tid", $tid, ["name" => $_POST["name"]]);
        Srv::editData(DB, "navindex", "dest", "table.php?tid=" . $tid, ["titel" => $_POST["name"]]);

        updateCache("tableindex");
        updateCache("navindex");

        Ref::this_file();
    }
}

if (isset($_GET["bib"])) {
    if ($_GET["bib"] == "del") {
        $bid = $_GET["bid"];

        Srv::deleteData(DB, "bibindex", "bid", $bid);
        Srv::deleteData(DB, "navindex", "dest", "?docman=" . ROOT_PATH . $bid);

        FS::deleteFiles("assets/docs/" . ROOT_PATH . $bid);
        FS::deleteDirectory("assets/docs/" . ROOT_PATH . $bid);

        updateCache("bibindex");
        updateCache("navindex");

        Ref::this_file();
    } else if ($_GET["bib"] == "edit") {
        $bid = $_GET["bid"];

        Srv::editData(DB, "bibindex", "bid", $bid, ["name" => $_POST["name"]]);
        Srv::editData(DB, "navindex", "dest", "?docman=" . ROOT_PATH . $bid, ["titel" => $_POST["name"]]);

        updateCache("bibindex");
        updateCache("navindex");

        Ref::this_file();
    }
}

if (isset($_GET["kal"])) {
    if ($_GET["kal"] == "del") {
        $kid = $_GET["kid"];

        Srv::deleteData(DB, "kalenderindex", "kid", $kid);
        Srv::deleteData(DB, "navindex", "dest", "kalender.php?id=" . $kid);

        updateCache("kalenderindex");
        updateCache("navindex");

        Ref::this_file();
    } else if ($_GET["kal"] == "edit") {
        $kid = $_GET["kid"];

        Srv::editData(DB, "kalenderindex", "kid", $kid, ["name" => $_POST["name"]]);
        Srv::editData(DB, "navindex", "dest", "kalender.php?id=" . $kid, ["titel" => $_POST["name"]]);

        updateCache("kalenderindex");
        updateCache("navindex");

        Ref::this_file();
    }
}
?>
