<?php
if (isset($_GET["chip"])) {
    if ($_GET["chip"] == "do") {
        $do = GetForm::getDropdown($_POST["do"]);

        Srv::editData(DB, "nextrfid", "id", 0, ["do" => $do]);

        if (isset($_POST["digi"])) {
            Srv::editData(DB, "settings", "id", ZEIT_DIGITAL, ["param" => true]);
        } else {
            Srv::editData(DB, "settings", "id", ZEIT_DIGITAL, ["param" => false]);
        }

        updateCache("rfid");
        updateCache("nextrfid");
        updateCache("settings");

        Ref::this_file();
    } else if ($_GET["chip"] == "user") {
        if (isset($_POST["user"]) && isset($_POST["chip"])) {
            $uid = GetForm::getDropdown($_POST["user"]);
            $rfid = GetForm::getDropdown($_POST["chip"]);

            Srv::editData(DB, "rfid", "rfid", $rfid, ["uid" => $uid]);
        }

        updateCache("rfid");


        Ref::this_file();
    } else if ($_GET["chip"] == "un") {
        $rfid = $_GET["rfid"];

        Srv::editData(DB, "rfid", "rfid", $rfid, ["uid" => ""]);

        updateCache("rfid");

        Ref::this_file();
    } else if ($_GET["chip"] == "del") {
        $rfid = $_GET["rfid"];

        Srv::deleteData(DB, "rfid", "rfid", $rfid);

        updateCache("rfid");

        Ref::this_file();
    } else if ($_GET["chip"] == "digi") {
        $many = $_POST["chips"];
        $nextChip = 1;
        
        for ($i = 0; $i < $many; $i++) {
            $rfid = hash('sha256', bin2hex(random_bytes(32)));
            $chips = Srv::getData(DB, "rfid");

            foreach ($chips as $j => $r) {
                $nextChip = $r["chip"] + 1;
            }

            Srv::addData(DB, "rfid", ["chip" => $nextChip, "uid" => "", "rfid" => $rfid]);
        }

        updateCache("rfid");

        Ref::this_file();
    }
}

if (isset($_GET["zeit"])) {
    if ($_GET["zeit"] == "buch") {
        $rfid = $_POST["rfid"];
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://localhost/REPOS/greenbucket/__NEU__/sub_webs/api.greenbucket.online/zeiterfassung/index.php',//'https://api.greenbucket.online/zeiterfassung/index.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "auth": "e93bd9071a832950b2b0f57907aad22f416587dde2729250e7ea3c5ce6e3f512",
            "cid": "' . DB . '",
            "rfid": "' . $rfid . '"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Cookie: Cookie2=abc; PHPSESSID=mjmqes0l6f7d4dt00q2s9jg65u; TestCookie=Test1'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        Ref::to("?buch=1");
    }
}
?>
