<?php

trait GBDB_IndexTrait {

    /**
     * Verarbeitet die Funktion name token.
     * @param string $plain Übergabewert.
     * @param string $ns Übergabewert.
     * @return string Rückgabewert.
     */
    private static function nameToken(string $plain, string $ns = 'g'): string {
        $plain = (string)$plain;
        $key   = (string)Vars::cryptKey();
        $data  = $ns . '|' . $plain;
        $raw  = hash_hmac('sha256', $data, $key, true);
        $b64  = base64_encode($raw);
        $safe = rtrim(strtr($b64, '+/', '-_'), '=');

        return 'gb_' . $safe;
    }


    /**
     * Verarbeitet die Funktion db index file.
     * @return string Rückgabewert.
     */
    private static function dbIndexFile(): string {
        return Vars::DB_PATH() . self::nameToken('__db_index__', 'meta') . Vars::data_extension();
    }


    /**
     * Verarbeitet die Funktion table index file by db token.
     * @param string $dbToken Übergabewert.
     * @return string Rückgabewert.
     */
    private static function tableIndexFileByDbToken(string $dbToken): string {
        $dir = Vars::DB_PATH() . $dbToken . "/";
        return $dir . self::nameToken('__table_index__', 'meta') . Vars::data_extension();
    }


    /**
     * Verarbeitet die Funktion read index.
     * @param string $file Übergabewert.
     * @return array Rückgabewert.
     */
    private static function readIndex(string $file): array {
        $rows = self::ini($file);

        if (empty($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return [];
        }

        unset($rows[0]);

        $rows = array_values($rows);
        $map = [];

        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            if (!isset($r['plain'], $r['token'])) continue;

            $p = (string)$r['plain'];
            $t = (string)$r['token'];

            if ($p !== "" && $t !== "") {
                $map[$p] = $t;
            }
        }

        return $map;
    }


    /**
     * Verarbeitet die Funktion write index.
     * @param string $file Übergabewert.
     * @param array $map Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function writeIndex(string $file, array $map): bool {
        $db = [];

        $db[] = [
            "id"    => -1,
            "plain" => "-header-",
            "token" => "-header-",
        ];

        $id = 0;

        foreach ($map as $plain => $token) {
            $db[] = [
                "id"    => $id++,
                "plain" => (string)$plain,
                "token" => (string)$token,
            ];
        }

        return self::writeTable($file, $db);
    }


    /**
     * Verarbeitet die Funktion get db token.
     * @param string $dbPlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getDbToken(string $dbPlain, bool $ensure = false): ?string {
        $dbPlain = Format::cleanString($dbPlain);

        if ($dbPlain === "") return null;

        if (!Vars::crypt_data()) {
            return $dbPlain;
        }

        $idxFile = self::dbIndexFile();
        $map     = self::readIndex($idxFile);

        if (isset($map[$dbPlain])) {
            return $map[$dbPlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken('db:' . $dbPlain, 'db');
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken('db:' . $dbPlain . '#'.$n, 'db');
                $n++;
            } while (isset($used[$token2]));

            $token = $token2;
        }

        $map[$dbPlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }


    /**
     * Verarbeitet die Funktion get table token.
     * @param string $dbPlain Übergabewert.
     * @param string $tablePlain Übergabewert.
     * @param bool $ensure Übergabewert.
     * @return ?string Rückgabewert.
     */
    private static function getTableToken(string $dbPlain, string $tablePlain, bool $ensure = false): ?string {
        $dbPlain    = Format::cleanString($dbPlain);
        $tablePlain = Format::cleanString($tablePlain);

        if ($dbPlain === "" || $tablePlain === "") return null;

        if (!Vars::crypt_data()) {
            return $tablePlain;
        }

        $dbToken = self::getDbToken($dbPlain, $ensure);

        if ($dbToken === null) return null;

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        $map     = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            return $map[$tablePlain];
        }

        if (!$ensure) {
            return null;
        }

        $token = self::nameToken('tbl:' . $dbPlain . '|' . $tablePlain, 'tbl');
        $used = array_flip(array_values($map));

        if (isset($used[$token])) {
            $n = 2;

            do {
                $token2 = self::nameToken('tbl:' . $dbPlain . '|' . $tablePlain . '#'.$n, 'tbl');
                $n++;
            } while (isset($used[$token2]));

            $token = $token2;
        }

        $map[$tablePlain] = $token;

        if (!self::writeIndex($idxFile, $map)) {
            return null;
        }

        return $token;
    }


    /**
     * Verarbeitet die Funktion drop table from index.
     * @param string $dbPlain Übergabewert.
     * @param string $tablePlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function dropTableFromIndex(string $dbPlain, string $tablePlain): void {
        if (!Vars::crypt_data()) return;

        $dbToken = self::getDbToken($dbPlain, false);

        if ($dbToken === null) return;

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        $map     = self::readIndex($idxFile);

        if (isset($map[$tablePlain])) {
            unset($map[$tablePlain]);
            self::writeIndex($idxFile, $map);
        }
    }


    /**
     * Verarbeitet die Funktion remove table index if exists.
     * @param string $dbPlain Übergabewert.
     * @return void Rückgabewert.
     */
    private static function removeTableIndexIfExists(string $dbPlain): void {
        if (!Vars::crypt_data()) return;

        $dbToken = self::getDbToken($dbPlain, false);

        if ($dbToken === null) return;

        $idxFile = self::tableIndexFileByDbToken($dbToken);

        if (is_file($idxFile)) {
            @unlink($idxFile);
        }
    }


    /* ============================================================
       CORE IO + LOCKING + META + APPEND
       ============================================================ */
}
