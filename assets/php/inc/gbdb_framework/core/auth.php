<?php

class Auth {
    public static function testLogin(string $cookie, string $errFile, mixed $users, string $testFor, array $whitelist): void {
        if (!in_array(Vars::this_file(), $whitelist)) {
            if (!Cookie::exists($cookie)) {
                Ref::to($errFile);
            }

            $ok = false;

            foreach ($users as $i => $r) {
                if ($r[$testFor] == Cookie::get($cookie)) {
                    $ok = true;
                    break;
                }
            }

            if (!$ok) {
                Ref::to($errFile);
            }
        }
    }

    public static function login(mixed $users, string $user, string $pass, string $r_user, string $r_pass, string $r_mail, string $cookie, string $r_cookie): bool {
        $ok = false;
        $pass = hash('sha256', $pass);

        foreach ($users as $i => $r) {
            if ($r[$r_user] == $user || $r[$r_mail] == $user) {
                if ($r[$r_pass] == $pass) {
                    $ok = true;
                    
                    if ($ok) {
                        Cookie::set($cookie, $r[$r_cookie]);
                    }

                    break;
                }
            }
        }

        return $ok;
    }

    public static function logout(string $cookie, string $refFile) {
        Cookie::delete($cookie);
        Ref::to($refFile);

        exit;
    }

    public static function user(mixed $users, string $cookie, string $testFor): ?array {
        if (!Cookie::exists($cookie)) return null;

        $token = Cookie::get($cookie);

        foreach ($users as $r) {
            if ($r[$testFor] == $token) {
                return $r;
            }
        }

        return null;
    }
}
