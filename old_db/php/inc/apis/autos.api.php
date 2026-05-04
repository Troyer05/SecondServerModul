<?php
// updateCache("autos");

if (isset($_GET["auto"])) {
    if ($_GET["auto"] == "add") {
        $obj = [
            "aid" => bin2hex(random_bytes(16)),
            "name" => $_POST["name"],
            "marke" => $_POST["marke"],
            "modell" => $_POST["modell"],
            "fahrgestell" => $_POST["fahrgestell"],
            "kategorie" => GetForm::getDropdown($_POST["kategorie"]),
            "nummernschild" => $_POST["kennzeichen"],
            "parkplatz" => isset($_POST["parkplatz"]) ? $_POST["parkplatz"] : "",
            "status" => "Frei"
        ];

        Srv::addData(DB, "autos", $obj);

        updateCache("autos");

        Ref::this_file();
    }

    if ($_GET["auto"] == "del") {
        $aid = $_GET["aid"];

        Srv::deleteData(DB, "autos", "aid", $aid);

        updateCache("autos");

        Ref::this_file();
    }

    if ($_GET["auto"] == "res") {
        $aid = GetForm::getDropdown($_POST["auto"]);
        $von = $_POST["von"];
        $bis = $_POST["bis"];
        $user = USER["uid"];
        $datum = Format::dateToView($_POST["datum"]);
        $km = $_POST["km"];
        $auto_frei = true;
        $heute  = new DateTime();

        foreach (AUTO_RES as $r) {
            $given  = new DateTime($r["datum"]);

            $plusEinMonat = (clone $given)->add(new DateInterval("P1M"));

            if ($heute >= $plusEinMonat) {
                Srv::deleteData(DB, "autores", "id", $r["id"]);
            }

            if ($r["aid"] == $aid && $r["datum"] == $datum) {
                $startNeu = strtotime($datum . " " . $von);
                $endeNeu  = strtotime($datum . " " . $bis);
                $startAlt = strtotime($r["datum"] . " " . $r["von"]);
                $endeAlt  = strtotime($r["datum"] . " " . $r["bis"]);

                if (!($endeNeu <= $startAlt || $startNeu >= $endeAlt)) {
                    $auto_frei = false;
                    break;
                }
            }
        }

        if (!$auto_frei) {
            Ref::to("?err=1");
        } else {
            $obj = [
                "aid" => $aid,
                "von" => $von,
                "bis" => $bis,
                "user" => $user,
                "datum" => $datum,
                "km" => $km
            ];

            Srv::addData(DB, "autores", $obj);

            updateCache("autores");

            Ref::to("hAuto.php");
        }
    }
}
?>
