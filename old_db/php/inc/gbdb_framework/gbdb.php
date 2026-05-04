<?php
/**
 * greenbucket® PHP 8.1 EasyFramework mit Datenbanksystem
 * 
 * Dieses FrameWork ist Open source und kostenlos.
 * Es wurde von einer Person entwickelt, um einige Funktionen
 * übersichtlicher und einfacher zu bündeln. Dieses FrameWork
 * wird bei greenbucket eingesetzt und steht unter dessen Copyright.
 * Das Framework darf von jedem genutzt- und geändert werden.
 * Bei Änderungen, wird darum gebeten, diese mit Kommentaren als eigene
 * Änderungen zu kennzeichnen. Eine eigene Veröffentlichung des FrameWorks
 * ohne Genehmigung ist untersagt. Dieses FrameWork wurde von
 * Markus Müller entwickelt für PHP 8.1
 * 
 * Gibt es Fragen, Verbesserungen, Wünsche oder Probleme, 
 * können diese auf Github im ISSUES Bereich gemeldet werden.
 * 
 * Erste Schritt: Gehen Sie in die ENV.php und passen alle Variablen so an,
 * dass es perfekt auf Ihr Projekt zugeschnitten werden kann.
 *
 * @author dr. h. c. Markus Müller CIO -> greenbucket®
 * @copyright greenbucket®
 * @internal use only
 * @since 2023
 * @uses PHP 8.1
 * @license Apache2
 * @global gbdb.php
 * @source https://github.com/Troyer05/phpFramework
 * @see https://github.com/Troyer05/phpFramework
 * 
 * Wie verwendet man dieses Framework?
 * Einfach diese Datei bei den PHP Dateien, in denen Sie das
 * Framework nutzen wollen ganz oben via include oder require einbinden
 * 
 * DOKUMENTATION
 * Die Vollständige Dokumentation ist erhältlich unter
 * @link https://github.com/Troyer05/phpFramework
 * 
 * Bitte Chekcen Sie das FrameWork regelmäßig auf Updates
 * Bitte beachten Sie, dass das gbDB FrameWork Lese- und Schreibrechte benötigt
 * um vollständig verwendet werden zu können
 * 
 * Es sind weitere Funktionen für zukünfige Versionen in Arbeit.
 * 
 * @version 1.5
 * 
**/

// In der ENV.php können Sie alle Globalen Variablen setzen. 
// Diese wurden in die Datei ausgelagert, damit zukünftige
// Updates Ihres Frameworks nur noch via COPY & PASTE durchgeführt
// werden können, ohne dabei alle von Ihnen gesetzten Variablen
// zu resetten. (Bzw. dass nur die gbdb.php mit der neuen gbdb.php ersetzt werden muss)
require 'ENV.php';

// ========================================================================================================================================================================================
// AB HIER BEGINNT DAS FRAMEWORK! Bearbeitung auf eigene Gefahr!
// Sobald Sie den Code des FrameWorks bearbeiten, verfällt
// unser Support für das greenbucket FrameWork.
// ========================================================================================================================================================================================

class FS {
    /**
     * Schreibt JSON Daten in eine .json Datei
     * @param string $file der path zur .json Datei
     * @param mixed $data die JSON Daten als PHP Array, der in die Datei geschrieben werden soll
     * @param bool $add (Optional, Standart true) sollen die Daten an die Datei angefügt werden (bei false wird überschrieben)
     * @param bool $pretty (Optional, Standard false) sollen die JSON Daten schön Formatiert werden (bei false = Einzeiler)
     * @return bool true wenn es keine Probleme gab
     */
    public static function write_json(string $file, mixed $data, bool $add = true, bool $pretty = false): bool {
        $data = array_values($data);

        if ($add) {
            $tmp = self::read_json($file);
            array_push($tmp, $data);
            $data = $tmp;
        }

        $file = Vars::json_path() . $file;

        $pretty
            ? file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))
            : file_put_contents($file, json_encode($data));

        return true;
    }

    /**
     * Liest JSON Daten aus einer .json Datei heraus und stellt sie PHP Formatiert bereit
     * @param string $file der path zur .json Datei
     * @return mixed die JSON Daten 
     */
    public static function read_json(string $file): mixed {
        return json_decode(file_get_contents(Vars::json_path() . $file, true), true);
    }

    public static function createFolder(string $pathAndName): void {
        mkdir($pathAndName, 0777);
    }

    /**
     * Schreibt normale Dateien in eine Datei
     * @param string $file path zur Datei
     * @param mixed $data die Daten die in die Datei geschrieben werden sollen
     * @param bool $stream (Optional, Standart false) ob es ein Filestream sein soll oder als stack etwa später geschrieben werden kann
     * @param bool $overwrite (Optional, Standart false) ob Daten überschrieben werden sollen oder angehängt werden sollen
     * @return bool true wenn es keine Probleme gab
     */
    public static function write(string $file, mixed $data, bool $stream = false, bool $overwrite = false): bool {
        if ($stream) {
            $f = ($overwrite ? fopen($file, 'w') : fopen($file, 'a+'));
            fwrite($f, $data);
            fclose($f);

            return true;
        } else {
            file_put_contents($file, $data);
            return true;
        }
    }

    /**
     * Liest  Daten aus einer Datei heraus
     * @param string $file der path zur Datei
     * @return mixed die Daten
     */
    public function read(string $file): mixed {
        return file_get_contents($file);
    }

    /**
     * Löscht ein Verzeichnis
     * @param string $dir path zum Verzeichnis
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteDirectory(string $dir): bool {
        if (is_dir($dir)) {
            $files = scandir($dir);

            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $path = $dir . "/" . $file;

                    if (is_dir($path)) {
                        FS::deleteDirectory($path);
                    } else {
                        unlink($path);
                    }
                }
            }

            rmdir($dir);
            return true;
        }

        return false;
    }

    /**
     * Gibt die Größe eines Verzeichnisses wieder
     * @param string $path path zum Verzeichnis
     * @return string die Größe des Verzeichnisses
     */
    public static function getFolderSize(string $path): string {
        $size = 0;

        if (!file_exists('assets/wasm/fssize.wasm')) {
            die("FEHLER: fssize.wasm konnte nicht gefunden werden!");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $size += $file->getSize();
        }

        if ($size >= 1125899906842624) { // PB
            return number_format($size / 1125899906842624, 2) . ' PB';
        } elseif ($size >= 1099511627776) { // TB
            return number_format($size / 1099511627776, 2) . ' TB';
        } elseif ($size >= 1073741824) { // GB
            return number_format($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) { // MB
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) { // KB
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' B';
        }
    }

    /**
     * Löscht alle Dateien in einem Ordner
     * @param string $path Path zum Ordner
     * @return bool true wenn es keine Probleme gab
    */
    public static function deleteFiles(string $path): bool {
        if (!is_dir($path)) {
            return false;
        }
    
        $dir = opendir($path);

        if (!$dir) {
            return false;
        }
    
        while (($file = readdir($dir)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
    
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
    
            if (is_file($filePath)) {
                if (!unlink($filePath)) {
                    closedir($dir);
                    return false;
                }
            }
        }
    
        closedir($dir);
        
        return true;
    }
}

class Ref {
    /**
     * Leitet auf eine andere Datei/Seite weiter
     * @param string $url die URL der Seite oder den Path der Datei
     */
    public static function to(string $url): void {
        echo '<meta http-equiv="refresh" content="0; URL=' . $url . '">';
        exit;
    }

    /**
     * Ladet aktuelle Seite neu
     */
    public static function this_file(): void {
        echo '<meta http-equiv="refresh" content="0; URL=' . Vars::this_file() . '">';
        exit;
    }
}

class GetForm {
    /**
     * Liest ein Dropdown aus und gibt die explizite Auswahl wieder
     * @param mixed $dropdown die POST Variable des Dropdowns (@example $_POST["drop1"])
     * @return mixed Explizite Userauswahl
     */
    public static function getDropdown(mixed $dropdown): mixed {
        $e = "";

        foreach ($dropdown as $val) {
            $e = $val;
        }

        return $e;
    }

    /**
     * Funktion zum Hochladen von Dateien (Max. 2 MB Zugelassen)
     * @param mixed $file Datei(en) der POST Methode zum Hochladen
     * @param string $path (OPTIONAL) Path Wohin die Datei(en) hochgeladen werden sollen
     * @param string $useName (OPTIONAL) Wenn die Datei einen speziellen Namen haben soll
     * @return bool true wenn es keine Probleme gab
     */
    public static function upload(mixed $file, string $path = "./", string $useName = ""): bool {
        if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > (250 * 1024)) {
            return false;
        }
    
        $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($file['name']));
        $fileName = str_replace('..', '', $fileName); 
    
        // Erlaubte Dateiendungen
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'txt', 'docx', 'doc', 'xls', 'ppt', 'ppts', 'webp'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return false;
        }
    
        if ($useName == "") {
            $fileName = uniqid() . '_' . $fileName;
        } else {
            $fileName = rtrim($useName, '.') . '.' . $fileExtension;
        }
    
        if (!is_dir($path) || !is_writable($path)) {
            return false;
        }
    
        if (move_uploaded_file($file['tmp_name'], $path . '/' . $fileName)) {
            return true;
        } else {
            return false;
        }
    }    

    public static function check_required_fields(mixed $post_data): mixed {
        $empty_fields = [];
    
        foreach ($post_data as $field_name => $value) {
            if (substr($field_name, -3) === '_rf') {
                if (empty($value) || $value == " " || $value == "  ") {
                    $empty_fields[] = $field_name;
                }
            }
        }
    
        if (empty($empty_fields)) {
            return 0;
        }
    
        return $empty_fields;
    }
    
    public static function createInput(string $name, string $type, mixed $form_data, string $placeholder = "", string $class = "", string $id = ""): string {
        $value = htmlspecialchars($form_data[$name] ?? '');
        $element = '<input type="' . $type . '" name="' . $name . '" placeholder="' . $placeholder . '" value="' . $value . '"';
        
        if (empty($class)) {
            $class = "";
        }

        // $element .= ' class="gLFyf gsfi ' . $class . '"';
        // $element .= ' jsaction="paste:puy29d;" maxlength="2048"';
        // $element .= ' aria-autocomplete="both" aria-haspopup="false" autocapitalize="off" autocomplete="off" autocorrect="off" autofocus=""';
        // $element .= ' spellcheck="false"';
        // $element .= ' aria-label="Pesquisar" data-ved="0ahUKEwjw0svW6brxAhWdqJUCHXoYDRsQ39UDCAQ"';

        if (!empty($id)) {
            $element .= ' id="' . $id . '"';
        }
        
        $element .= ' />';
        
        return $element;
    }

    public static function checkPost(): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return true;
        }

        return false;
    }
}

