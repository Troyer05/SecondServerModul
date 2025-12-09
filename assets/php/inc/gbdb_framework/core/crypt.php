<?php

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
