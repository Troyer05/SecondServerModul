<?php
if (isset($_GET["ticket"])) {
    if ($_GET["ticket"] == "new") {
        $titel = $_POST["titel"];
        $text = str_replace("\r\n", "<br>", $_POST["text"]);
        $tid = bin2hex(random_bytes(32));
        $von = USER["uid"];
        $status = "Offen";
        $antwort = "";
        $datum = date("d.m.Y");
        $uhrzeit = date("H:i");

        $obj = [
            "tid" => $tid,
            "titel" => $titel,
            "von" => $von,
            "text" => $text,
            "status" => $status,
            "antwort" => $antwort,
            "datum" => $datum,
            "uhrzeit" => $uhrzeit
        ];

        Srv::addData(DB, "tickets", $obj);

        updateCache("tickets");

        Ref::to("mTickets.php");
    } else if ($_GET["ticket"] == "ant") {
        $tid = $_GET["t"];
        $status = GetForm::getDropdown($_POST["status"]);
        $antwort = $_POST["antwort"];

        $obj = [
            "antwort" => $antwort,
            "status" => $status
        ];

        Srv::editData(DB, "tickets", "tid", $tid, $obj);

        updateCache("tickets");
        
        Ref::to("?t=" . $tid);
    }
}
?>
