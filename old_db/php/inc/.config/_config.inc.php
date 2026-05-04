<?php
include 'assets/php/inc/gbdb_framework/gbdb.php';

define('_INC_HEADER', 'assets/php/inc/header.inc.php');
define('_INC_TOP', 'assets/php/inc/top.inc.php');
define('_INC_NAV', 'assets/php/inc/nav.inc.php');
define('_INC_API', 'assets/php/inc/apis/');

$_ORGAS = Srv::getData("main", "suites");

if (COOKIE::exists("sid") || Vars::this_file() == "api.php") {
    $sid = COOKIE::get("sid");

    if (Vars::this_file() == "api.php") {
        $sid = $body["sid"];
    }

    if (checkSID($sid)) {
        define("DB", $sid);

        define("CACHE", Srv::getData(DB, "cache"));

        define("SETTINGS", loadFromCache("settings"));
        define("USERS", loadFromCache("users"));
        define("NAV_INDEX", loadFromCache("navindex"));
        define("NAV_GROUPS", loadFromCache("navgroups"));
        define("BIB_INDEX", loadFromCache("bibindex"));
        define("TABLE_INDEX", loadFromCache("tableindex"));
        define("PROJEKTE", loadFromCache("projekte"));
        define("PROJEKT_INDEX", loadFromCache("projektindex"));
        define("PROJEKT_TRACKING", loadFromCache("tracks"));
        define("NEXT_RFID", loadFromCache("nextrfid"));
        define("TICKETS", loadFromCache("tickets"));
        define("WEBKEYS", loadFromCache("plugins"));
        define("RECHTE_INDEX", loadFromCache("rechteindex"));
        define("RECHTE_USER", loadFromCache("rechte"));
        define("BLOGS", loadFromCache("blogs"));
        define("KALENDER_INDEX", loadFromCache("kalenderindex"));
        define("API_KEYS", loadFromCache("api"));
        define("ANTRAGE", loadFromCache("antrage"));
        define("REGISTRIERUNGEN", loadFromCache("register"));
        define("AUTO_INDEX", loadFromCache("autos"));
        define("AUTO_RES", loadFromCache("autores"));
        define("ZEITEN", loadFromCache("zeiten"));
        define("RFID", loadFromCache("rfid"));
        define("JOBSY_PARAM", loadFromCache("jobsy"));
        
        define("ROOT_PATH", DB . "/");
        define("NAME", 0);
        define("FARBE", 1);
        define("PROJEKT_ZEIT", 2);
        define("ZEITERFASSUNG", 3);
        define("PLUGINS", 4);
        define("ZEIT_DIGITAL", 5);
        define("TICKET_SYSTEM", 6);
        define("NEWS_BLOG", 7);
        define("ANTRAG_STELLUNG", 8);
        define("VEREIN", 9);
        define("REGISTRIERUNG", 10);
        define("AUTOS", 11);
        define("JOBSY", 12);
        define("P", "param");

        define("JOBSY_DB", JOBSY_PARAM[0]["uid"]);

        if (SETTINGS[REGISTRIERUNG][P]) {
            $regs = count(REGISTRIERUNGEN);
        }
    } else {
        COOKIE::delete("sid");
        COOKIE::delete("u12UnS");
        COOKIE::delete("u13UnS");

        Ref::to("index.php");
    }
}

