<?php
if (isset($_GET["antrag"])) {
    if ($_GET["antrag"] == "new") {
        $von = USER["uid"];
        $status = "Gestellt";
        $titel = $_POST["titel"];
        $text = str_replace("\r\n", "<br>", $_POST["text"]);

        $obj = [
            "aid" => bin2hex(random_bytes(32)),
            "von" => $von,
            "status" => $status,
            "titel" => $titel,
            "text" => $text,
            "datum" => date("d.m.Y")
        ];

        Srv::addData(DB, "antrage", $obj);

        updateCache("antrage");

        Ref::to("antrag_liste.php");
    } else if ($_GET["antrag"] == "del") {
        Srv::deleteData(DB, "antrage", "aid", $_GET["aid"]);

        updateCache("antrage");

        Ref::this_file();
    } else if ($_GET["antrag"] == "edit") {
        $do = GetForm::getDropdown($_POST["set"]);
        
        Srv::editData(DB, "antrage", "aid", $_GET["aid"], ["status" => $do]);

        updateCache("antrage");

        Ref::this_file();
    }
}
?>
