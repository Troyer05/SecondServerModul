<?php
if (isset($_GET["ogr"])) {
    $orga = $_GET["ogr"];
    $ok = false;

    foreach ($_ORGAS as $i => $r) {
        if ($r["orga"] == $orga) {
            COOKIE::set("sid", $r["sid"]);
            COOKIE::set("ogr", $orga);
            
            $ok = true;

            break;
        }
    }

    if ($ok) {
        Ref::to("login.php");
    } else {
        Ref::this_file();
    }
}

if (isset($_GET["orga"])) {
    $orga = $_POST["orga"];
    $ok = false;

    foreach ($_ORGAS as $i => $r) {
        if ($r["orga"] == $orga) {
            COOKIE::set("sid", $r["sid"]);
            COOKIE::set("ogr", $orga);
            
            $ok = true;

            break;
        }
    }

    if ($ok) {
        Ref::to("login.php");
    } else {
        Ref::this_file();
    }
}

if (COOKIE::exists("sid")) {
    Ref::to("login.php");
}

// 494290
?>
