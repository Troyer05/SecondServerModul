<?php

trait GBDBv2_MaintenanceTrait {

    /**
     * Komprimiert eine Tabelle.
     * @param string $database Übergabewert.
     * @param string $table Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function compactTable(string $database, string $table): bool {
        $file = self::makePath($database, $table);

        if (!file_exists($file)) {
            return false;
        }

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile) {
            $base = self::ini($file);

            if (empty($base) || !isset($base[0]) || !is_array($base[0])) {
                return false;
            }

            $ops = self::readAppendOps($appendFile);

            if (empty($ops)) {
                return true;
            }

            $full = self::applyOps($base, $ops);

            if (!self::writeTable($file, $full)) {
                return false;
            }

            if (!GBDBStorage::atomicWrite($appendFile, "")) {
                return false;
            }

            $meta = self::readMeta($metaFile);

            $maxId = (int)($meta["last_id"] ?? 0);
            $rows = 0;

            foreach ($full as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($i === 0 && self::isHeaderRow($row)) {
                    continue;
                }

                $rows++;

                if (isset($row["id"])) {
                    $maxId = max($maxId, (int)$row["id"]);
                }
            }

            $meta["rows"] = $rows;
            $meta["last_id"] = $maxId;
            $meta["append_ops"] = 0;
            $meta["deleted_rows"] = 0;
            $meta["checksum"] = GBDBStorage::checksum($full);
            $meta["last_compaction"] = time();

            GBDBStorage::rebuildIndexes($file, $meta, $full);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }



    /**
     * Erstellt einen Snapshot einer Tabelle.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $reason Grund.
     * @return string Snapshot-ID oder leer.
     */
    public static function snapshot(string $database, string $table, string $reason = "manual"): string {
        $file = self::makePath($database, $table);

        if (!is_file($file)) return "";

        $extras = [
            self::metaFileForTable($database, $table),
            self::appendFileForTable($database, $table),
            self::appendFileForTable($database, $table) . ".wal"
        ];

        $id = GBDBStorage::snapshot($file, $extras, $reason);

        if ($id !== "") {
            $metaFile = self::metaFileForTable($database, $table);
            $meta = self::readMeta($metaFile);
            $meta["last_snapshot"] = time();
            self::writeMeta($metaFile, $meta);
        }

        return $id;
    }


