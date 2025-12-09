<?php

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
