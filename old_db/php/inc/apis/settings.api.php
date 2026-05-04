<?php
if (isset($_GET["settings"])) {
    if ($_GET["settings"] == "1") {
        $name = $_POST["name"];
        $farbe = $_POST["farbe"];
        $projekt = false;
        $zeit = false;
        $plugins = false;
        $tickets = false;
        $blog = false;
        $antrage = false;
        $verein = false;
        $reg = false;
        $auto = false;
        $jobsy = false;

        if (isset($_POST["projekt"])) {
            $projekt = true;
        }

        if (isset($_POST["zeit"])) {
            $zeit = true;
        }

        if (isset($_POST["plugins"])) {
            $plugins = true;
        }

        if (isset($_POST["tickets"])) {
            $tickets = true;
        }

        if (isset($_POST["blog"])) {
            $blog = true;
        }

        if (isset($_POST["antrage"])) {
            $antrage = true;
        }

        if (isset($_POST["verein"])) {
            $verein = true;
        }

        if (isset($_POST["reg"])) {
            $reg = true;
        }

        if (isset($_POST["auto"])) {
            $auto = true;
        }

        if (isset($_POST["jobsy"])) {
            $jobsy = true;
        }

        Srv::editData(DB, "settings", "id", NAME, ["param" => $name]);
        Srv::editData(DB, "settings", "id", FARBE, ["param" => $farbe]);
        Srv::editData(DB, "settings", "id", PROJEKT_ZEIT, ["param" => $projekt]);
        Srv::editData(DB, "settings", "id", ZEITERFASSUNG, ["param" => $zeit]);
        Srv::editData(DB, "settings", "id", PLUGINS, ["param" => $plugins]);
        Srv::editData(DB, "settings", "id", TICKET_SYSTEM, ["param" => $tickets]);
        Srv::editData(DB, "settings", "id", NEWS_BLOG, ["param" => $blog]);
        Srv::editData(DB, "settings", "id", ANTRAG_STELLUNG, ["param" => $antrage]);
        Srv::editData(DB, "settings", "id", VEREIN, ["param" => $verein]);
        Srv::editData(DB, "settings", "id", REGISTRIERUNG, ["param" => $reg]);
        Srv::editData(DB, "settings", "id", AUTOS, ["param" => $auto]);
        Srv::editData(DB, "settings", "id", JOBSY, ["param" => $jobsy]);

        updateCache("settings");

        Ref::this_file();
    } else if ($_GET["settings"] == "2") {
        $name = $_POST["titel"];

        $obj = [
            "nid" => bin2hex(random_bytes(32)),
            "titel" => $name,
            "rechte" => ""
        ];

        Srv::addData(DB, "navgroups", $obj);

        updateCache("navgroups");

        Ref::this_file();
    } else if ($_GET["settings"] == "3") {
        $nav = $_GET["nav"];
        $multi_elements = isset($_POST['multi']) ? getMultiSelect($_POST['multi']) : [];
    
        foreach (NAV_INDEX as $item) {
            if ($item["nid"] == $nav) {
                if (!in_array($item["id"], $multi_elements)) {
                    Srv::editData(DB, "navindex", "id", $item["id"], ["nid" => ""]);
                }
            }
        }
    
        foreach ($multi_elements as $id) {
            Srv::editData(DB, "navindex", "id", $id, ["nid" => $nav]);
        }

        Srv::editData(DB, "navgroups", "nid", $_POST["nid"], ["titel" => $_POST["name"]]);

        updateCache("navindex");
        updateCache("navgroups");
    
        Ref::to("?nav=" . $nav);
    } else if ($_GET["settings"] == "4") {
        $nav = $_GET["nav"];

        foreach (NAV_INDEX as $i => $r) {
            if ($r["nid"] == $nav) {
                Srv::editData(DB, "navindex", "id", $r["id"], ["nid" => ""]);
            }
        }

        Srv::deleteData(DB, "navgroups", "nid", $nav);

        updateCache("navindex");
        updateCache("navgroups");

        Ref::to("nav.php");
    }
}

if (isset($_GET["do"])) {
    if ($_GET["do"] == "saver") {
        $multi_elements = isset($_POST['multi']) ? getMultiSelect($_POST['multi']) : [];
        $rechte = empty($multi_elements) ? "" : implode(",", array_unique(array_filter($multi_elements)));
        $nid = $_GET["nav"];

        Srv::editData(DB, "navgroups", "nid", $nid, ["rechte" => $rechte]);

        updateCache("navindex");
        updateCache("navgroups");
        
        Ref::to("?nav=" . $nid);
    }
}
?>