<?php
if (isset($_GET["plugin"])) {
    if ($_GET["plugin"] == "new") {
        $obj = [
            "name" => $_POST["name"],
            "webkey" => $_POST["webkey"],
            "plugin" => GetForm::getDropdown($_POST["plugin"])
        ];

        Srv::addData(DB, "plugins", $obj);

        updateCache("plugins");

        Ref::this_file();
    } else if ($_GET["plugin"] == "del") {
        Srv::deleteData(DB, "plugins", "webkey", $_GET["webkey"]);

        updateCache("plugins");
        Ref::this_file();
    }
}
?>
