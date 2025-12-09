<?php

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