class Format {
    /**
     * Formatiert ein Datum korrekt für ein HTML Input Feld des Typen date
     * @param mixed $date das zu formatierende Datum
     * @return mixed das korrekt formatierte Datum
     */
    public static function dateForInput(mixed $date): mixed {
        return date('Y-m-d', strtotime($date));
    }

    /**
     * Formatiert eine Uhrzeit korrekt für ein HTML Input Feld des Typen time
     * @param mixed $time die zu formatierende Zeit
     * @return mixed die korrekt formatierte Zeit
     */
    public static function timeForInput(mixed $time): mixed {
        return date('H:i:s', strtotime($time));
    }

    /**
     * Formatiert ein Datum korrekt zum Anzeigen für einen Nutzer
     * @param mixed $date das zu formatierende Datum
     * @return mixed das korrekt formatierte Datum
     */
    public static function dateToView(mixed $date) {
        return date('d.m.Y', strtotime($date));
    }

    /**
     * Schneidet einen String ab
     * @param string $string der ab zu schneidende String
     * @param int $width (Optional, standart 14) wie lang soll der String maximal sein
     * @param int $shortBy (Optional, standart 14) Ab welchem Charackter soll der String abgeschnitten werden
     * @return string der abgeschnittene String
     */
    public static function shortString(string $string, int $width = 14, int $shortBy = 14): string {
        if (strlen($string) <= $width) {
            return $string;
        } else {
            $shortString = substr($string, 0, $shortBy) . '....';
            return $shortString;
        }
    }

    /**
     * Entfernt alle Nichtalphabetische- und nichtnumerische Charackter aus einem String
     * @param string $string der zu ändernde String
     * @return string der modifizierte String
     */
    public static function cleanString(string $string): string {
        return preg_replace("/[^a-zA-Z0-9]/", "", $string);
    }

    /**
     * Konvertiert neue Zeilen Encodes von HTML zu INPUT und umgekehrt
     * @param string $string der Text zum Konvertieren
     * @param bool $forHTML Input zu HTML (true) | HTML zu Input (false)
     * @return string der korrekt formatierte Text
    */
    public static function newLineCode(string $string, bool $forHtml = true): string {
        if ($forHtml) {
            return str_replace("\r\n", "<br>", $string);
        }

        return str_replace("<br>", "\r\n", $string);
    }
}

class GBAPI {
    /**
     * @internal only
     * @uses internal Framewok
     */
    private static function bro(): mixed {
        return [
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ],    
        ];
    }

    private static function getToken(): mixed {
        $url = Vars::greenbucket_mail_api_url();

        $header = [
            "x-auth-key" => Vars::greenbucket_mail_api_key()
        ];

        $ch = curl_init();

        $formattedHeaders = [];

        foreach ($header as $key => $value) {
            $formattedHeaders[] = "$key: $value";
        }

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_POST, 1);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            echo("SEND Error: $error <br>");
            return false;
        } else {
            return json_decode($response);
        }
    }

    /**
     * Funktion um eine E-Mail zu versenden anhand der greenbucket E-Mail API
     * @param string $to E-Mail Adresse des Empfängers
     * @param string $subject Betreff der E-Mail
     * @param string $content Inhalt der E-Mail (HTML)
     * @return bool true wenn die E-Mail Problemlos versendet wurde
    */
    public static function sendMail(string $to, string $subject, string $title, string $content): bool {
        $url = Vars::greenbucket_mail_api_url();

        $header = [
            "Content-Type" => "application/json",
            "x-auth-key" => Vars::greenbucket_mail_api_key(),
            "token" => self::getToken()[0]->token
        ];

        $body = [
            "to" => $to,
            "subject" => $subject,
            "title" => $title,
            "content" => $content
        ];

        $ch = curl_init();
        $formattedHeaders = [];

        foreach ($header as $key => $value) {
            $formattedHeaders[] = "$key: $value";
        }

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            echo("SEND Error: $error <br>");
            return false;
        } else {
            return true;
            // return $response;
        }
    }

    /**
     * greenbucket API Fetchen
     * @param mixed $params parameter für den API Fetch (@example ["use" => "id", "id" => 4])
     * @return mixed API Antwort
     */
    public static function fetch(mixed $params): mixed {
        $context = stream_context_create(self::bro());

        $url = Vars::greenbucket_api_url();
        $url .= '?key=' . Vars::greenbucket_api_key();
        $url .= '&' . http_build_query($params);
        
        $result = file_get_contents($url, false, $context);

        return json_decode($result, true);
    }
}

