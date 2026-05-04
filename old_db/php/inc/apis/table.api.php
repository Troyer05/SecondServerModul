<?php
if (isset($_GET["table"])) {
    if ($_GET["table"] == "create") {
        $many = $_POST["many"];
        $name = $_POST["name"];
        $tid = bin2hex(random_bytes(32));
        $settings_table = "tbls" . $tid;
        $table = "tbl" . $tid;
        $sp = [];
        $multi_elements = isset($_POST['multi']) ? getMultiSelect($_POST['multi']) : [];
        $rechte = empty($multi_elements) ? "" : implode(",", array_unique(array_filter($multi_elements)));

        Srv::addData(DB, "tableindex", ["tid" => $tid, "name" => $name, "rechte" => $rechte]);
        Srv::createTable(DB, $settings_table, ["spalte", "typ", "show", "pflicht"]);

        for ($i = 0; $i < $many; $i++) {
            if (isset($_POST["s-" . $i])) {
                array_push($sp, $_POST["s-" . $i]);

                $show = false;
                $req = false;

                if (isset($_POST["show-" . $i])) {
                    $show = true;
                }

                if (isset($_POST["req-" . $i])) {
                    $req = true;
                }

                $obj = [
                    "spalte" => $_POST["s-" . $i],
                    "typ" => GetForm::getDropdown($_POST["styp-" . $i]),
                    "show" => $show,
                    "pflicht" => $req
                ];

                Srv::addData(DB, $settings_table, $obj);
            }
        }

        $obj = [
            "titel" => $name,
            "dest" => "table.php?tid=" . $tid,
            "nid" => "",
            "rechte" => $rechte
        ];

        Srv::addData(DB, "navindex", $obj);
        Srv::createTable(DB, $table, $sp);

        updateCache("tableindex");
        updateCache("navindex");

        Ref::to("table.php?tid=" . $tid);
    } else if ($_GET["table"] == "add") {
        $obj = [];

        foreach ($table_settings as $i => $r) {
            if (isset($_POST["inp-" . $i]) && $r["typ"] == "checkbox") {
                $obj[$r["spalte"]] = "Ja";
            } else if (!isset($_POST["inp-" . $i]) && $r["typ"] == "checkbox") {
                $obj[$r["spalte"]] = "Nein";
            } else {
                $obj[$r["spalte"]] = $_POST["inp-" . $i];
            }
        }

        Srv::addData(DB, "tbl" . $tid, $obj);

        updateCache("tableindex");
        updateCache("navindex");
        Ref::to("?tid=" . $tid);
    } else if ($_GET["table"] == "del") {
        Srv::deleteData(DB, "tbl" . $tid, "id", $_GET["id"]);
        Ref::to("?tid=" . $tid);
    } else if ($_GET["table"] == "erase") {
        Srv::deleteTable(DB, "tbl" . $tid);
        Srv::deleteTable(DB, "tbls" . $tid);
        Srv::deleteData(DB, "navindex", "dest", "table.php?tid=" . $tid);
        Srv::deleteData(DB, "tableindex", "tid", $tid);

        updateCache("tableindex");
        updateCache("navindex");

        Ref::to("main.php");
    } else if ($_GET["table"] == "edit") {
        $obj = [];

        foreach ($table_settings as $i => $r) {
            if (isset($_POST["inp-" . $i]) && $r["typ"] == "checkbox") {
                $obj[$r["spalte"]] = "Ja";
            } else if (!isset($_POST["inp-" . $i]) && $r["typ"] == "checkbox") {
                $obj[$r["spalte"]] = "Nein";
            } else {
                $obj[$r["spalte"]] = $_POST["inp-" . $i];
            }
        }

        Srv::editData(DB, "tbl" . $tid, "id", $_GET["id"], $obj);

        updateCache("tableindex");
        updateCache("navindex");
        Ref::to("?tid=" . $tid);
    }
}
?>
