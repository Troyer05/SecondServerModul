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

    public static function enable_https_redirect() {
        return true; // Soll der PHP Basierte HTTPS redirect aktiviert werden?
    }

    // Alle Variablen für die greenbucket API:
    public static function greenbucket_api_key() {
        return self::__DEV__() ? 
        "" // ... wenn DEV Modus
        : ""; // greenbucket API Key
    }

    public static function greenbucket_mail_api_url() {
        return "http://192.168.178.72:4901/send";
    }

    public static function greenbucket_mail_api_key() {
        return self::__DEV__() ? 
        hash('sha256', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3')
        : hash('sha256', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3'); // Key der Mail API von greenbucket
    }

    public static function greenbucket_api_version() {
        return "v1"; // greenbucket API Version
    }

    public static function greenbucket_api_url() {
        return "https://greenbucket.online/API/" . self::greenbucket_api_version() . "/api"; // greenbucket API URL
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
        return "greenbucket®"; // Ihr 'Firmen' Name
    }

    public static function company_email() {
        return "info@greenbucket-ellwangen.de"; // Ihre 'Firmen' E-Mail Adresse
    }

    public static function reCaptcha_website_key() {
        return "6LfQt3YqAAAAAFN5Ib2Vf6ZLp7sSPCAwxlgNbLLP";
    }

    public static function reCaptcha_secret_key() {
        return "6LfQt3YqAAAAAJ2XzdI-BXzOP4fql_RTrhaJ_MqN";
    }

    public static function greenQL_UI_password() {
        return ""; // Ein Passwort für die greenQL UI Festlegen
        // Ist es ein leerer String, so ist die Passwort Funktion deaktiviert.
        // ist __DEV__ true, ist die Passwort Funktion deaktiviert.
    }

    public static function second_server_key() {
        return self::__DEV__() ? 
        hash(self::second_server_hash(), 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3') // ... wenn DEV Modus
        : hash(self::second_server_hash(), 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3'); // Der Erstauthentifizierungs key für den SecondServer
        // Ersetzen Sie abc mit etwas sicherem. Desto länger und komplexer, desto sicherer
        // Achten Sie darauf, dass Sie den exakt selben Key auf Ihrem SecondServer verwenden
    }

    public static function second_server_uri() {
        // $uri = 'http://192.168.178.41/';
        $uri = 'http://localhost/gb_data/sharesuite/';
        
        if (self::__DEV__()) {
            $uri = 'http://127.0.0.1/REPOS/greenbucket/__NEU__/RS604/';
        }

        return $uri; // Die URI zur SecondServer GBDB Backend API (ohne api.php am Ende!!)

        // 'https://gb-dev08.greenbucket.online' 
        // : 'https://gb-dev08.greenbucket.online';
        // Beispiel: http://0.0.0.0/path/to/api/
    }

    public static function second_server_hash() {
        return self::__DEV__() ? 
        'sha256' // ... wenn DEV Modus
        : "sha256"; // Die Hash Methode um den SecondServer Key zu hashen
    }

    public static function second_server_token_file() {
        return self::__DEV__() ? 
        'assets/DB/framework_temp/tkns.json' // ... wenn DEV Modus
        : 'assets/DB/framework_temp/tkns.json'; // der Path zur Temporären Datei der SecondServer Authentifizierungs Tokens
        // Beachten Sie bitte, dass dieser Path bereits existieren sollte
    }

    public static function second_server_pretty() {
        return self::__DEV__() ? 
        true // ... wenn DEV Modus
        : true; // Schöne Formatierung der Temporären Dateien? 
    }

    public static function second_server_max_minutes() {
        return self::__DEV__() ? 
        5 // ... wenn DEV Modus
        : 5; // Gültigkeitsdauer von Authentifizierungs Tokens bis sie als Datenmüll gelöscht werden (Angabe in MINUTEN)
    }

    public static function second_server_jwt_maximum() {
        return self::__DEV__() ?
        44 // ... wenn DEV Modus
        : 28; // Gültigkeitsdauer in Tagen von einem JWT Token
    }

    public static function tfa_maximum_minutes() {
        return self::__DEV__() ? 
        20
        : 20; // Gültigkeitsdauer in Minuten für 2 Faktor Authentifizierung
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

    // Hier können Sie festlegen welche Daten bei einem Benutzer hinterlegt werden sollen
    // Die Spalten werden exakt erstellt, wie sie hier angegeben werden. 
    // Verwendung bei SecondServer: Achten Sie darauf, dass die Angaben identisch auf beiden Servern sind
    public static function required_user_data() {
        return [
            "uid", // "uid" --> Diese Angabe ist verpflichtend!
            "2fa", // "2fa" --> Duese Angabe ist verpflichtend!
            "username", // "username" --> Diese Angabe ist verpflichtend!
            "passwort", // "passwort" --> Diese Angabe ist verpflichtend!
            "nachname", // "nachname" --> Wenn die Mail Verifizierung aktiviert ist, ist diese Angabe verpflichtet
            "email", // "email" --> Wenn die Mail Verifizierung aktiviert ist, ist diese Angabe verpflichtet
            "status", // "status" --> Wenn die Mail Verifizierung aktiviert ist, ist diese Angabe verpflichtet
            "vorname",
            "ttk"
            // ...
        ];
    }

    public static function user_mail_verify() {
        return self::__DEV__() ? 
        true 
        : true; // Bei Benutzer hinzufügen eine Verifizierungs E-Mail versenden?
    }

    public static function mail_verify_domain() {
        return self::__DEV__() ? 
        'http://127.0.0.1/REPOS/greenbucket/__NEU__/RS602/'
        : 'https://gb-dev04.greenbucket.online'; // Domain des Verifizierungs Links (Wenn user_mail_verify true ist)
        // Beispiel: https://domain.de/   Abschließendes "/" verpflichtend
    }

    public static function fergot_password_domain() {
        return self::__DEV__() ? 
        'http://127.0.0.1/REPOS/greenbucket/__NEU__/RS602/'
        : 'https://gb-dev04.greenbucket.online'; // Domain des Passwort Vergessen Links 
        // Beispiel: https://domain.de/   Abschließendes "/" verpflichtend
    }

    public static function mail_verify_maximum() {
        return 3; // Anzahl der Tage bis eine Verifizierung verworfen wird 
    }

    public static function system_db_name() {
        return 'main'; // Name der System Datenbank
    }

    public static function users_table_name() {
        return 'users'; // Name der Tabelle mit den Benutzerdaten
    }

    public static function mail_verify_table_name() {
        return 'mailverify'; // Name der Tabelle zur Mailverifizierung
    }

    public static function password_forget_table_name() {
        return 'passwordforget'; // Name der Tabelle zur Passwort vergessen Funktion
    }

    public static function tfa_table_name() {
        return '2fa'; // Name der Tabelle für 2 Faktor Authentifizierung
    }

    public static function verify_mail_data() {
        // Hier können Sie die Verifizierungs E-Mail Daten bearbeiten

        /**
         * @see | @todo
         * 
         * Wichtige Information:
         * 
         * Bitte beachten Sie, dass folgende Elemente vom System zu tatsächlichen Daten übersetzt wird:
         * :name:
         * :link:
         * :firma:
         * 
         * Diese sind keine Platzhalter die SIE ändern sollen. Dies sind Systemanweisungen.
         * 
         * :name:  --> An dieser Stelle setzt das System den Nachnamen des Benutzers ein
         * :link:  --> An dieser Stelle setzt das System den Verifizierungs Link ein
         * :firma: --> An dieser Stelle setzt das System Ihren Firmennamen ein
         * 
         * Sie können diese Elemente einsetzen wo sie im E-Mail Text benötigt werden.
         */

        // Produktiv
        $mail_data = [
            "betreff" => "E-Mail Verifizierung",
            "absender_name" => "greenbucket AUTH",
            "absender_email" => "noreply@greenbucket-ellwangen.de",
            "mail_text" => "Sehr geehrte/r Herr/Frau :name:,<br>
                            <br>
bitte bestätigen Sie mit folgendem Link Ihre E-Mail Adresse:<br>
<br>
:link:<br>
<br>
Sollten Sie sich nie registriert haben, ignorieren Sie diese E-Mail bitte.<br>
Ohne E-Mail Verifizierung ist eine Anmeldung nicht möglich.<br>
<br>
Mit freundlichen Grüßen<br>
:firma:"
        ];

        // Wenn DEV Modus
        $dev_mail_data = [
            "betreff" => "E-Mail Verifizierung",
            "absender_name" => "greenbucket AUTH",
            "absender_email" => "noreply@greenbucket-ellwangen.de",
            "mail_text" => "Sehr geehrte/r Herr/Frau :name:,<br>
                            <br>
bitte bestätigen Sie mit folgendem Link Ihre E-Mail Adresse:<br>
<br>
:link:<br>
<br>
Sollten Sie sich nie registriert haben, ignorieren Sie diese E-Mail bitte.<br>
Ohne E-Mail Verifizierung ist eine Anmeldung nicht möglich.<br>
<br>
Mit freundlichen Grüßen<br>
:firma:"
        ];

        return self::__DEV__() ? $dev_mail_data : $mail_data;
    }

    public static function password_forget_mail_data() {
        // Hier können Sie die E-Mail Daten bearbeiten von Passwort vergessen

        /**
         * @see | @todo
         * 
         * Wichtige Information:
         * 
         * Bitte beachten Sie, dass folgende Elemente vom System zu tatsächlichen Daten übersetzt wird:
         * :name:
         * :link:
         * :firma:
         * 
         * Diese sind keine Platzhalter die SIE ändern sollen. Dies sind Systemanweisungen.
         * 
         * :name:  --> An dieser Stelle setzt das System den Nachnamen des Benutzers ein
         * :link:  --> An dieser Stelle setzt das System den Verifizierungs Link ein
         * :firma: --> An dieser Stelle setzt das System Ihren Firmennamen ein
         * 
         * Sie können diese Elemente einsetzen wo sie im E-Mail Text benötigt werden.
         */

        // Produktiv
        $mail_data = [
            "betreff" => "Passwort vergessen?",
            "absender_name" => "greenbucket AUTH",
            "absender_email" => "noreply@greenbucket-ellwangen.de",
            "mail_text" => "Sehr geehrte/r Herr/Frau :name:,<br>
            <br>
Hier ist der Link um Ihr Passwort bei :firma: zurück zu setzen:<br>
<br>
:link:<br>
<br>
Mit freundlichen Grüßen<br>
:firma:"
        ];

        // Wenn DEV Modus
        $dev_mail_data = [
            "betreff" => "Passwort vergessen?",
            "absender_name" => "greenbucket AUTH",
            "absender_email" => "noreply@greenbucket-ellwangen.de",
           "mail_text" => "Sehr geehrte/r Herr/Frau :name:,<br>
            <br>
Hier ist der Link um Ihr Passwort bei :firma: zurück zu setzen:<br>
<br>
:link:<br>
<br>
Mit freundlichen Grüßen<br>
:firma:"
        ];

        return self::__DEV__() ? $dev_mail_data : $mail_data;
    }

    public static function tfa_mail_data() {
        // Hier können Sie die E-Mail Daten bearbeiten von Passwort vergessen

        /**
         * @see | @todo
         * 
         * Wichtige Information:
         * 
         * Bitte beachten Sie, dass folgende Elemente vom System zu tatsächlichen Daten übersetzt wird:
         * :name:
         * :link:
         * :firma:
         * 
         * Diese sind keine Platzhalter die SIE ändern sollen. Dies sind Systemanweisungen.
         * 
         * :name:  --> An dieser Stelle setzt das System den Nachnamen des Benutzers ein
         * :link:  --> An dieser Stelle setzt das System den Verifizierungs Link ein
         * :firma: --> An dieser Stelle setzt das System Ihren Firmennamen ein
         * 
         * Sie können diese Elemente einsetzen wo sie im E-Mail Text benötigt werden.
         */

        // Produktiv
        $mail_data = [
            "betreff" => "2 Faktor Authentifizierung",
            "absender_name" => "greenbucket AUTH",
            "absender_email" => "noreply@greenbucket-ellwangen.de",
            "mail_text" => "Sehr geehrte/r Herr/Frau :name:,<br>
            <br>
Ihr 2 Faktor Authentifizierungs PIN lautet:<br>
<br>
:pin:<br>
<br>
Mit freundlichen Grüßen<br>
:firma:"
        ];

        // Wenn DEV Modus
        $dev_mail_data = [
           "betreff" => "2 Faktor Authentifizierung",
            "absender_name" => "greenbucket AUTH",
            "absender_email" => "noreply@greenbucket-ellwangen.de",
            "mail_text" => "Sehr geehrte/r Herr/Frau :name:,<br>
            <br>
Ihr 2 Faktor Authentifizierungs PIN lautet:<br>
<br>
:pin:<br>
<br>
Mit freundlichen Grüßen<br>
:firma:"
        ];

        return self::__DEV__() ? $dev_mail_data : $mail_data;
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