class Api {
    /**
     * Ruft Daten von einer API ab
     * @param string $url Die URL der API
     * @param array $headers Optional: Ein assoziatives Array von Headerinformationen
     * @param mixed $body Optional: Die Daten, die im Request-Body gesendet werden sollen
     * @return mixed Die Daten von der API als Array oder Objekt, oder false im Fehlerfall
     */
    public static function fetch($url, $headers = [], $body = null): mixed {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            echo("API Fetch Error: $error");
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    public static function sendMail(string $toName, string $toMail, string $fromName, string $fromMail, string $subject, string $msg): bool {
        $curl = curl_init();

        curl_setopt_array(
            $curl, 
            array(
                CURLOPT_URL => 'https://greenbucket.haugga.de/gbdb/mail/index.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
                    "to_name": "' . $toName . '",
                    "to_email": "' . $toMail .'",
                    "from_name": "' . $fromName . '",
                    "from_email": "' . $fromMail . '",
                    "subject": "' . $subject . '",
                    "mail_content": "' . $msg . '"
                }
                ',
                CURLOPT_HTTPHEADER => array(
                    'test: aaa',
                    'key: 63b773e1983ab3a64b2b088660019bb749078b4fe25bc4718636ec14543a1ccb',
                    'Content-Type: application/json'
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        if ($response == "ok") {
            return true;
        } else {
            return false;
        }
    }
}

class Hash {
    /**
     * Hasht ein Passwort Regelkonform
     * @param string $password das zu hashende Passwort
     * @return string das gehashte Passwort
     */
    public static function hashpassword(string $paassword): string {
        return hash('sha256', hash('sha512', $paassword));
    }

    /**
     * Erstellt einen SHA256 hash aus einem String
     * @param string $string der zu hashende String
     * @return string der gehashte String
     */
    public static function sha256(string $string): string {
        return hash('sha256', $string);
    }

    /**
     * Erstellt einen SHA512 hash aus einem String
     * @param string $string der zu hashende String
     * @return string der gehashte String
     */
    public static function sha512(string $string): string {
        return hash('sha512', $string);
    }

    /**
     * Erstellt einen adler32 hash aus einem String
     * @param string $string der zu hashende String
     * @return string der gehashte String
     */
    public static function adler32(string $string): string {
        return hash('adler32', $string);
    }

    /**
     * Erstellt einen md5 hash aus einem String
     * @uses not recommanded
     * @param string $string der zu hashende String
     * @return string der gehashte String
     */
    public static function md5(string $string): string {
        return hash('md5', $string);
    }

    /**
     * Erstellt einen verwurzelten Hash aus einem String
     * @param string $string der zu hashende String
     * @return string der gehashte String
     */
    public static function multiHash(string $string): string {
        $a = hash('sha256', $string);
        $b = hash('adler32', $a);
        $c = hash('md5', $b);
        $d = hash('sha512', $c);
        $e = hash('sha256', $d);

        return hash('sha512', $e);
    }
}

class SQL {
    /**
     * @var any $pdo PDO SQL Connection
     */
    public static $pdo;

    /**
     * Stellt die Verbindung zum SQL Server her
     * @return bool true wenn es keine Probleme gab
     */
    public static function connect(): bool {
        if (Vars::__DEV__()) {
            $dsn = "mysql:host=" . Vars::sql_dev_server();
            $dsn .= ";dbname=" . Vars::sql_dev_database();
            $u = Vars::sql_dev_user();
            $p = Vars::sql_dev_password();
        } else {
            $dsn = "mysql:host=" . Vars::sql_server();
            $dsn .= ";dbname=" . Vars::sql_database();
            $u = Vars::sql_user();
            $p = Vars::sql_password();
        }

        try {
            self::$pdo = new PDO($dsn, $u, $p);
            return true;
        } catch (PDOException $e) {
            echo "Error when connecting to SQL database: " . $e;
            return false;
        }
    }

    /**
     * Sendet eine SQL Abfrage an den SQL Server
     * @param string $query die zu sendene SQL Abfrage
     * @return mixed die Antwort des SQL Servers / Das Ergebnis der SQL Abfrage
     */
    public static function sendSQL(string $query): mixed {
        $ergebnis = self::$pdo->query($query);
    
        if ($ergebnis) {
            return $ergebnis->fetchAll(PDO::FETCH_ASSOC);
        }
    
        return false;
    }

    /**
     * Einfacher Select Befehl
     * @param string $table Name der Tabelle
     * @param string $select Was sie Selectieren wollen (Optional, Standard: *)
     * @param string $where (Optional)
     * @param string $is (Optional, @example $where = "name" $is = "Max")
     * @return mixed Ergebnis der SELECT Abfrage
     */
    public static function select(string $table, string $select = "*", string $where = "", string $is = ""): mixed {
        if ($where != "") {
            $query = "SELECT $select FROM $table WHERE $where = $is";
        } else {
            $query = "SELECT $select FROM $table";
        }

        return self::sendSQL($query);
    }

    /**
     * Einfacher Insert-Befehl
     * @param string $table Name der Tabelle
     * @param array $data Daten zum Einfügen (assoziatives Array)
     * @return mixed Ergebnis des Insert-Befehls
     */
    public static function insert(string $table, array $data): mixed {
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";
        $query = "INSERT INTO $table ($columns) VALUES ($values)";

        return self::sendSQL($query);
    }

    /**
     * Einfacher Update-Befehl
     * @param string $table Name der Tabelle
     * @param array $data Neue Daten (assoziatives Array)
     * @param string $where Spalte für die Bedingung
     * @param mixed $is Wert für die Bedingung
     * @return mixed Ergebnis des Update-Befehls
     */
    public static function update(string $table, array $data, string $where, mixed $is): mixed {
        $set = '';

        foreach ($data as $column => $value) {
            $set .= "$column = '$value', ";
        }

        $set = rtrim($set, ', ');
        $query = "UPDATE $table SET $set WHERE $where = '$is'";
        
        return self::sendSQL($query);
    }

    /**
     * Einfacher Delete-Befehl
     * @param string $table Name der Tabelle
     * @param string $where Spalte für die Bedingung
     * @param mixed $is Wert für die Bedingung
     * @return mixed Ergebnis des Delete-Befehls
     */
    public static function delete(string $table, string $where, mixed $is): mixed {
        $query = "DELETE FROM $table WHERE $where = '$is'";
        return self::sendSQL($query);
    }
}

class Converter {
    /**
     * Addiert zwei Kommazahlen
     * @param int|float $p Kommazahl
     * @param int|float $a Multiplikator (@example Summe = $a * $p)
     * @return int|float Die Summe $a * $p
     */
    public static function getSumme(int|float $p, int|float $a): int|float {
        $tmp1 = str_replace(',', '.', $p);
        $tmp2 = floatval($tmp1);
        $tmp3 = $a * $tmp2;
        $tmp4 = strval($tmp3);
    
        $e = str_replace('.', ',', $tmp4);
    
        if (!is_int($tmp3)) {
            $e = number_format($tmp3, 2, ',', '');
        }
    
        return $e;
    }

    /**
     * Konvertiert eine Kommazahl zu einer Ganzzahl (Keine Aufrundung)
     * @param int|float $x Die zu Konvertieredne Kommazahl
     * @return int die Ganzzahl
     */
    public static function convertToNumber(int|float $x): int {
        $x = str_replace(',', '.', $x);
        $x = floatval($x);
    
        return $x;
    }
}

class Time {
    /**
     * Gibt wieder, wie lange ein Datum mit/oder Uhrzeit her ist
     * @param mixed $timestamp der TimeStamp
     * @return string Ausgabe wielange es her ist
     */
    public static function timeAgo(mixed $timestamp): string {
        $currentTime = time();
        $uploadedTime = strtotime($timestamp);
    
        $timeDifference = $currentTime - $uploadedTime;
    
        $seconds = $timeDifference;
        $minutes = round($seconds / 60);
        $hours   = round($seconds / 3600);
        $days    = round($seconds / 86400);
        $weeks   = round($seconds / 604800);
        $months  = round($seconds / 2629440);
        $years   = round($seconds / 31553280);
    
        if ($seconds <= 60) {
            return "vor $seconds Sekunden";
        } elseif ($minutes <= 60) {
            if ($minutes == 1) {
                return "vor einer Minute";
            } else {
                return "vor $minutes Minuten";
            }
        } elseif ($hours <= 24) {
            if ($hours == 1) {
                return "vor einer Stunde";
            } else {
                return "vor $hours Stunden";
            }
        } elseif ($days <= 7) {
            if ($days == 1) {
                return "vor einem Tag";
            } else {
                return "vor $days Tagen";
            }
        } elseif ($weeks <= 4.3) {  // 4.3 == 30/7
            if ($weeks == 1) {
                return "vor einer Woche";
            } else {
                return "vor $weeks Wochen";
            }
        } elseif ($months <= 12) {
            if ($months == 1) {
                return "vor einem Monat";
            } else {
                return "vor $months Monaten";
            }
        } else {
            if ($years == 1) {
                return "vor einem Jahr";
            } else {
                return "vor $years Jahren";
            }
        }
    }
}

class Cookie {
    private const DUR = 60 * 60 * 24 * 360;

    /**
     * Setzt ein Cookie
     * @param string $name Name des Cookies
     * @param string $value Inhalt des Cookies
     * @param int $expiration (Optional, Standard @var DUR ) Haltbarkeit des Cookies
     */
    public static function set(string $name, string $value, int $expiration = self::DUR): void {
        setcookie($name, $value, time() + $expiration, "/", "", false);
    }

    /**
     * Setzt ein Sicheres und HTTPonly Cookie
     * @param string $name Name des Cookies
     * @param string $value Inhalt des Cookies
     * @param int $expiration (Optional, Standard @var DUR ) Haltbarkeit des Cookies
     */
    public static function setSecure(string $name, string $value): void {
        self::set($name, $value);
    }

    /**
     * Fügt ein neuen Cookie hinzu
     * @param string $name Name des Cookies
     * @param string $data inhalt des Cookies
     */
    public static function add(string $name, string $data): void {
        if (!isset($_COOKIE[$name])) {
            self::set($name, $data);
        }
    }

    /**
     * Ruft den Inhalt eines Cookies ab
     * @param string $name Name des Cookies
     * @return mixed Inhalt des Cookies
     */
    public static function get(string $name): mixed {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Löscht ein Cookie
     * @param string $name Name des zu löschenden Cookies
     */
    public static function delete(string $name): void {
        self::set($name, "", (0-3600));
    }

    /**
     * Bearbeitet ein Cookie
     * @param string $name Name des zu bearbeitenden Cookies
     * @param string $value neuer Inhalt des Cookies
     */
    public static function edit(string $name, string $value): void {
        self::delete($name);
        self::set($name, $value);
    }

    /**
     * Vergleicht ein Cookie mit etwas
     * @param string $name Name des Cookies
     * @param string $value Mit was der Cookie verglichen werden soll
     * @return bool true wenn Gleich
     */
    public static function compare(string $name, string $value): bool {
        return self::get($name) === $value;
    }

    /**
     * Aktuallisiert die Cookies im Browser
     */
    public static function refresh(): void {
        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $name => $data) {
                self::edit($name, $data);
            }
        }
    }

    /**
     * initialisierung der initialcookies
     * @internal used by Framework
     */
    public static function init(): void {
        foreach (vars::init_cookies() as $i => $r) {
            self::add($r["cookie_name"], $r["cookie_value"]);
            self::refresh();
        }
    }

    /**
     * Schaut, ob ein Cookie existiert
     * @param string $name Name des Cookies
     * @return bool true wenn Existiert
    */
    public static function exists(string $name): bool {
        if (isset($_COOKIE[$name])) {
            return true;
        }

        return false;
    }
}

class Session {
    /**
     * Erneuert die Session
     * @internal used by Framework
     */
    public static function renew_session(): void {
        session_abort();
        ini_set('session.gc_maxlifetime', 0);
        
        $days = 360;
        $lifetime = $days * 24 * 60 * 60;
        
        session_set_cookie_params($lifetime);
        session_cache_expire($days);
        session_start();

        if (isset($_SESSION['created'])) {
            $renewThreshold = 30 * 24 * 60 * 60;

            if (time() - $_SESSION['created'] > $lifetime - $renewThreshold) {
                $_SESSION['created'] = time();
                session_regenerate_id(true);
            }
        } else {
            $_SESSION['created'] = time();
        }
    }

    /**
     * Behandeln der Session
     * @internal used by Framework
     */
    public static function handler(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::renew_session();
        }

        if (session_status() === PHP_SESSION_NONE) {
            self::renew_session();
        }
    }

    /**
     * initialisierung der initialen Session Variablen
     * @internal used by Framework
     */
    public static function init(): void {
        foreach (Vars::init_session() as $i => $r) {
            $_SESSION[$r["session_name"]] = $r["session_value"];
        }
    }

    /**
     * Gibt den Inhalt einer Session Variable zurück
     * @param string $name Name der Session Variable
     * @return mixed INhalt der Session Variable
     */
    public static function get(string $name): mixed {
        return $_SESSION[$name];
    }

    /**
     * Erstellt oder bearbeitet eine Session Variable
     * @param string $name Name der Session Variable
     * @param mixed $value Inhalt der Session Variable
     */
    public static function add_or_edit(string $name, mixed $value): void {
        $_SESSION[$name] = $value;
    }

    /**
     * Löscht eine Session Variable
     * @param string $name Name der zu löschenden Session Variable
     */
    public static function delete(string $name): void {
        $_SESSION[$name] = null;
        
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }

    public static function exists(string $name): bool {
        if (isset($_SESSION[$name])) {
            return true;
        }

        return false;
    }
}

class Json {
    /**
     * Dekodiert einen JSON-String in ein PHP-Array oder Objekt
     * @param string $json Der zu dekodierende JSON-String
     * @param bool $assoc Gibt an, ob das zurückgegebene Objekt ein assoziatives Array sein soll oder nicht
     * @return mixed Das dekodierte JSON als Array oder Objekt
     */
    public static function decode($json, $assoc = false) {
        return json_decode($json, $assoc);
    }

