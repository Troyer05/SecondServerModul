<?php
if (isset($_GET["bib"])) {
    if ($_GET["bib"] == "create") {
        $bid = bin2hex(random_bytes(32));
        $multi_elements = isset($_POST['multi']) ? getMultiSelect($_POST['multi']) : [];
        $rechte = empty($multi_elements) ? "" : implode(",", array_unique(array_filter($multi_elements)));

        $obj = [
            "bid" => $bid,
            "name" => $_POST["name"],
            "rechte" => $rechte
        ];

        Srv::addData(DB, "bibindex", $obj);

        $obj = [
            "titel" => $_POST["name"],
            "dest" => "?docman=" . ROOT_PATH . $bid,
            "nid" => "",
            "rechte" => $rechte
        ];

        Srv::addData(DB, "navindex", $obj);

        mkdir("assets/docs/" . DB . "/" . $bid, 0777);

        updateCache("bibindex");
        updateCache("navindex");

        Ref::this_file();
    }
}
?>
