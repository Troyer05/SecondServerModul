<?php

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
                $token_array[$j] = hash('adler32', strval(rand(0, 4096)));
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
                $token_array[$j] = hash('adler32', strval(rand(0, 1024)));
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