if (Vars::this_file() != "api.php") {
    $whiteList = ["index.php", "login.php", "registrieren.php", "wfr.php"];

    if (!COOKIE::exists("sid") && Vars::this_file() != "index.php") {
        Ref::to("index.php");
    }

    if (!COOKIE::exists("u12UnS") && !in_array(Vars::this_file(), $whiteList)) {
        Ref::to("login.php");
    }

    if (!COOKIE::exists("u13UnS") && !in_array(Vars::this_file(), $whiteList)) {
        Ref::to("login.php");
    }

    if (COOKIE::exists("u12UnS") && COOKIE::exists("u13UnS")) {
        $u_ok = false;
        
        define("USER", Srv::getData(DB, "users", true, "uid", COOKIE::get("u12UnS")));
        define("RECHTE", Srv::getData(DB, "rechte", true, "uid", USER["uid"]));

        if (COOKIE::get("u13UnS") == USER["password"]) {
            $u_ok = true;
        }

        if (!$u_ok && Vars::this_file() != "login.php" && Vars::this_file() != "registrieren.php" && Vars::this_file() != "wfr.php") {
            Ref::to("login.php");
        }
    }
}

/**
 * @param $post -> $_POST['multi']
 * @return array
 */
function getMultiSelect(mixed $post): array {
    $ret = [];

    foreach ($post as $value) {
        array_push($ret, $value);
    }

    return $ret;
}

function stundenZuMinuten($str) {
    $float = floatval(str_replace(",", ".", $str));
    return (int) round($float * 60);
}

function minutenZuStunden($minuten) {
    return round($minuten / 60, 2);
}

function calcZeiten($bisher, $tracking) {
    $min1 = stundenZuMinuten($bisher);
    $min2 = stundenZuMinuten($tracking);
    $gesamtMinuten = $min1 + $min2;
    $gesamtStunden = minutenZuStunden($gesamtMinuten);

    return number_format($gesamtStunden, 2, ".", "");
}

function calcStunden(array $zeiten): string {
    $gesamtMinuten = 0;

    for ($i = 0; $i < count($zeiten) - 1; $i += 2) {
        $start = $zeiten[$i];
        $ende = $zeiten[$i + 1];

        if ($start === "" || $ende === "") {
            continue;
        }

        [$sh, $sm] = explode(":", $start);
        [$eh, $em] = explode(":", $ende);

        $startMinuten = ($sh * 60) + $sm;
        $endeMinuten = ($eh * 60) + $em;

        $diff = $endeMinuten - $startMinuten;

        if ($diff > 0) {
            $gesamtMinuten += $diff;
        }
    }

    $stunden = floor($gesamtMinuten / 60);
    $minuten = $gesamtMinuten % 60;

    return "{$stunden} Stunde" . ($stunden != 1 ? "n" : "") . ", {$minuten} Minute" . ($minuten != 1 ? "n" : "");
}

function checkSID($sid) {
    $ok = false;

    foreach (Srv::getData("main", "suites") as $i => $r) {
        if ($sid == $r["sid"]) {
            $ok = true;
            break;
        }
    }

    return $ok;
}

function loadFromCache($table) {
    // Stelle sicher, dass die Session-Struktur vorhanden ist
    if (!isset($_SESSION["cache"]["updates"])) {
        $_SESSION["cache"]["updates"] = [];
    }

    // Lade aktuellen Update-Wert der Tabelle aus der CACHE-Konstanten
    $currentUpdate = null;
    foreach (CACHE as $entry) {
        if ($entry["table"] === $table) {
            $currentUpdate = $entry["update"];
            break;
        }
    }

    // Wenn es keinen Eintrag gibt, gib leeres Array zurück
    if ($currentUpdate === null) {
        return [];
    }

    // Prüfe, ob Update-Wert sich geändert hat
    $needsUpdate = !isset($_SESSION["cache"]["updates"][$table]) || 
                   $_SESSION["cache"]["updates"][$table] !== $currentUpdate;

    if ($needsUpdate) {
        // Neu aus DB laden
        $_SESSION["cache"]["data"][$table] = Srv::getData(DB, $table);
        $_SESSION["cache"]["updates"][$table] = $currentUpdate;
    }

    // Gib Daten aus Session zurück
    return $_SESSION["cache"]["data"][$table];
}

function updateCache($table) {
    Srv::editData(DB, "cache", "table", $table, ["update" => uniqid()]);
}
?>
