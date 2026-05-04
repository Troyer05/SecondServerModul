<?php
if (isset($_GET["blog"])) {
    updateCache("blogs");

    if ($_GET["blog"] == "new") {
        $text = str_replace("\r\n", "<br>", $_POST["text"]);

        $obj = [
            "bid" => bin2hex(random_bytes(32)),
            "titel" => $_POST["titel"],
            "text" => $text,
            "uid" => USER["uid"]
        ];

        Srv::addData(DB, "blogs", $obj);

        updateCache("blogs");

        Ref::to("main.php");
    } else if ($_GET["blog"] == "del") {
        $id = $_GET["id"];

        Srv::deleteData(DB, "blogs", "bid", $id);

        updateCache("blogs");

        Ref::this_file();
    } else if ($_GET["blog"] == "edit") {
        $text = str_replace("\r\n", "<br>", $_POST["text"]);

        $obj = [
            "titel" => $_POST["titel"],
            "text" => $text
        ];

        Srv::editData(DB, "blogs", "bid", $_GET["id"], $obj);

        updateCache("blogs");

        Ref::to("main.php");
    }
}
?>
