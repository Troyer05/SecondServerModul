<?php
if (isset($_GET["save"])) {
    $obj = [];

    if ($_POST["pass"] != "") {
        $obj["password"] = hash('sha256', $_POST["pass"]);
    }

    $obj["name"] = $_POST["name"];

    Srv::editData(DB, "users", "uid", USER["uid"], $obj);

    updateCache("users");

    Ref::this_file();
}
?>
