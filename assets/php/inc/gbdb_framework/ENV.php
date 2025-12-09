<?php
// Hier sind alle Globale Variablen. Diese können nach 
// Bedarf geändert werden. 

class Vars {
    public static function __DEV__() {
        // Entwickler Modus für Entwicklung/Lokale Umgebung. Bei Produktiv Umgebung auf false setzen:
        if (file_exists('D:\\')) {
            if (file_exists('D:\\priv_laptop')) {
                return true;
            }
        }

        if (file_exists('C:\\daa\\daa.txt')) {
            return true;
        }

        return false;
    }

    public static function srvp_ip() {
        return "127.0.0.1/REPOS/SecondServerModul";
    }

    public static function srvp_ssl() {
        return false;
    }

    public static function srvp_static_key() {
        return "_dev";
    }

    public static function enable_https_redirect() {
        return true; // Soll der PHP Basierte HTTPS redirect aktiviert werden?
    }

    // Alle Variablen für JSON Behandlung(en):
    public static function json_path() {
        // Wenn Sie einen bestimmten Ordner verwenden um JSON Dateien ab zu legen,
        // dann können Sie den Path zu diesem Ordner hier einfügen:
        return "assets/DB/"; // WARNUNG: BITTE DENKEN SIE AN DAS ABSCHLIEßENDE /
        // Beispiel: assets/DB/
    }

    // Sollen alle JSON Daten in Dateien formatiert werden?
    public static function json_pretty() {
        return self::__DEV__() ? 
        true
        : true; // true für Formatieren
    }

    // Alle SQL Variablen für Produktiv Umgebung:
    public static function sql_server() {
        return ""; // SQL Server
    }

    public static function sql_database() {
        return ""; // SQL Datenbank
    }

    public static function sql_user() {
        return ""; // SQL User
    }

    public static function sql_password() {
        return ""; // SQL User-Passwort
    }

    // Alle SQL Variablen für Entwicklungs/Lokale Umgebung:
    public static function sql_dev_server() {
        return ""; // SQL Dev Server
    }

    public static function sql_dev_database() {
        return ""; // SQL Dev Datenbank
    }

    public static function sql_dev_user() {
        return ""; // SQL Dev User
    }

    public static function sql_dev_password() {
        return ""; // SQL Dev User-Passwort
    }

    public static function company_name() {
        return ""; // Ihr 'Firmen' Name
    }

    public static function company_email() {
        return ""; // Ihre 'Firmen' E-Mail Adresse
    }

    public static function reCaptcha_website_key() {
        return "";
    }

    public static function reCaptcha_secret_key() {
        return "";
    }

    public static function greenQL_UI_password() {
        return ""; // Ein Passwort für die greenQL UI Festlegen
        // Ist es ein leerer String, so ist die Passwort Funktion deaktiviert.
        // ist __DEV__ true, ist die Passwort Funktion deaktiviert.

        // Nachtrag: Bitte nicht ändern, das Problem ist noch nicht behoben!!
    }

    public static function crypt_data() {
        return self::__DEV__() ? 
        false
        : false; // Auf true setzen, wenn Daten verschlüsselt abgelegt werden sollen
        // Ändern ist nach Datenverkehr nicht mehr empfohlen. Bevor GBDB verwendet wird, sollte diese Einstellung vorgenommen werden.
        // Wenn diese Einstellung aktiviert ist, funktioniert nur das erstellen und Löschen von Datenbanken und Tabellen über die GreenQL UI. Der Rest der UI ist dann unbrauchbar
    }

    public static function cryptKey() {
        return self::__DEV__() ?
        "abc"
        : "abc"; // Schlüssel zum ver- und entschlüsseln der Daten
    }

    public static function data_extension() {
        return self::crypt_data() ?
        ".db" // ... wenn Daten Verschlüsselung aktiviert ist
        : ".json"; // Dateiendung der Datendateien
    }

    // Hier können Sie Cookies hinzufügen, die initial gesetzt werden sollen
    // WICHTIG: Es dürfen NUR Zahlen unnd Buchstaben verwendet werden für Cookies.
    // Nicht einmal Leerzeichen sind zulässig.
    public static function init_cookies() {
        return array(
            [
                "cookie_name" => "TestCookie", // Cookie Name
                "cookie_value" => "Test1" // Cookie Value
            ],
            [
                "cookie_name" => "Cookie2",
                "cookie_value" => "abc"
            ], // ...
        );
    }

    // Hier können Sie Session Variablen hinzufügen, die initial gesetzt werden sollen
    public static function init_session() {
        return array(
            [
                "session_name" => "pnp", // Session Variable Name
                "session_value" => "" // Session variable Value
            ],
            [
                "session_name" => "Test Session Variable 2",
                "session_value" => "Test 2"
            ], // ...
        );
    }
    
    // ========================================================================================================================================================================================
    // AB HIER BEGINNT DAS FRAMEWORK! Bearbeitung auf eigene Gefahr!
    // Sobald Sie den Code des FrameWorks bearbeiten, verfällt
    // unser Support für das greenbucket FrameWork.
    // ========================================================================================================================================================================================

    public static function this_file() {
        return basename($_SERVER['SCRIPT_FILENAME']);
    }
    
    public static function this_path() {
        return ltrim($_SERVER['SCRIPT_NAME'], '/');
    }

    public static function this_uri() {
        $scheme = $_SERVER['REQUEST_SCHEME'] . '://';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return $scheme . $host . $uri;
    }

    public static function client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $ip = str_replace(":", "-", $ip);

        return $ip;
    }

    public static function DB_PATH() {
        if (!is_dir(Vars::json_path())) {
            mkdir(Vars::json_path(), 0777);
        }

        if (!is_dir(Vars::json_path() . 'GBDB/')) {
            mkdir(Vars::json_path() . 'GBDB/', 0777);
        }

        return Vars::json_path() . 'GBDB/';
    }

    public static function jpretty() {
        if (Vars::json_pretty()) {
            return 128;
        }
        
        return 0;
    }
}
?>
