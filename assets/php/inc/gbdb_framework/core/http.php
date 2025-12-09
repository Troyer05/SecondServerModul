<?php

class Http {

    /** Führt einen HTTP-GET-Request aus */
    public static function get(string $url, array $headers = [], int $timeout = 10): string|false {
        if (function_exists('curl_init')) {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => self::formatHeaders($headers)
            ]);

            $response = curl_exec($ch);

            curl_close($ch);

            return $response;
        }

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => self::implodeHeaders($headers),
                'timeout' => $timeout
            ]
        ];

        return file_get_contents($url, false, stream_context_create($opts));
    }

    /** Führt einen HTTP-POST-Request aus */
    public static function post(string $url, array $data = [], array $headers = [], int $timeout = 10): string|false {
        if (function_exists('curl_init')) {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => self::formatHeaders($headers)
            ]);

            $response = curl_exec($ch);

            curl_close($ch);

            return $response;
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => self::implodeHeaders($headers) . "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => $timeout
            ]
        ];

        return file_get_contents($url, false, stream_context_create($opts));
    }

    /** Gibt eine JSON-Antwort mit optionalem HTTP-Statuscode zurück */
    public static function jsonResponse(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    /** Leitet zu einer anderen Seite weiter */
    public static function redirect(string $url, int $status = 302): void {
        http_response_code($status);
        header("Location: $url");

        exit;
    }

    /** Gibt alle HTTP-Header des aktuellen Requests zurück */
    public static function getHeaders(): array {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /** Gibt HTTP-Methode des aktuellen Requests zurück (GET, POST, etc.) */
    public static function method(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Prüft, ob der aktuelle Request JSON enthält */
    public static function isJson(): bool {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }

    /** Liest JSON-Body eines Requests als Array */
    public static function jsonInput(): array {
        $input = file_get_contents('php://input');
        $decoded = json_decode($input, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** Formatiert Header-Array für cURL */
    private static function formatHeaders(array $headers): array {
        $formatted = [];

        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }

        return $formatted;
    }

    /** Wandelt Header-Array in Text um (für file_get_contents) */
    private static function implodeHeaders(array $headers): string {
        $result = '';

        foreach ($headers as $key => $value) {
            $result .= "$key: $value\r\n";
        }

        return $result;
    }
}
