<?php
// Hier sind alle globalen Variablen und Helper-Funktionen des Frameworks.
// Bearbeitung der oberen Konfigurations-Bereiche ist vorgesehen.
// Ab dem "Framework"-Block sollte nur mit Bedacht geändert werden.

class Vars {
    /**
     * Cache für die Dev-Erkennung, damit nicht bei jedem Aufruf auf die Platte zugegriffen wird.
     * @var bool|null
     */
    protected static ?bool $isDev = null;

    /**
     * Entwickler Modus für Entwicklung/Lokale Umgebung.
     * Bei Produktiv Umgebung auf false setzen:
     *
     * Erkennung:
     *  - ENV GBDB_ENV=dev oder GBDB_DEV=1
     *  - bestimmte Dateien/Ordner auf Windows (dein bisheriger Mechanismus)
     *
     * @return bool
     */
    public static function __DEV__(): bool {
        if (self::$isDev !== null) {
            return self::$isDev;
        }

        // 1) Environment-Override (z.B. in Apache/Nginx/PHP-FPM gesetzt)
        $env = getenv('GBDB_ENV');

        if ($env !== false && strtolower($env) === 'dev') {
            return self::$isDev = true;
        }

        $envDevFlag = getenv('GBDB_DEV');
        
        if ($envDevFlag !== false && (int)$envDevFlag === 1) {
            return self::$isDev = true;
        }

        // 2) Deine bisherigen Marker (Windows Pfade / Dateien)
        if (file_exists('D:\\')) {
            if (file_exists('D:\\priv_laptop')) {
                return self::$isDev = true;
            }
        }

        if (file_exists('C:\\daa\\daa.txt')) {
            return self::$isDev = true;
        }

        // 3) Default: kein Dev
        return self::$isDev = false;
    }

    public static function srvp_ip()
    {
        return "127.0.0.1/REPOS/SecondServerModul";
    }

    public static function srvp_ssl()
    {
        return false; // Wird Server 2 mit HTTPS aufgerufen? (http://192.168.x.x | https://server2.deinewebapp.abc)
    }

    public static function srvp_static_key()
    {
        return "abc"; // Statischer Erstauthentifizierungsschlüssel für das Second Server Modul. Beachten: Auf Server 2 sollte exakt derselbe key verwendet werden.
    }

    public static function srvp_api_log()
    {
        return false; // Sollen API Aufrufe von Server 2 geloggt werden? (Logs können schnell sehr groß werden, jedoch werden in diesen Logs alle möglichen Infos über den Aufrufer abgelegt.)
    }

    public static function srvp_log_path()
    {
        return "assets/php/srv_logs/";
    }

    public static function enable_https_redirect()
    {
        // Soll der PHP basierte HTTPS Redirect aktiviert werden?
        return true;
    }

    // Alle Variablen für GBDB:
    public static function json_path()
    {
        // Wo soll das GBDB System die Daten ablegen?
        // WARNUNG: BITTE AN DAS ABSCHLIEßENDE / DENKEN
        return "assets/DB/";
    }

    // Sollen alle JSON Daten in Dateien formatiert werden?
    public static function json_pretty()
    {
        // DEV: schön formatiert, PROD: kompakt
        return self::__DEV__() ? true : false; // true für Formatieren
    }

    // Alle SQL Variablen für Produktiv Umgebung:
    public static function sql_server()
    {
        return ""; // SQL Server
    }

    public static function sql_database()
    {
        return ""; // SQL Datenbank
    }

    public static function sql_user()
    {
        return ""; // SQL User
    }

    public static function sql_password()
    {
        return ""; // SQL User-Passwort
    }

    // Alle SQL Variablen für Entwicklungs/Lokale Umgebung:
    public static function sql_dev_server()
    {
        return ""; // SQL Dev Server
    }

    public static function sql_dev_database()
    {
        return ""; // SQL Dev Datenbank
    }

    public static function sql_dev_user()
    {
        return ""; // SQL Dev User
    }

    public static function sql_dev_password()
    {
        return ""; // SQL Dev User-Passwort
    }