    /**
     * Kodiert ein PHP-Array oder Objekt in einen JSON-String
     * @param mixed $data Das zu kodierende Array oder Objekt
     * @return string Der JSON-String
     */
    public static function encode($data) {
        return json_encode($data);
    }

    /**
     * Überprüft, ob eine Zeichenkette ein gültiges JSON ist
     * @param string $json Die zu überprüfende Zeichenkette
     * @return bool Gibt zurück, ob die Zeichenkette ein gültiges JSON ist (true) oder nicht (false)
     */
    public static function isJson($json) {
        json_decode($json);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Iteriert über jedes Element eines Arrays oder Objekts und wendet eine Callback-Funktion darauf an
     * @param mixed $data Das Array oder Objekt, über das iteriert werden soll
     * @param callable $callback Die Callback-Funktion, die auf jedes Element angewendet werden soll
     * @return mixed Das modifizierte Array oder Objekt
     */
    public static function loop($data, $callback) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = call_user_func($callback, $value, $key);
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = call_user_func($callback, $value, $key);
            }
        }
        
        return $data;
    }

    /**
     * Überprüft, ob ein bestimmtes Element in einem JSON-Array oder Objekt existiert
     * @param mixed $data Das JSON-Array oder -Objekt
     * @param string $key Der Schlüssel des zu überprüfenden Elements
     * @return bool Gibt zurück, ob das Element existiert (true) oder nicht (false)
     */
    public static function elementExists($data, $key) {
        if (is_array($data)) {
            return array_key_exists($key, $data);
        } elseif (is_object($data)) {
            return property_exists($data, $key);
        }

        return false;
    }

    /**
     * Ruft die Daten eines bestimmten Elements aus einem JSON-Array oder -Objekt ab, falls es existiert
     * @param mixed $data Das JSON-Array oder -Objekt
     * @param string $key Der Schlüssel des Elements
     * @return mixed Die Daten des Elements, falls vorhanden, ansonsten null
     */
    public static function getElement($data, $key) {
        if (self::elementExists($data, $key)) {
            if (is_array($data)) {
                return $data[$key];
            } elseif (is_object($data)) {
                return $data->$key;
            }
        }

        return null;
    }
}

class GBDB {
    /**
     * Erstellt den Path zu der Datenabnk / Tabelle
     * @internal Used by Framework
     */
    private static function makePath(string $database, string $table): string {
        $table = Format::cleanString($table);
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $table = Crypt::encode($table);
            $database = Crypt::encode($database);
        }

        $table .= Vars::data_extension();
        $database = Vars::DB_PATH() . $database . "/";
        