    /**
     * Erstellt einen Spaltenindex.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @return bool true bei Erfolg.
     */
    public static function createIndex(string $database, string $table, string $column): bool {
        $column = Format::cleanString($column);
        $file = self::makePath($database, $table);

        if ($column === "" || $column === "id" || !is_file($file)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $column) {
            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $full = self::applyOps($base, self::readAppendOps($appendFile));
            $header = $full[0] ?? [];

            if (!is_array($header) || !array_key_exists($column, $header)) return false;

            if (!GBDBStorage::writeIndex($file, $column, $full)) return false;

            $meta = self::readMeta($metaFile);
            $indexes = $meta["indexes"] ?? [];

            if (!in_array($column, $indexes, true)) {
                $indexes[] = $column;
            }

            sort($indexes, SORT_NATURAL | SORT_FLAG_CASE);
            $meta["indexes"] = $indexes;
            $meta["checksum"] = GBDBStorage::checksum($full);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }


    /**
     * Löscht einen Spaltenindex.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @return bool true bei Erfolg.
     */
    public static function dropIndex(string $database, string $table, string $column): bool {
        $column = Format::cleanString($column);
        $file = self::makePath($database, $table);

        if ($column === "" || !is_file($file)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $column) {
            $meta = self::readMeta($metaFile);
            $meta["indexes"] = array_values(array_filter($meta["indexes"] ?? [], function ($idx) use ($column) {
                return (string)$idx !== $column;
            }));

            GBDBStorage::deleteIndex($file, $column);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }


    /**
     * Gibt alle Spaltenindexe einer Tabelle zurück.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Index-Spalten.
     */
    public static function listIndexes(string $database, string $table): array {
        $metaFile = self::metaFileForTable($database, $table);
        $meta = self::readMeta($metaFile);
        return array_values($meta["indexes"] ?? []);
    }


    /**
     * Baut alle bekannten Indexe einer Tabelle neu.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return bool true bei Erfolg.
     */
    public static function rebuildIndexes(string $database, string $table): bool {
        $file = self::makePath($database, $table);

        if (!is_file($file)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile) {
            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            $full = self::applyOps($base, self::readAppendOps($appendFile));
            $meta = self::readMeta($metaFile);
            GBDBStorage::rebuildIndexes($file, $meta, $full);
            $meta["checksum"] = GBDBStorage::checksum($full);
            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }


    /**
     * Prüft eine Tabelle auf grundlegende Konsistenz.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Diagnose-Daten.
     */
    public static function health(string $database, string $table): array {
        $file = self::makePath($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $warnings = [];
        $errors = [];

        if (!is_file($file)) {
            return ["ok" => false, "errors" => ["table_not_found"], "warnings" => []];
        }

        $base = self::ini($file);

        if (empty($base) || !isset($base[0]) || !is_array($base[0]) || !self::isHeaderRow($base[0])) {
            $errors[] = "invalid_or_missing_header";
        }

        $ops = self::readAppendOps($appendFile);
        $full = self::applyOps($base, $ops);
        $meta = self::readMeta($metaFile);
        $rows = 0;
        $ids = [];

        foreach ($full as $i => $row) {
            if (!is_array($row)) continue;
            if ($i === 0 && self::isHeaderRow($row)) continue;
            $rows++;

            if (!isset($row["id"])) {
                $warnings[] = "row_without_id";
                continue;
            }

            $id = (int)$row["id"];

            if (isset($ids[$id])) {
                $errors[] = "duplicate_id:" . $id;
            }

            $ids[$id] = true;
        }

        if ($rows !== (int)($meta["rows"] ?? 0)) {
            $warnings[] = "meta_rows_mismatch";
        }

        if ((int)($meta["append_ops"] ?? 0) > 0 && !is_file($appendFile)) {
            $warnings[] = "append_file_missing";
        }

        if (GBDBStorage::shouldCompact($meta, $appendFile)) {
            $warnings[] = "compaction_recommended";
        }

        return [
            "ok" => empty($errors),
            "errors" => array_values(array_unique($errors)),
            "warnings" => array_values(array_unique($warnings)),
            "meta" => $meta,
            "rows_real" => $rows,
            "append_ops_real" => count($ops)
        ];
    }


    /**
     * Repariert eine Tabelle durch Komprimieren und Index-Neuaufbau.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return bool true bei Erfolg.
     */
    public static function repairTable(string $database, string $table): bool {
        self::snapshot($database, $table, "before_repair");

        if (!self::compactTable($database, $table)) {
            return false;
        }

        return self::rebuildIndexes($database, $table);
    }


    /**
     * Gibt die Meta-Daten einer Tabelle zurück.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Meta-Daten.
     */
    public static function meta(string $database, string $table): array {
        return self::readMeta(self::metaFileForTable($database, $table));
    }



    /**
     * Fügt einen einfachen Constraint für eine Tabellenspalte hinzu.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param string $type Constraint-Typ: unique oder required.
     * @return bool true bei Erfolg.
     */
    public static function addConstraint(string $database, string $table, string $column, string $type): bool {
        $column = Format::cleanString($column);
        $type = strtolower(Format::cleanString($type));
        $file = self::makePath($database, $table);

        if ($column === "" || $column === "id" || !is_file($file)) return false;
        if (!in_array($type, ["unique", "required"], true)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);
        $appendFile = self::appendFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $metaFile, $appendFile, $column, $type) {
            $base = self::ini($file);
            if (empty($base) || !isset($base[0]) || !is_array($base[0])) return false;

            if (!array_key_exists($column, $base[0])) return false;

            $full = self::applyOps($base, self::readAppendOps($appendFile));
            $meta = self::readMeta($metaFile);
            $constraints = $meta["constraints"] ?? [];

            if (!isset($constraints[$column]) || !is_array($constraints[$column])) {
                $constraints[$column] = [];
            }

            $constraints[$column][$type] = true;

            foreach ($full as $i => $row) {
                if (!is_array($row)) continue;
                if ($i === 0 && self::isHeaderRow($row)) continue;

                if (!GBDBStorage::validateConstraints($full, $row, $constraints, isset($row["id"]) ? (int)$row["id"] : null)) {
                    return false;
                }
            }

            $meta["constraints"] = $constraints;

            if ($type === "unique") {
                $idx = $meta["indexes"] ?? [];
                if (!in_array($column, $idx, true)) $idx[] = $column;
                sort($idx, SORT_NATURAL | SORT_FLAG_CASE);
                $meta["indexes"] = $idx;
                GBDBStorage::writeIndex($file, $column, $full);
            }

            self::writeMeta($metaFile, $meta);
            return true;
        });

        return (bool)$res;
    }


    /**
     * Entfernt einen einfachen Constraint von einer Tabellenspalte.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $column Spalte.
     * @param string $type Constraint-Typ: unique oder required.
     * @return bool true bei Erfolg.
     */
    public static function dropConstraint(string $database, string $table, string $column, string $type): bool {
        $column = Format::cleanString($column);
        $type = strtolower(Format::cleanString($type));
        $file = self::makePath($database, $table);

        if ($column === "" || !is_file($file)) return false;
        if (!in_array($type, ["unique", "required"], true)) return false;

        $lockFile = self::lockFileForTable($database, $table);
        $metaFile = self::metaFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($metaFile, $column, $type) {
            $meta = self::readMeta($metaFile);
            $constraints = $meta["constraints"] ?? [];

            if (isset($constraints[$column][$type])) {
                unset($constraints[$column][$type]);
            }

            if (isset($constraints[$column]) && empty($constraints[$column])) {
                unset($constraints[$column]);
            }

            $meta["constraints"] = $constraints;
            self::writeMeta($metaFile, $meta);

            return true;
        });

        return (bool)$res;
    }


    /**
     * Gibt die Constraints einer Tabelle zurück.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @return array Constraints.
     */
    public static function listConstraints(string $database, string $table): array {
        $meta = self::readMeta(self::metaFileForTable($database, $table));
        return $meta["constraints"] ?? [];
    }


    /**
     * Stellt einen Tabellen-Snapshot wieder her.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param string $snapshotId Snapshot-ID.
     * @return bool true bei Erfolg.
     */
    public static function restoreSnapshot(string $database, string $table, string $snapshotId): bool {
        $file = self::makePath($database, $table);

        if (!is_file($file)) return false;

        $lockFile = self::lockFileForTable($database, $table);

        $res = self::withTableLock($lockFile, function () use ($file, $snapshotId) {
            return GBDBStorage::restoreSnapshot($file, $snapshotId);
        });

        return (bool)$res;
    }
}