    public static function reCaptcha_website_key()
    {
        return "";
    }

    public static function reCaptcha_secret_key()
    {
        return "";
    }

    public static function crypt_data()
    {
        return true; // Verschlüsselung der Datenablage von GBDB. Sollte immer auf true bleiben. Wenn nach Nutzung von GBDB encrypted werden muss, erst variable umstellen, dann /cryption.php aufrufen
    }

    public static function cryptKey()
    {
        return "abc"; // Schlüssel zum ver- und entschlüsseln der Daten
    }

    public static function data_extension()
    {
        return self::crypt_data()
            ? ".db"   // ... wenn Daten Verschlüsselung aktiviert ist
            : ".json"; // Dateiendung der Datendateien
    }

    // Hier können Sie Cookies hinzufügen, die initial gesetzt werden sollen
    // WICHTIG: Es dürfen NUR Zahlen und Buchstaben verwendet werden für Cookies.
    // Nicht einmal Leerzeichen sind zulässig.
    public static function init_cookies()
    {
        return array(
            [
                "cookie_name"  => "TestCookie", // Cookie Name
                "cookie_value" => "Test1"       // Cookie Value
            ],
            [
                "cookie_name"  => "Cookie2",
                "cookie_value" => "abc"
            ], // ...
        );
    }

    // Hier können Sie Session Variablen hinzufügen, die initial gesetzt werden sollen
    public static function init_session()
    {
        return array(
            [
                "session_name"  => "pnp", // Session Variable Name
                "session_value" => ""     // Session variable Value
            ],
            [
                "session_name"  => "Test Session Variable 2",
                "session_value" => "Test 2"
            ], // ...
        );
    }

    // ======================================================================
    // AB HIER BEGINNT DAS FRAMEWORK! Bearbeitung auf eigene Gefahr!
    // Sobald Sie den Code des Frameworks bearbeiten, verfällt
    // unser Support für das greenbucket Framework.
    // ======================================================================

    /**
     * Sicheres Lesen von $_SERVER-Variablen mit Default.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected static function serverVar(string $key, $default = '')
    {
        return $_SERVER[$key] ?? $default;
    }

    public static function this_file()
    {
        return basename(self::serverVar('SCRIPT_FILENAME', 'index.php'));
    }

    public static function this_path()
    {
        $scriptName = self::serverVar('SCRIPT_NAME', '');
        return ltrim($scriptName, '/');
    }

    public static function this_uri()
    {
        // Scheme
        $https = self::serverVar('HTTPS', 'off');
        $scheme = (strtolower($https) === 'on') ? 'https://' : 'http://';

        // Host
        $host = self::serverVar('HTTP_HOST', 'localhost');

        // URI
        $uri = self::serverVar('REQUEST_URI', '/');

        return $scheme . $host . $uri;
    }

    public static function client_ip()
    {
        // Falls du später Proxies berücksichtigen willst, kannst du hier erweitern:
        // HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, etc.
        $ip = self::serverVar('REMOTE_ADDR', '0.0.0.0');
        $ip = str_replace(":", "-", $ip); // IPv6 -> kein Doppelpunkt in Dateinamen etc.

        return $ip;
    }

    public static function DB_PATH()
    {
        $basePath = self::json_path();
        $dbPath   = $basePath . 'GBDB/';

        // Basis-Ordner erstellen (rekursiv)
        if (!is_dir($basePath)) {
            if (!@mkdir($basePath, 0777, true) && !is_dir($basePath)) {
                trigger_error("GBDB: Konnte Basis-Ordner '{$basePath}' nicht erstellen.", E_USER_WARNING);
            }
        }

        // GBDB-Unterordner erstellen (rekursiv)
        if (!is_dir($dbPath)) {
            if (!@mkdir($dbPath, 0777, true) && !is_dir($dbPath)) {
                trigger_error("GBDB: Konnte DB-Ordner '{$dbPath}' nicht erstellen.", E_USER_WARNING);
            }
        }

        return $dbPath;
    }

    public static function jpretty()
    {
        if (self::json_pretty()) {
            return defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 128;
        }

        return 0;
    }
}
?>
