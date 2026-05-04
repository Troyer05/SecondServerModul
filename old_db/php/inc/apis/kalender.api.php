<?php
if (isset($_GET["kal"])) {
    if ($_GET["kal"] == "new") {
        $multi_elements = isset($_POST['multi']) ? getMultiSelect($_POST['multi']) : [];
        $rechte = empty($multi_elements) ? "" : implode(",", array_unique(array_filter($multi_elements)));
        $kid = bin2hex(random_bytes(32));

        $obj1 = [
            "kid" => $kid,
            "name" => $_POST["name"],
            "rechte" => $rechte
        ];

        $obj2 = [
            "titel" => $_POST["name"],
            "dest" => "kalender.php?id=" . $kid,
            "nid" => "",
            "rechte" => $rechte
        ];

        $kalender = ["titel", "von", "bis", "text"];

        Srv::addData(DB, "kalenderindex", $obj1);
        Srv::addData(DB, "navindex", $obj2);
        Srv::createTable(DB, "kl" . $kid, $kalender);

        updateCache("kalenderindex");
        updateCache("navindex");
        

        Ref::to("kalender.php?id=" . $kid);
    } else if ($_GET["kal"] == "del") {
        $id = $_GET["id"];

        Srv::deleteData(DB, "navindex", "dest", "kalender.php?id=" . $id);
        Srv::deleteData(DB, "kalenderindex", "kid", $id);
        Srv::deleteTable(DB, "kl" . $id);

        updateCache("kalenderindex");
        updateCache("navindex");
        

        Ref::to("new_kalender.php");
    } else if ($_GET["kal"] == "ter") {
        $id = $_GET["id"];
        $text = str_replace("\r\n", "<br>", $_POST["text"]);

        $datum = date("Y-m-d", strtotime($_POST["datum"]));
        $von = $datum . "T" . date("H:i", strtotime($_POST["von"]));
        $bis = $datum . "T" . date("H:i", strtotime($_POST["bis"]));

        $obj = [
            "titel" => $_POST["titel"],
            "von" => $von,
            "bis" => $bis,
            "text" => $text
        ];

        Srv::addData(DB, "kl" . $id, $obj);

        updateCache("kalenderindex");
        updateCache("navindex");
        
        Ref::to("?id=" . $id);
    }
}

if (isset($_GET["ter"])) {
    if ($_GET["ter"] == "del") {
        $id = $_GET["id"];
        $kal = $_GET["kal"];

        Srv::deleteData(DB, "kl" . $kal, "id", $id);

        updateCache("kalenderindex");
        updateCache("navindex");
        
        Ref::to("kalender.php?id=" . $kal);
    }
}
?>
