<?php

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
