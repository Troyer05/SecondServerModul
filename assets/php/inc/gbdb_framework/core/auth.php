<?php

class Auth {
    /**
     * Prüft ob der User eingeloggt ist.
     */
    public static function testLogin(
        string $cookie,
        string $errFile,
        mixed $users,
        string $testFor,
        array $whitelist
    ): void {
        // White-listed Seiten dürfen ohne Login
        if (!in_array(Vars::this_file(), $whitelist, true)) {

            // Cookie fehlt → redirect
            if (!Cookie::exists($cookie)) {
                Ref::to($errFile);
                exit;
            }

            $token = Cookie::get($cookie);
            $ok    = false;

            // Users muss iterable sein
            if (is_iterable($users)) {
                foreach ($users as $r) {
                    if (isset($r[$testFor]) && $r[$testFor] === $token) {
                        $ok = true;
                        break;
                    }
                }
            }

            if (!$ok) {
                Ref::to($errFile);
                exit;
            }
        }
    }

    /**
     * Login-Funktion
     */
    public static function login(
        mixed $users,
        string $user,
        string $pass,
        string $r_user,
        string $r_pass,
        string $r_mail,
        string $cookie,
        string $r_cookie
    ): bool {
        if (!is_iterable($users)) {
            return false;
        }

        // Legacy hashing (kompatibel)
        $hashedPass = hash('sha256', $pass);

        foreach ($users as $r) {
            $userMatch = (isset($r[$r_user]) && $r[$r_user] === $user)
                      || (isset($r[$r_mail]) && $r[$r_mail] === $user);

            if ($userMatch) {
                // Passwort prüfen
                if (!isset($r[$r_pass])) {
                    continue;
                }

                $stored = $r[$r_pass];
                $match = false;

                // Modern password_hash()?
                if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
                    // Modern verify
                    $match = password_verify($pass, $stored);
                } else {
                    // Legacy SHA-256
                    $match = ($stored === $hashedPass);
                }

                if ($match) {
                    // Cookie setzen
                    if (isset($r[$r_cookie])) {
                        Cookie::set($cookie, $r[$r_cookie]);
                    }
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * User ausloggen
     */
    public static function logout(string $cookie, string $refFile): void {
        Cookie::delete($cookie);
        Ref::to($refFile);
        exit;
    }

    /**
     * Liefert den eingeloggten User zurück oder null.
     */
    public static function user(
        mixed $users,
        string $cookie,
        string $testFor
    ): ?array {
        if (!Cookie::exists($cookie)) {
            return null;
        }

        $token = Cookie::get($cookie);

        if (!is_iterable($users)) {
            return null;
        }

        foreach ($users as $r) {
            if (isset($r[$testFor]) && $r[$testFor] === $token) {
                return $r;
            }
        }

        return null;
    }
}
