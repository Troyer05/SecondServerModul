<?php

trait GBDB_CrudTrait {

    /**
     * Verarbeitet die Funktion create database.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") return false;

        $base = Vars::DB_PATH();

        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, true)
            : $name;

        if ($dirName === null) return false;

        $path = $base . $dirName;

        if (!is_dir($path)) {
            return @mkdir($path, 0777);
        }

        return false;
    }


    /**
     * Verarbeitet die Funktion delete database.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteDatabase(string $name): bool {
        $name = Format::cleanString($name);

        if ($name === "") return false;

        $dirName = Vars::crypt_data()
            ? self::getDbToken($name, false)
            : $name;

        if ($dirName === null) return false;

        $path = Vars::DB_PATH() . $dirName;

        if (is_dir($path)) {
            $files = scandir($path);

            if ($files) {
                $rest = array_diff($files, ['.', '..']);

                if (Vars::crypt_data()) {
                    $idx = basename(self::tableIndexFileByDbToken($dirName));
                    $rest = array_values($rest);

                    if (count($rest) === 1 && $rest[0] === $idx) {
                        @unlink($path . "/" . $idx);
                        return @rmdir($path);
                    }
                }

                if (count($rest) === 0) {
                    return @rmdir($path);
                }
            }
        }

        return false;
    }


    /**
     * Verarbeitet die Funktion create table.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param array $cols Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createTable(string $database, string $table, array $cols): bool {
        $file       = self::makePath($database, $table, true);
        $lockFile   = self::lockFileForTable($database, $table, true);
        $metaFile   = self::metaFileForTable($database, $table, true);
        $appendFile = self::appendFileForTable($database, $table, true);

        $res = (bool) self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $cols) {
            if (file_exists($file)) return false;

            $header = ["id" => -1];

            foreach ($cols as $col) {
                $col = (string)$col;
                if ($col === "" || $col === "id") continue;
                $header[$col] = "-header-";
            }

            if (!self::writeTable($file, [$header])) {
                return false;
            }

            self::writeMeta($metaFile, GBDBStorage::normalizeMeta([
                "last_id" => 0,
                "rows" => 0,
                "append_ops" => 0,
                "checksum" => GBDBStorage::checksum([$header]),
                "indexes" => [],
                "created_at" => time(),
                "updated_at" => time()
            ]));

            // Append: leer (line-based). Keine Crypt::encode hier, jede Zeile wird einzeln encoded.
            if (!is_file($appendFile)) {
                if (!GBDBStorage::atomicWrite($appendFile, "")) {
                return false;
            }
            }

            return true;
        });

        if ($res) {
            self::setSchemaTable($database, $table, $cols);
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
        }

        return $res;
    }


    /**
     * Verarbeitet die Funktion add column.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param string $column Übergabewert.
     * @param mixed $default Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function addColumn(string $database, string $table, string $column, mixed $default = ""): bool {
        $column = trim($column);

        if ($column === '' || $column === 'id') return false;

        $file       = self::makePath($database, $table);
        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = (bool) self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $column, $default) {
            if (!file_exists($file)) return false;

            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $ops  = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);

            if (empty($full) || !isset($full[0]) || !is_array($full[0]) || !self::isHeaderRow($full[0])) {
                return false;
            }

            $header = $full[0];

            if (array_key_exists($column, $header)) {
                return true;
            }

            $newHeader = [];

            foreach ($header as $key => $value) {
                $newHeader[$key] = $value;

                if ($key === 'id') {
                    $newHeader[$column] = '-header-';
                }
            }

            if (!array_key_exists($column, $newHeader)) {
                $newHeader[$column] = '-header-';
            }

            $rebuilt = [$newHeader];
            $rows = 0;

            foreach ($full as $i => $row) {
                if (!is_array($row)) continue;
                if ($i === 0) continue;

                if (!array_key_exists($column, $row)) {
                    $row[$column] = $default;
                }

                $tmp = [];

                foreach ($newHeader as $key => $_) {
                    if ($key === 'id') continue;
                    $tmp[$key] = array_key_exists($key, $row) ? $row[$key] : $default;
                }

                $tmp['id'] = isset($row['id']) ? (int)$row['id'] : 0;
                $rebuilt[] = $tmp;
                $rows++;
            }

            if (!self::writeTable($file, $rebuilt)) {
                return false;
            }

            @file_put_contents($appendFile, '', LOCK_EX);

            $meta = self::readMeta($metaFile);
            $meta['rows'] = $rows;
            $meta['append_ops'] = 0;

            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            self::setSchemaTable($database, $table, [$column => $default]);
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
        }

        return $res;
    }


    /**
     * Verarbeitet die Funktion delete table.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteTable(string $database, string $table): bool {
        $file       = self::makePath($database, $table);
        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = (bool) self::withTableLock($lockFile, function () use ($database, $table, $file, $metaFile, $appendFile, $lockFile) {

            if (!file_exists($file)) return false;

            $ok = @unlink($file);

            if ($ok) {
                if (is_file($metaFile)) @unlink($metaFile);
                if (is_file($appendFile)) @unlink($appendFile);
                if (is_file($appendFile . ".wal")) @unlink($appendFile . ".wal");

                GBDBStorage::deleteTableArtifacts($file);
                self::dropTableFromIndex($database, $table);

                if (is_file($lockFile)) @unlink($lockFile);
            }

            return $ok;
        });

        if ($res) {
            self::dropSchemaTable($database, $table);
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
        }

        return $res;
    }


    /**
     * Verarbeitet die Funktion insert data.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $data Übergabewert.
     * @return int Rückgabewert.
     */
    public static function insertData(string $database, string $table, mixed $data): int {
        if (!is_array($data)) return -1;

        $file = self::makePath($database, $table);

        if (!file_exists($file)) return -1;

        if (self::inTransaction()) {
            $id = self::reserveTransactionId($database, $table, $data);
            $data["id"] = $id;

            self::$txOps[] = ["type" => "insert", "db" => $database, "table" => $table, "data" => $data, "id" => $id];

            return $id;
        }

        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);
        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $data) {

            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                self::ensureHeader($base, array_keys($data));
                if (!self::writeTable($file, $base)) return -1;
            }

            $header = $base[0];
            $meta = self::readMeta($metaFile);
            $next = (int)($meta["last_id"] ?? 0) + 1;
            $id = isset($data["id"]) ? (int)$data["id"] : $next;

            if ($id <= 0) $id = $next;

            $row = self::buildRowFromHeader($header, $data, $id);
            $full = self::applyOps($base, self::readAppendOps($appendFile));

            if (!GBDBStorage::validateConstraints($full, $row, $meta["constraints"] ?? [], $id)) {
                return -1;
            }

            $ok = self::appendOp($appendFile, [
                "op"  => "ins",
                "row" => $row,
                "ts"  => time(),
            ]);

            if (!$ok) return -1;

            $meta["last_id"] = max((int)($meta["last_id"] ?? 0), $id);
            $meta["rows"]    = (int)($meta["rows"] ?? 0) + 1;
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + 1;

            self::writeMeta($metaFile, $meta);

            return $id;
        });

        if (is_int($res) && $res > 0) {
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
        }

        return is_int($res) ? $res : -1;
    }


    /**
     * Verarbeitet die Funktion delete data.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteData(string $database, string $table, mixed $where, mixed $is): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) return false;

        if (self::inTransaction()) {
            self::$txOps[] = ["type" => "delete", "db" => $database, "table" => $table, "where" => $where, "is" => $is];
            return true;
        }

        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $where, $is) {

            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $ops  = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);
            $hasHeader = (isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]));
            $changed = false;
            $ids = [];

            foreach ($full as $i => $r) {
                if (!is_array($r)) continue;
                if ($hasHeader && $i === 0) continue;

                if (isset($r[$where]) && $r[$where] == $is && isset($r["id"])) {
                    $ids[] = (int)$r["id"];
                }
            }

            if (empty($ids)) return false;

            foreach ($ids as $id) {
                if (!self::appendOp($appendFile, [
                    "op" => "del",
                    "id" => $id,
                    "ts" => time(),
                ])) {
                    return false;
                }

                $changed = true;
            }

            if ($changed) {
                $meta = self::readMeta($metaFile);
                $meta["rows"] = max(0, (int)($meta["rows"] ?? 0) - count($ids));
                $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);

                self::writeMeta($metaFile, $meta);
            }

            return $changed;
        });

        if ($res) {
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
        }

        return (bool)$res;
    }


    /**
     * Verarbeitet die Funktion edit data.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @param mixed $newData Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function editData(string $database, string $table, mixed $where, mixed $is, mixed $newData): bool {
        if (!is_array($newData)) return false;

        $file = self::makePath($database, $table);

        if (!file_exists($file)) return false;

        if (self::inTransaction()) {
            self::$txOps[] = ["type" => "edit", "db" => $database, "table" => $table, "where" => $where, "is" => $is, "data" => $newData];
            return true;
        }

        $lockFile   = self::lockFileForTable($database, $table);
        $metaFile   = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $where, $is, $newData) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $ops  = self::readAppendOps($appendFile);
            $full = self::applyOps($base, $ops);
            $header = $full[0];
            $hasHeader = (isset($header) && is_array($header) && self::isHeaderRow($header));
            $set = [];

            foreach ($newData as $k => $v) {
                if ($k === "id") continue;

                if ($hasHeader && array_key_exists($k, $header)) {
                    $set[$k] = $v;
                }
            }

            if (empty($set)) return false;

            $ids = [];

            foreach ($full as $i => $r) {
                if (!is_array($r)) continue;
                if ($hasHeader && $i === 0) continue;

                if (isset($r[$where]) && $r[$where] == $is && isset($r["id"])) {
                    $ids[] = (int)$r["id"];
                }
            }

            if (empty($ids)) return false;

            foreach ($ids as $id) {
                $candidate = [];

                foreach ($full as $row) {
                    if (is_array($row) && isset($row["id"]) && (int)$row["id"] === $id) {
                        $candidate = array_merge($row, $set);
                        break;
                    }
                }

                $meta = self::readMeta($metaFile);

                if (!GBDBStorage::validateConstraints($full, $candidate, $meta["constraints"] ?? [], $id)) {
                    return false;
                }

                if (!self::appendOp($appendFile, [
                    "op"  => "upd",
                    "id"  => $id,
                    "set" => $set,
                    "ts"  => time(),
                ])) {
                    return false;
                }
            }

            $meta = self::readMeta($metaFile);
            $meta["append_ops"] = (int)($meta["append_ops"] ?? 0) + count($ids);

            self::writeMeta($metaFile, $meta);

            return true;
        });

        if ($res) {
            if (method_exists(static::class, 'clearRuntimeCache')) self::clearRuntimeCache($database, $table);
            self::autoCompact($database, $table);
        }

        return (bool)$res;
    }


    public static function getData(
        string $database,
        string $table,
        bool $filter = false,
        mixed $where = "",
        mixed $is = ""
    ): mixed {
        $file = self::makePath($database, $table);
        $base = self::ini($file);

        if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
            return $filter ? [] : [];
        }

        $appendFile = self::appendFileForTable($database, $table);
        $ops        = self::readAppendOps($appendFile);

        if ($filter && empty($ops)) {
            $meta = self::readMeta(self::metaFileForTable($database, $table));
            $indexes = $meta["indexes"] ?? [];

            if (in_array((string)$where, $indexes, true)) {
                $ids = GBDBStorage::indexLookup($file, (string)$where, $is);

                if (!empty($ids)) {
                    foreach ($base as $i => $row) {
                        if (!is_array($row)) continue;
                        if ($i === 0 && self::isHeaderRow($row)) continue;

                        if (isset($row["id"]) && in_array((int)$row["id"], $ids, true)) {
                            return $row;
                        }
                    }
                }

                return [];
            }
        }

        $full = self::applyOps($base, $ops);
        $hasHeader = (isset($full[0]) && is_array($full[0]) && self::isHeaderRow($full[0]));

        if ($filter) {
            foreach ($full as $i => $r) {
                if (!is_array($r)) continue;
                if ($hasHeader && $i === 0) continue;

                if (isset($r[$where]) && $r[$where] == $is) {
                    return $r;
             }
            }

            return [];
        }

        if ($hasHeader) {
            unset($full[0]);
            $full = array_values($full);
        }

        return $full;
    }


    /**
     * Verarbeitet die Funktion element exists.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @param mixed $where Übergabewert.
     * @param mixed $is Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function elementExists(string $database, string $table, mixed $where, mixed $is): bool {
        $r = self::getData($database, $table, true, $where, $is);
        return is_array($r) && !empty($r);
    }


    /**
     * Verarbeitet die Funktion list d bs.
     * @return array Rückgabewert.
     */
    public static function listDBs(): array {
        $d = Vars::DB_PATH();

        if (!is_dir($d)) return [];

        if (!Vars::crypt_data()) {
            $dirs = [];

            $tmp = array_filter(scandir($d), function ($f) use ($d) {
                return $f !== '.' && $f !== '..' && is_dir($d . $f);
            });

            foreach ($tmp as $db_name) {
                $dirs[] = $db_name;
            }

            return $dirs;
        }

        $idxFile = self::dbIndexFile();
        $map     = self::readIndex($idxFile);
        $out = [];

        foreach ($map as $plain => $token) {
            if (is_dir($d . $token)) {
                $out[] = $plain;
            }
        }

        return $out;
    }


    /**
     * Verarbeitet die Funktion list tables.
     * @param string $database Übergabewert.
     * @param bool $descending Übergabewert.
     * @return array Rückgabewert.
     */
    public static function listTables(string $database, bool $descending = false): array {
        $database = Format::cleanString($database);

        if ($database === "") return [];

        $ext = Vars::data_extension();

        if (!Vars::crypt_data()) {
            $databasePath = Vars::DB_PATH() . $database . "/";

            if (!is_dir($databasePath)) return [];

            $tables = [];
            $order  = $descending ? 1 : 0;
            $tmp    = scandir($databasePath, $order);

            foreach ($tmp as $entry) {
                if ($entry === "." || $entry === "..") continue;
                if (str_ends_with($entry, ".lock")) continue;
                if (!str_ends_with($entry, $ext)) continue;
                if (str_starts_with($entry, "__meta__")) continue;
                if (str_starts_with($entry, "__append__")) continue;
                if (str_starts_with($entry, "__idx__")) continue;
                if (str_starts_with($entry, "__idxa__")) continue;

                $tables[] = str_replace($ext, "", $entry);
            }

            return $tables;
        }

        $dbToken = self::getDbToken($database, false);

        if ($dbToken === null) return [];

        $databasePath = Vars::DB_PATH() . $dbToken . "/";

        if (!is_dir($databasePath)) return [];

        $idxFile = self::tableIndexFileByDbToken($dbToken);
        $map     = self::readIndex($idxFile);
        $tables = [];

        foreach ($map as $plain => $token) {
            $file = $databasePath . $token . $ext;

            if (is_file($file)) {
                $tables[] = $plain;
            }
        }

        if ($descending) {
            rsort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            sort($tables, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $tables;
    }


    /**
     * Verarbeitet die Funktion delete all.
     * @param string $database Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteAll(string $database): bool {
        $ok     = true;
        $tables = self::listTables($database);

        foreach ($tables as $tbl) {
            if (!self::deleteTable($database, $tbl)) {
                $ok = false;
                break;
            }
        }

        self::removeTableIndexIfExists($database);

        if (!self::deleteDatabase($database)) {
            $ok = false;
        }

        if ($ok) {
            self::dropSchemaDatabase($database);
        }

        return $ok;
    }


    /**
     * Verarbeitet die Funktion next i d.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return int Rückgabewert.
     */
    public static function nextID(string $database, string $table): int {
        $file = self::makePath($database, $table);
        
        if (!file_exists($file)) return 0;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($metaFile) {
            $meta = self::readMeta($metaFile);
            return (int)($meta["last_id"] ?? 0) + 1;
        });

        return is_int($res) ? $res : 0;
    }


    /**
     * Verarbeitet die Funktion get keys.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getKeys(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $db   = self::ini($file);

        if (empty($db) || !isset($db[0]) || !is_array($db[0])) {
            return [];
        }

        return array_keys($db[0]);
    }


    /**
     * Führt eine GreenQL-Abfrage aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function query(string $script, array $ctx = [], array $params = []): array {
        return GreenQL::run($script, $ctx, $params);
    }


    /**
     * Führt ein Script aus und gibt das Ergebnis zurück.
     * @param string $path Übergabewert.
     * @param array $params Übergabewert.
     * @param array $ctx Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $path, array $params = [], array $ctx = []): array {
        if (!file_exists($path)) {
            return [
                "ok" => false,
                "messages" => [
                    ["ok" => false, "text" => "Script nicht gefunden: " . $path]
                ],
                "results" => [],
                "keys" => [],
                "rows" => [],
                "ctx" => [
                    "db" => GreenQL::cleanName((string)($ctx["db"] ?? '')),
                    "table" => GreenQL::cleanName((string)($ctx["table"] ?? ''))
                ],
                "vars" => [],
                "refresh" => false
            ];
        }

        $script = file_get_contents($path);

        if ($script === false) {
            return [
                "ok" => false,
                "messages" => [
                    ["ok" => false, "text" => "Script konnte nicht gelesen werden: " . $path]
                ],
                "results" => [],
                "keys" => [],
                "rows" => [],
                "ctx" => [
                    "db" => GreenQL::cleanName((string)($ctx["db"] ?? '')),
                    "table" => GreenQL::cleanName((string)($ctx["table"] ?? ''))
                ],
                "vars" => [],
                "refresh" => false
            ];
        }

        return self::query($script, $ctx, $params);
    }
}
