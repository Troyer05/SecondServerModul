<?php
if (isset($_GET["recht"])) {
    if ($_GET["recht"] == "add") {
        $obj = [
            "rid" => bin2hex(random_bytes(32)),
            "name" => $_POST["name"]
        ];

        Srv::addData(DB, "rechteindex", $obj);

        updateCache("rechteindex");
        updateCache("rechte");
        
        Ref::this_file();
    } else if ($_GET["recht"] == "del") {
        Srv::deleteData(DB, "rechteindex", "rid", $_GET["rid"]);

        updateCache("rechteindex");
        updateCache("rechte");
        
        Ref::this_file();
    } else if ($_GET["recht"] == "save") {
        $multi_elements = isset($_POST['multi']) ? getMultiSelect($_POST['multi']) : [];

        foreach (RECHTE_USER as $i => $r) {
            if ($r["uid"] == $_GET["uid"]) {
                $rechte = $r["recht"];
                break;
            }
        }

        $rechte = explode(",", $rechte);

        foreach ($multi_elements as $recht) {
            if (!in_array($recht, $rechte)) {
                $rechte[] = $recht;
            }
        }

        $rechte = array_filter($rechte, function($r) use ($multi_elements) {
            return in_array($r, $multi_elements);
        });

        $neueRechte = implode(",", array_unique(array_filter($rechte)));

        Srv::editData(DB, "rechte", "uid", $_GET["uid"], ["recht" => $neueRechte]);

        updateCache("rechteindex");
        updateCache("rechte");
        
        Ref::to("?id=" . $_GET["uid"]);
    }
}
?>