        return $database . $table;
    }

    /**
     * Generiert die ID für einen nächsten Eintrag
     * @internal used by Framework
     */
    private static function genID(string $file): int {
        $database = self::ini($file);

        $id = 0;

        foreach ($database as $i => $r) {
            $id = $r["id"] + 1;
        }

        return $id;
    }

    /**
     * Stellt den Inhalt einer Tabelle für PHP zur Verfügung
     * @internal used by Framework
     */
    private static function ini(string $file): mixed {
        $db = [];
        $tmp = file_get_contents($file, true);
        $db = json_decode($tmp, true);

        if (Vars::crypt_data()) {
            $db = json_decode(Crypt::decode($tmp), true);
        }

        return $db;
    }

    /**
     * Erstellt eine GBDB Datenbank
     * @param string $name Name der Datenank (Alles was kein Buchstabe und keine Zahl ist, wird ignoriert)
     * @return bool true wenn es keine Probleme gab
     */
    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if (Vars::crypt_data()) {
            $name = Crypt::encode($name);
        }

        if (!is_dir(Vars::DB_PATH())) {
            mkdir(Vars::DB_PATH(), 0777);
        }

        if (!is_dir(Vars::DB_PATH() . $name)) {
            mkdir(Vars::DB_PATH() . $name, 0777);
            return true;
        }

        return false;
    }

    /**
     * Löscht eine GBDB Datenbank (nur wenn diese leer ist)
     * @param string $name name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if (Vars::crypt_data()) {
            $name = Crypt::encode($name);
        }

        if (is_dir(Vars::DB_PATH() . $name)) {
            rmdir(Vars::DB_PATH() . $name);
            return true;
        }

        return false;
    }

    /**
     * Erstelt eine DBDB Tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table Name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param array $cols Namen der Spalten (@example ["name", "notiz"])
     * @return bool true wenn es keine Probleme gab
     */
    public static function createTable(string $database, string $table, array $cols): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            $columns = '[{"id": -1, ';
            $n = count($cols);
            $i = 0;

            foreach ($cols as $col) {
                $columns .= '"' . $col . '": "-header-", ';
            }

            $columns = rtrim($columns, ', ');
            $columns .= '}]';

            if (Vars::crypt_data()) {
                $columns = Crypt::encode($columns);
            } else {
                $columns = json_encode(json_decode($columns), Vars::jpretty());
            }

            file_put_contents($file, $columns);

            return true;
        }

        return false;
    }
    
    /**
     * Löscht eine GBDB tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);

        if (file_exists($file)) {
            unlink($file);
            return true;
        }

        return false;
    }

    /**
     * Fügt Daten in eine GBDB tabelle in einer GBDB Datenbank ein
     * @param string $database name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $data Daten die eingefügt werden sollen (@example ["name" => "Max Mustermann", "notiz" => "Testeintrag"])
     * @return bool true wenn es keine Probleme gab
     */
    public static function insertData(string $database, string $table, mixed $data): bool {
        $file = self::makePath($database, $table);
    
        if (file_exists($file)) {
            $table_data = json_decode(file_get_contents($file), true);
    
            if (Vars::crypt_data()) {
                $table_data = json_decode(Crypt::decode(file_get_contents($file)), true);
            }
    
            if (empty($table_data)) {
                foreach ($data as $key => $value) {
                    $table_data[0][$key] = null;
                }
            }
    
            if (!isset($data['id'])) {
                $data['id'] = self::genID($file);
            }
    
            if (count($data) !== count($table_data[0])) {
                return false;
            }
    
            $new_row = [];

            foreach ($table_data[0] as $col => $value) {
                $new_row[$col] = isset($data[$col]) ? $data[$col] : $value;
            }
    
            $table_data[] = $new_row;

            if (Vars::crypt_data()) {
                $new_data_json = Crypt::encode(json_encode($table_data));
            } else {
                $new_data_json = json_encode($table_data, Vars::jpretty());
            }

            file_put_contents($file, $new_data_json);
    
            return true;
        }
    
        return false;
    }    

    /**
     * Entfernt Daten aus einer GBDB Tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteData(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            // $where = Crypt::encode($where);
            // $is = Crypt::encode($is);
        }

        $return = false; 

        foreach ($db as $i => $r) {
            if ($r[$where] == $is) {
                unset($db[$i]);
                $return = true;
            }
        }

        $db = array_values($db);

        if ($return) {
            if (Vars::crypt_data()) {
                file_put_contents($file, Crypt::encode(json_encode($db, Vars::jpretty())));
            } else {
                file_put_contents($file, json_encode($db, Vars::jpretty()));
            }
        }

        return $return;
    }

    /**
     * Bearbeitet Daten aus einer GBDB Tabelle in einer GBDB Datenbank
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @param mixed $newData Neue Daten (@example ["Henry Henryson"])
     * @return bool true wenn es keine Probleme gab
     */
    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData): bool {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            // $where = Crypt::encode($where);
            // $is = Crypt::encode($is);
        }

        $return = false;

        foreach ($db as $i => $r) {
            if ($r[$where] == $is) {
                foreach ($newData as $col => $value) {
                    if (array_key_exists($col, $db[$i])) {
                        $db[$i][$col] = $value;
                    }
                }

                $return = true;
            }
        }

        if ($return) {
            if (Vars::crypt_data()) {
                file_put_contents($file, Crypt::encode(json_encode($db, Vars::jpretty())));
            } else {
                file_put_contents($file, json_encode($db, Vars::jpretty()));
            }
        }

        return $return;
    }

    /**
     * Stellt alle Daten aus einer GBDB tabelle bereit
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param bool $filter (Optional, Standard: false) Soll gefiltert werden?
     * @param mixed $where (Optional) In welcher Spalte.... 
     * @param mixed $is (Optional) .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @return mixed Daten aus der Tabelle
     */
    public static function getData(string $database, string $table, bool $filter = false, mixed $where = "", mixed $is = ""): mixed {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            if ($filter) {
                // $where = Crypt::encode($where);
                // $is = Crypt::encode($is);
            }
        }

        if ($filter) {
            foreach ($db as $i => $r) {
                if ($r[$where] == $is) {
                    return $db[$i];
                }
            }

            return [];
        } else {
            unset($db[0]);
            $db = array_values($db);
        }

        return $db;
    }

    /**
     * Überprüft, ob ein Element in einer GBDB Tabelle vorhanden ist
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @return bool true, wenn das Element vorhanden ist
     */
    public static function elementExists(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);
        $db = self::ini($file);

        if (Vars::crypt_data()) {
            // $where = Crypt::encode($where);
            // $is = Crypt::encode($is);
        }

        foreach ($db as $i => $r) {
            if ($r[$where] == $is) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gibt alle Datenbanken zurück die existieren
     * @return array Datenbanken, String Array
     */
    public static function listDBs(): array {
        $d = Vars::DB_PATH();
        $dirs = [];

        $tmp = array_filter(scandir($d), function ($f) use($d) {
            return is_dir($d . $f);
        });

        for ($i = 0; $i < count($tmp); $i++) {
            if ($tmp[$i] != "." && $tmp[$i] != "..") {
                $db_name = $tmp[$i];

                if (Vars::crypt_data()) {
                    $db_name = Crypt::decode($db_name);
                }

                array_push($dirs, $db_name);
            }
        }

        return $dirs;
    }

    /**
     * Gibt alle Tabellen aus einer Datenbank zurück, die existieren
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param bool $descending (Optional, Standart: false) Soll DESCENDING Sortierung verwendet werden?
     * @return array Tabellen, String Array
     */
    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);

        if (Vars::crypt_data()) {
            $database = Crypt::encode($database);
        }

        $database = Vars::DB_PATH() . $database . "/";
        $tables = [];
        $desc = 0;

        if ($descending) {
            $desc = 1;
        }

        $tmp = scandir($database, $desc);
        
        for ($i = 0; $i < count($tmp); $i++) {
            if ($tmp[$i] != "." && $tmp[$i] != "..") {
                $table_name = str_replace(Vars::data_extension(), "", $tmp[$i]);

                if (Vars::crypt_data()) {
                    $table_name = Crypt::decode($table_name);
                }

                array_push($tables, $table_name);
            }
        }

        return $tables;
    }

    /**
     * Überprüft ob ein- oder zwei Values jewailig des Operatores zueinandner in einem Datensatz vorhanden sind
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @todo Dokumentation vervollständigen
     * 
    */
    public static function inDB2(string $database, string $table, mixed $value1, string $operator, mixed $value2): bool {
        $file = self::makePath($database, $table);
        $jsonContent = file_get_contents($file);
        $db = json_decode($jsonContent, true);

        if (Vars::crypt_data()) {
            $db = json_decode(Crypt::decode($jsonContent), true);
            // $value1 = Crypt::encode($value1);
            // $value2 = Crypt::encode($value2);
        }

        foreach ($db as $r) {
            $foundValue1 = false;
            $foundValue2 = false;

            foreach ($r as $key => $value) {
                if ($value == $value1) {
                    $foundValue1 = true;
                }

                if ($value == $value2) {
                    $foundValue2 = true;
                }
            }

            if ($operator == 'AND') {
                if ($foundValue1 && $foundValue2) {
                    return true;
                }
            } else if ($operator == 'OR') {
                if ($foundValue1 || $foundValue2) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Löscht eine Datenbank inklusive aller Tabellen darin
     * @param string $database Name der Datenbank
     * @return true wenn es keine Probleme gab
    */
    public static function deleteAll(string $database): bool {
        $ok = true;
        $tables = self::listTables($database);

        for ($i = 0; $i < count($tables); $i++) {
            if (!self::deleteTable($database, $tables[$i])) {
                $ok = false;
                break;
            }
        }

        if (!self::deleteDatabase($database)) {
            $ok = false;
        }

        return $ok;
    }

    /**
     * Gibt die ID, welche für den nächsten Datensatz vorgehesehn ist
     * @param string $database Datenbank
     * @param string $table Tabelle
     * @return int Die Vorgesehene ID
     */
    public static function nextID(string $database, string $table): int {
        $file = self::makePath($database, $table);
    
        if (file_exists($file)) {
            return self::genID($file);
        }
    
        return 0;
    }
}

class Crypt {
    private const METHOD = 'aes-256-cbc';
    private const SECRET_IV = '1234567891011121';

    /**
     * Verschlüsselt Daten basierend auf einem Schlüsselwort
     * @param string $data Daten die verschlüsselt werden sollen
     * @return string Verschlüsselter Datenstring
     */
    public static function encode(string $data): string {
        $key = hash('sha256', Vars::cryptKey(), true); // Binary format
        $iv = substr(hash('sha256', self::SECRET_IV, true), 0, 16); // Binary format
        $encrypted = openssl_encrypt($data, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
        $base64 = base64_encode($encrypted);

        return str_replace(['+', '/', '='], ['-', '_', ''], $base64); // URL-safe Base64
    }

    public static function decode(string $data): string {
        $key = hash('sha256', Vars::cryptKey(), true); // Binary format
        $iv = substr(hash('sha256', self::SECRET_IV, true), 0, 16); // Binary format
        $base64 = str_replace(['-', '_'], ['+', '/'], $data); // URL-safe Base64 to standard Base64
        $encrypted = base64_decode($base64);

        return openssl_decrypt($encrypted, self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
    }
}

class Tools {
    /**
     * Generiert ein Passwort
     * 
     * @param int $length Länge des Passwort als Integer
     * @return string Das generierte Passwort
     */
    public static function generatePassword(int $length): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}|:<>?-=[];,./';
        $password = '';
    
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
    
        return $password;
    }

    /**
     * Testet eine Passwort Stärke
     * 
     * @param string $password Das zu testende Passwort
     * @return string Angabe welche Schwäsche auf zu weisen ist
     */
    public static function testPasswordStrength(string $password): string {
        if (strlen($password) < 8) {
            return 'It would be good, if the password would have 8 charackters or more.';
        }
    
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            return 'It would be good, to add camlcase characters.';
        }
    
        if (!preg_match('/\d/', $password)) {
            return 'It would be good, if the password would have one or more numbers.';
        }
    
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'It would be good, if the password would contain a non-alphabetic charackter.';
        }
    
        return 'This password is strong.';
    }

    /**
     * Findes WHOIS Daten über eine Domain heraus
     * 
     * @param string $domain Domain
     * @return mixed Domain Daten
     */
    public static function getDomainInfo(string $domain): mixed {
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            $whois = shell_exec("whois $domain");
            return json_encode(array("success" => $whois));
        } else {
            return json_encode(array("error" => "That domain does not exist."));
        }
    }

    /**
     * Generiert eine ID
     * 
     * @return int ID
     */
    public static function generateId(): int {
        $tmpFile = "../../" . Vars::json_path() . 'framework_temp/_id.txt';

        if (!file_exists($tmpFile)) {
            file_put_contents($tmpFile, '');
        }

        $use_id = file($tmpFile);
        $id = 0;

        foreach ($use_id as $n) {
            $id = $n + 1;
        }

        file_put_contents($tmpFile, $id . "\n", FILE_APPEND);

        return $id;
    }

    /**
     * Generiert einen Token
     * 
     * @param string $delimiter (OPTIONAL) Trennzeichen zwischen den Tokenfragmenten (STANDARD: -)
     * @param int $many (OPTIONAL) Anzahl der Token die generiert werden sollen (STANDARD: 1)
     * @param int $fragments (OPTIONAL) Anzahl der Tokenfragmente (STANDARD: 1)
     * @return array Generierte Tokens
     */
    public static function generateToken(string $delimiter = "-", int $many = 1, int $fragments = 4): array {
        $tmpFile = "../../" .  Vars::json_path() . 'framework_temp/_tokens.txt';
        $token_array = [];
        $tokens = [];
        $xn = 0;
    
        if (!file_exists($tmpFile)) {
            file_put_contents($tmpFile, '');
        }
    
        for ($i = 0; $i < $many; $i++) {
            $token = "";
    
            for ($j = 0; $j < $fragments; $j++) {
                $token_array[$j] = hash('adler32', rand(0, 4096));
                $token .= $token_array[$j] . $delimiter;
            }
    
            $token = rtrim($token, $delimiter);
            $use_token = file($tmpFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $result = $token;
            $write = true;
    
            foreach ($use_token as $n) {
                if (trim($n) === $token) {
                    $result = "T_A_E";
                    $write = false;

                    break;
                }
            }
    
            if ($write) {
                file_put_contents($tmpFile, $result . "\n", FILE_APPEND);

                $tokens[] = $result;
                $xn++;
            } else {
                $i--;
            }
        }
    
        return $tokens;
    }

    public static function generateTokenExt(string $delimiter = "-", int $many = 1, int $fragments = 4): array {
        $tmpFile = Vars::json_path() . 'framework_temp/_tokens.txt';
        $token_array = [];
        $tokens = [];
        $xn = 0;
    
        if (!file_exists($tmpFile)) {
            file_put_contents($tmpFile, '');
        }
    
        for ($i = 0; $i < $many; $i++) {
            $token = "";
    
            for ($j = 0; $j < $fragments; $j++) {
                $token_array[$j] = hash('adler32', rand(0, 4096));
                $token .= $token_array[$j] . $delimiter;
            }
    
            $token = rtrim($token, $delimiter);
            $use_token = file($tmpFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $result = $token;
            $write = true;
    
            foreach ($use_token as $n) {
                if (trim($n) === $token) {
                    $result = "T_A_E";
                    $write = false;

                    break;
                }
            }
    
            if ($write) {
                file_put_contents($tmpFile, $result . "\n", FILE_APPEND);

                $tokens[] = $result;
                $xn++;
            } else {
                $i--;
            }
        }
    
        return $tokens;
    }
    
    /**
     * Findet heraus, aus welchem Land eine IP stammt
     * 
     * @param string $ip IP Adresse
     * @return mixed IP Land
     */
    public static function getIpCountry(string $ip): string {
        $url = 'https://api.country.is/' . $ip;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($content);
        $out = $json->country;

        if (!$out || $out == null || $out == "") {
            $out = "Invalid IP.";
        }

        return $out;
    }

    /**
     * Prüft die Verbindung zu einer IPv4 Adresse
     * 
     * @param string $ip IPv4 Adresse
     * @return string Erreichbarkeitsstatus
     */
    public static function ping4(string $ip): string {
        exec("ping " . $ip, $output, $status);

        if ($status === 0) {
            $out = $ip . " erreichbar.";
        } else {
            $out = $ip . " nicht erreichbar!";
        }

        return $out;
    }

    /**
     * Prüft die Verbindung zu einer IPv6 Adresse
     * 
     * @param string $ip IPv6 Adresse
     * @return string Erreichbarkeitsstatus
     */
    public static function ping6(string $ip): string {
        exec("ping [" . $ip . "]", $output, $status);

        if ($status === 0) {
            $out = $ip . " erreichbar.";
        } else {
            $out = $ip . " nicht erreichbar!";
        }

        return $out;
    }

    /**
     * Erstellt einen QR Code
     * 
     * @param string $value Inhalt des QR Codes
     * @param int $width Width in PX
     * @param int $height Height in PX
     * @return string HTML um den QR Code an zu zeigen
     */
    public static function qr(string $value, int $width, int $height): string {
        $params = "?width=" . $width . "&height=" . $height . "&correctlevel=H";
        $params .= "&zielurl=" . urlencode($value);
        $style = "border: none; width: " . $width . "px; height: " . $height . "px;";
    
        return '<iframe style="' . $style . '" src="assets/tool_apis/qrcode.api.php' . $params . '"></iframe>';
    }    

    /**
     * Erstellt einen BAR Code
     * 
     * @param string $value Inhalt des BAR Codes
     * @param int $width Width in PX
     * @param int $height (OPTIONAL) Height in PX (STANDARD: 175)
     * @return string HTML um den BAR Code an zu zeigen
     */
    public static function bar(string $value, int $width, int $height = 175): string {
        $params = "?value=" . urlencode($value);
        $style = "border: none; width: " . $width . "px; height: " . $height . "px;";
    
        return '<iframe style="' . $style . '" src="assets/tool_apis/barcode.api.php' . $params . '"></iframe>';
    }    
}

class ReCaptcha {
    /**
     * @return string Name der ReCaptcha $_POST Checkbox
     */
    public static function postName(): string {
        return "g-recaptcha-response";
    }

    /**
     * Erstellt die reCAPTCHA checkbox
     * 
     * @param string $callbaclJs (OPTIONAL) Name einer JavaScript Funktion die ausgeführt werden soll, wenn die Checkbox gecheked wird
     * @return string Die HTML der ReCAPTCHA Checkbox
     */
    public static function checkBox(string $callbackJs = ""): string {
        $wc = Vars::reCaptcha_website_key();
        return '<div class="g-recaptcha" data-sitekey="' . $wc. '" data-callback="' . $callbackJs . '"></div>';
    }

    /**
     * Verifizierung von reCAPTCHA
     * 
     * @param mixed $post POST Variable von reCAPTCHA Checkbox ($_POST[ReCaptcha::postName()])
     * @return boolean TRUE wenn Verifizierung erfolgreich!
     */
    public static function verify(mixed $post): bool {
        if (!empty($post)) {
            $reCAPTCHA_secret_key = Vars::reCaptcha_secret_key();
            $reCAPTCHA_uri = "https://www.google.com/recaptcha/api/siteverify";
            $reCAPTCHA_param1 = "?secret=" . $reCAPTCHA_secret_key;
            $reCAPTCHA_param2 = "&response=". $post;
            $reCAPTCHA_param3 = "&remoteip=". Vars::client_ip();
            $reCAPTCHA_complete_uri = $reCAPTCHA_uri . $reCAPTCHA_param1 . $reCAPTCHA_param2 . $reCAPTCHA_param3;
            $reCAPTCHA_verify = Api::fetch($reCAPTCHA_complete_uri);
            
            return $reCAPTCHA_verify["success"];
        }
        
        return false;
    }
}

/**
 * @see WARNUNG!!!!!
 * 
 * Für die Verwendung von SecondServer ist ein höheres Verständnis zu Datenübertragungen
 * und der Funktionsweiße des GBDB-Backends nötig. Die Abfragen können schnell groß und komplex werden.
 * Wenn Sie ein eigenes Backend verwenden, empfehlen wir stattdessen die Verwendung der Api Klasse ab Zeile 330.
 * Wenn Sie das GBDB Backend verwenden, lesen Sie vor der Verwendung der SecondServer Unterstützung die Dokumentation
 * @link https://github.com/Troyer05/phpFramework
 * 
 * BEI VERWENDUNG DES GBDB Backends:
 * - Stellen Sie sicher, dass auf dem SecondServer Apache2 oder Nginx mit PHP8.1 laufen (Hierbei ist jeder Port verwendbar, solange Sie diesen in ENV.php angeben)
 * - Stellen Sie sicher, dass Sie in der ENV.php die Adresse (ggf. mit Port) angegeben haben
 * - Stellen Sie sicher, dass Sie die neueste Version des GBDB Backends auf dem SecondServer haben und dieses über die von Ihnen angegebene Adresse erreichbar ist
 * - Stellen Sie sicher, dass die version des GBDB-Backend mit der Version des GBDB-Frameworks kompatibel ist (siehe oberste Kommentare)
 * - Stellen Sie sicher, dass Sie die ENV.php des GBDB Backends entsprechend konfiguriert haben
 * 
 * Das GBDB Backend auf dem SecondServer verwendet für die Datenverarbeitung ebenso die GBDB Klasse.
 * Die Anfragen sind also sehr ähnlich aufgebaut.
 * 
 * Wie verwendet man SecondServer? 
 * 1. Kopieren Sie dieses FrameWork Projekt auf einen zweiten Server, welcher als SecondServer fungieren soll
 * 2. Konfigurieren Sie die Variablen in ENV.php
 * 3. Stellen Sie sicher, dass die api.php von Server1 aus erreichbar ist (Hierzu können Sie die Funktion testConnection() in der Srv Klasse nutzen)
 * 
 * Das wars. Wenn Sie auf Server1 und Server2 die ENV.php jewails korrekt konfiguriert haben und alle Anforderungen erfüllt haben, sollte es funktionieren.
 * 
 * Auf Server1 können Sie folgende Datei löschen: api.php
 * 
 * SecondServer achtet darauf, dass nur Authorisierte Zugriffe auf die API stattfinden können.
 * Zudem wird darauf geachtet, dass mit großen Datenmengen optimal umgegangen wird und damit die Server Ressourcen geschont werden.
 * SecondServer achtet darauf, dass keine Daten verloren gehen und Daten sicher sind. Der SecondServer muss keine Verbindung zur Ausenwelt haben, NUR zu Server1.
 * SecondServer ist zudem dazu konzipiert, Datenverluste zu minimieren und rein im Backend verwendet zu werden, damit die Daten vor NetworkSniffer sicher sind
 * SecondServer achtet zudem darauf, dass so wenig wie möglich Datenmüll zurück bleibt
 * 
 * Diese Klasse geht davon aus, dass Sie das GBDB Backend auf Ihrem SecondServer verwenden.
 * 
 * Beachten Sie, dass die Daten über HTTP Request Bodys gesendet werden. Je nach Datenmenge die Sie über SecondServer verarbeiten wollen, sollten Sie sicher stellen,
 * dass in Ihrer Apache2/Nginx Konfiguration das Body Request Limit entsprechend angepasst ist.
 * 
 * @todo Request Sending Query Liste
 * @todo Bei Datenverlust erneute Anfrage senden
 * @todo DELETE TABLE
 * @todo CREATE TABLE
 * @todo DELETE DATABASE
 * @todo CREATE DATABASE
 * 
 */

class Srv {
    /**
     * Sendet die Anfragen in Korrekter Form an das GBDB Backend
     * @internal used by FrameWork
    */
    private static function send(mixed $header, mixed $body): mixed {
        $ch = curl_init();

        $formattedHeaders = [];

        foreach ($header as $key => $value) {
            $formattedHeaders[] = "$key: $value";
        }

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_URL, Vars::second_server_uri() . 'api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            echo("SEND Error: $error <br>");
            return false;
        } else {
            return $response;
        }
    }

    /**
     * Fordert anhand des gemeinsamen API Keys einen Authentifizierungs Token an
     * @internal used by FrameWork
    */
    private static function getToken(): string {
        $body = [
            "request" => "get",
            "exp" => "token"
        ];
        
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "Content-Type" => "application/json"
        ];

        $res = self::send($header, $body);

        if ($res === false) {
            echo 'Error sending request to API<br>';
        }

        $res = json_decode($res, true);

        if (is_array($res) && isset($res[0]['token'])) {
            $token = $res[0]['token'];
            return $token;
        } else {
            echo 'Token not found in response<br>';
            exit;
        }
    }

    /**
     * Fordert Daten aus einer Tabelle auf dem SecondServer an
     * @param string $db Datenbank, in der sich die Tabelle befindet
     * @param string $table die Tabelle von der die Daten angefragt werden
     * @return mixed Daten aus der Tabelle (Normal als JSON)
    */
    public static function getData(string $db, string $table, bool $filter = false, mixed $where = "", mixed $is = ""): mixed {
        $body = [
            "request" => "get",
            "exp" => "data",
            "db" => $db,
            "table" => $table,
            "filter" => $filter,
            "where" => $where,
            "is" => $is
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        return json_decode(self::send($header, $body), true);
    }

    /**
     * Löscht eine Datenbank inklusive aller Tabellen darin
     * @param string $database Name der Datenbank
     * @return true wenn es keine Probleme gab
    */
    public static function deleteAll(string $database): mixed {
        $body = [
            "request" => "delete",
            "exp" => "bool",
            "type" => "all",
            "db" => $database
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        return self::send($header, $body);
    }

    /**
     * Gibt die Lesefertig formatierte Größe zurück die verbraucht wird
     * @return mixed Größe des verbrauchten Speicherplatzes
    */
    public static function getSize(): mixed {
        $body = [
            "request" => "size",
            "exp" => "size"
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        return self::send($header, $body);
    }

    /**
     * Überprüft ob ein- oder zwei Values jewailig des Operatores zueinandner in einem Datensatz vorhanden sind
     * @param string $database Name der Datenbank (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @param string $table name der Tabelle (Alles was kein Buchstabe und keine zahl ist, wird ignoriert)
     * @todo Dokumentation vervollständigen
     * 
    */
    public static function inDB2(string $database, string $table, mixed $value1, string $operator, mixed $value2): mixed {
        $body = [
            "request" => "get",
            "exp" => "bool",
            "db" => $database,
            "table" => $table,
            "val1" => $value1,
            "op" => $operator,
            "val2" => $value2
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        return self::send($header, $body);
    }

    /**
     * Fügt einen Datensatz in einer Tabelle hinzu
     * @param string $db Datenbank, in der sich die Tabelle befindet
     * @param string $table die Tabelle von der die Daten angefragt werden
     * @param array $daten Die Daten die hinzugefügt werden
     * @return bool true wenn keine Fehler aufgetreten sind
    */
    public static function addData(string $db, string $table, array $data): mixed {
        $body = [
            "request" => "put",
            "exp" => "bool",
            "db" => $db,
            "table" => $table,
            "data" => $data
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    /**
     * Löscht einen Datensatz aus einer Tabelle
     * @param string $db Datenbank, in der sich die Tabelle befindet
     * @param string $table die Tabelle von der die Daten angefragt werden
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @return bool true wenn keine Fehler aufgetreten sind
    */
    public static function deleteData(string $db, string $table, string $where, string $is): bool {
        $body = [
            "request" => "del",
            "exp" => "bool",
            "db" => $db,
            "table" => $table,
            "where" => $where,
            "is" => $is
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    /**
     * Bearbeitet einen Datensatz in einer Tabelle
     * @param string $db Datenbank, in der sich die Tabelle befindet
     * @param string $table die Tabelle von der die Daten angefragt werden
     * @param mixed $where In welcher Spalte.... 
     * @param mixed $is .... $is ist. (@example $where = "Name", $is = "Max Mustermann")
     * @param array $data die neuen Daten für den Datensatz
     * @return bool true wenn keine Fehler aufgetreten sind
    */
    public static function editData(string $db, string $table, string $where, string $is, array $data): bool {
        $body = [
            "request" => "update",
            "exp" => "bool",
            "db" => $db,
            "table" => $table,
            "where" => $where,
            "is" => $is,
            "data" => $data
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    /**
     * Erstellt eine Datenbank auf dem SecondServer
     * @param string $name Name der Datenbank
     * @return bool true wenn die Datenbank erfogreich erstellt wurde
    */
    public static function createDatabase(string $name): bool {
        $body = [
            "request" => "create",
            "exp" => "bool",
            "type" => "db",
            "name" => $name
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    /**
     * Erstellt eine Tabelle innerhalb einer Datenbank auf dem SecondServer
     * @param string $db Name der Datenbank in der die Tabelle erstellt werden soll
     * @param string $name Name der Tabelle
     * @param array $data Erstellungsdaten nach GBDB Form (Siehe GBDB Klasse für mehr Infos)
     * @return bool true wenn die Tabelle erfolgreich erstellt wurde
    */
    public static function createTable(string $db, string $name, array $data): bool {
        $body = [
            "request" => "create",
            "exp" => "bool",
            "type" => "table",
            "db" => $db,
            "name" => $name,
            "data" => $data
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    /**
     * Löscht eine Datenbank auf dem SecondServer (NUR WENN DB LEER!!)
     * @param string $name Name der zu löschenden Datenbank
     * @return bool true wenn die Datenbank erfolgreich gelöscht werden konnte
    */
    public static function deleteDatabase(string $name): bool {
        $body = [
            "request" => "delete",
            "exp" => "bool",
            "type" => "db",
            "name" => $name
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    /**
     * Löscht eine Tabelle in einer Datenbank auf dem SecondServer
     * @param string $db Name der Datenbank in der sich die zu löschende Tabelle befindet
     * @param string $name Name der zu löschenden Tabelle
     * @return bool true wenn die Tabelle erfolgreich gelöscht werden konnte
    */
    public static function deleteTable(string $db, string $name): bool {
        $body = [
            "request" => "delete",
            "exp" => "bool",
            "type" => "table",
            "db" => $db,
            "name" => $name
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    /**
     * Listet alle Datenbanken auf, die auf dem SecondServer liegen
     * @return mixed Liste der Datenbanken nach GBDB Form (Siehe GBDB Klasse für genauere Infos)
    */
    public static function listDBs(): mixed {
        $body = [
            "request" => "list",
            "exp" => "data",
            "type" => "db"
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        return json_decode(self::send($header, $body), true);
    }

    /**
     * Listet alle Tabellen innerhalb einer Datenbank von dem SecondServer auf
     * @param string $db Name der Datenbank
     * @param bool $desc Soll Sortiert aufgelistet werden?
     * @return mixed Liste der Tabellen nach GBDB Form (Siehe GBDB Klasse für genauere Infos)
    */
    public static function listTables(string $db, bool $desc): mixed {
        $body = [
            "request" => "list",
            "exp" => "data",
            "type" => "table",
            "db" => $db,
            "desc" => $desc
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        return json_decode(self::send($header, $body), true);
    }

    /**
     * Testet, ob die Verbindung und Verifizierung zum SecondServer funktioniert
     * @return bool true wenn keine Probleme auftreten
    */
    public static function testConnection(): bool {
        $body = [
            "request" => "test",
            "exp" => "bool"
        ];

        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    // add user
    public static function addUser(mixed $userData): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "add",
            "data" => array($userData)
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    // get some user (sys only)
    public static function getUser(string $uid): mixed {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "get",
            "uid" => $uid
        ];

        $res = self::send($header, $body);
        
        return json_decode($res, true);
    }

    // get actual user
    public static function getThisUser(string $jwt): mixed {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];
    
        $body = [
            "request" => "user_action",
            "pr" => "get_this",
            "jwt" => $jwt
        ];
    
        $res = self::send($header, $body);
        $tmp = json_decode($res, true);

        return $tmp;
    }
    
    // edit some user (sys only)
    public static function editUser(string $uid, mixed $newUserData): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "edit",
            "uid" => $uid,
            "new_data" => array($newUserData)
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    // edit actual user
    public static function editThisUser(string $jwt, mixed $newUserData): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "edit_this",
            "jwt" => $jwt,
            "new_data" => array($newUserData)
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }
    
    // delete user
    public static function deleteUser(string $uid): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "delete",
            "uid" => $uid
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    public static function deleteThisUser(string $jwt): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "delete_this",
            "jwt" => $jwt
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    public static function test2fa(string $jwt): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "test2fa",
            "jwt" => $jwt
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    public static function tfa(string $pin, string $uid): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "2fa",
            "pin" => $pin,
            "uid" => $uid
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    public static function userLogin(string $userNameOrEmail, string $password): string {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "login",
            "user_name_email" => $userNameOrEmail,
            "password" => hash(Vars::second_server_hash(), $password)
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->jwt;

        if (!str_contains($res, "error")) {
            COOKIE::setSecure("jwt", $res);
        }

        return $res;
    }

    public static function isLoggedIn(): bool {
        if (COOKIE::exists("jwt")) {
            $jwt = COOKIE::get("jwt");
            $user = self::getThisUser($jwt);

            if ($user == 404) {
                return false;
            }

            return true;
        }

        return false;
    }

    public static function userLogout(): void {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "user_logout",
            "jwt" => COOKIE::get("jwt")
        ];

        $res = json_decode(self::send($header, $body));

        COOKIE::delete("jwt");
    }

    public static function tfaAbr(): void {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "2fa_abr",
            "jwt" => COOKIE::get("jwt")
        ];

        $res = json_decode(self::send($header, $body));
    }

    public static function verifyMail(string $mailToken): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "verify_mail",
            "mail_token" => $mailToken
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    public static function fergotPassword(string $user): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "pwf_p1",
            "user" => $user
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    public static function newPassword(string $token, string $password): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "pwf_p3",
            "token" => $token,
            "password" => $password
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }

    public static function checkPassword(string $token): bool {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "pwf_p2",
            "token" => $token
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        if ($res == "ok") {
            return true;
        }

        return false;
    }


    // ====---- greenbucket ----====

    public static function checkForToken($la, $lo, $ac, $uid, $ttk) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "TOKEN_SERVER_GREENBUCKET",
            "la" => $la,
            "lo" => $lo,
            "ac" => $ac,
            "uid" => $uid,
            "ttk" => $ttk
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }

    public static function mailVerifyEdit($uid, $email) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "VERIFY_EMAIL_EDIT_GREENBUCKET",
            "uid" => $uid,
            "email" => $email
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }

    public static function testForEmail($email) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "TEST_FOR_EMAIL_GREENBUCKET",
            "email" => $email
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }

    public static function getCoupon($uid, $couponId, $companyId) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "GET_COUPON_GREENBUCKET",
            "uid" => $uid,
            "couponId" => $couponId,
            "companyId" => $companyId
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }

    public static function testLicense($license, $licenseAreaTable) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "TEST_LICENSE_GREENBUCKET",
            "lic" => $license,
            "lat" => $licenseAreaTable
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }

    public static function getLicenseData($license, $licenseAreaTable) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "GET_LICENSE_DATA_GREENBUCKET",
            "lic" => $license,
            "lat" => $licenseAreaTable
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }

    public static function getAllLicenses($licenseAreaTable) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "GET_ALL_LICENSES_GREENBUCKET",
            "lat" => $licenseAreaTable
        ];

        $res = json_decode(self::send($header, $body), true);
        // $res = $res[0]->status;

        return $res;
    }

    public static function generateLicense($lat, $uid, $kdn) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "CREATE_LICENSES_GREENBUCKET",
            "lat" => $lat,
            "uid" => $uid,
            "kdn" => $kdn
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }

    public static function createLog($log) {
        $header = [
            "x-auth-key" => Vars::second_server_key(),
            "token" => self::getToken(),
            "Content-Type" => "application/json"
        ];

        $body = [
            "request" => "user_action",
            "pr" => "CREATE_LOG_GREENBUCKET",
            "log" => $log,
        ];

        $res = json_decode(self::send($header, $body));
        $res = $res[0]->status;

        return $res;
    }
}

class Jobsy {
    public static function send($uid, $do, $table, $where = "", $is = "", $data = []) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://jobsy.greenbucket.online/api.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "uid": "' . $uid . '",
                "do": "' . $do . '",
                "table": "' . $table . '",
                "where": "' . $where . '",
                "is": "' . $is . '",
                "data": ' . json_encode($data) . '
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: Cookie2=abc; PHPSESSID=qi074ievfvciedovnnp7r2m6ti; TestCookie=Test1'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true);
    }

    public static function test($uid) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://jobsy.greenbucket.online/api.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "uid": "' . $uid . '",
                "do": "test"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: Cookie2=abc; PHPSESSID=qi074ievfvciedovnnp7r2m6ti; TestCookie=Test1'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response, true)["res"];
    }
}

/**
 * Vielen Dank, dass Sie das gbDB PHP FrameWork verwenden.
 */

Session::handler();
Cookie::init();

if (Vars::enable_https_redirect()) {
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO']) {
        $_SERVER['HTTPS'] = 1;
    }
}
?>
