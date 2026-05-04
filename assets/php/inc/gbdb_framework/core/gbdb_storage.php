<?php

class GBDBStorage {
    private const SNAPSHOT_DIR = ".snapshots";
    private const WAL_SUFFIX = ".wal";
    private const INDEX_PREFIX = "__idx2__";

    /**
     * Schreibt Nutzdaten atomar auf die Festplatte.
     * @param string $file Ziel-Datei.
     * @param string $payload Datei-Inhalt.
     * @return bool true bei Erfolg.
     */
    public static function atomicWrite(string $file, string $payload): bool {
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $tmp = $file . "." . getmypid() . "." . uniqid("tmp_", true);
        $handle = @fopen($tmp, "wb");

        if (!$handle) {
            error_log("[GBDBStorage] Konnte Temp-Datei nicht öffnen: {$tmp}");
            return false;
        }

        $ok = false;

        try {
            if (@flock($handle, LOCK_EX)) {
                $written = @fwrite($handle, $payload);

                if ($written !== false && $written === strlen($payload)) {
                    @fflush($handle);

                    if (function_exists("fsync")) {
                        @fsync($handle);
                    }

                    $ok = true;
                }

                @flock($handle, LOCK_UN);
            }
        } finally {
            @fclose($handle);
        }

        if (!$ok) {
            @unlink($tmp);
            error_log("[GBDBStorage] Konnte Temp-Datei nicht vollständig schreiben: {$tmp}");
            return false;
        }

        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            error_log("[GBDBStorage] Konnte {$tmp} nicht nach {$file} verschieben");
            return false;
        }

        self::syncDir($dir);
        return true;
    }

    /**
     * Fügt eine Zeile sicher an eine Datei an.
     * @param string $file Ziel-Datei.
     * @param string $line Zeile.
     * @return bool true bei Erfolg.
     */
    public static function appendLine(string $file, string $line): bool {
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $handle = @fopen($file, "ab");

        if (!$handle) {
            error_log("[GBDBStorage] Konnte Append-Datei nicht öffnen: {$file}");
            return false;
        }

        $ok = false;

        try {
            if (@flock($handle, LOCK_EX)) {
                $written = @fwrite($handle, $line);

                if ($written !== false && $written === strlen($line)) {
                    @fflush($handle);

                    if (function_exists("fsync")) {
                        @fsync($handle);
                    }

                    $ok = true;
                }

                @flock($handle, LOCK_UN);
            }
        } finally {
            @fclose($handle);
        }

        return $ok;
    }

    /**
     * Schreibt eine WAL-Operation.
     * @param string $appendFile Append-Datei.
     * @param array $op Operation.
     * @param string $state Status.
     * @param string $tx Transaktions-ID.
     * @return bool true bei Erfolg.
     */
    public static function wal(string $appendFile, array $op, string $state, string $tx): bool {
        $entry = [
            "tx" => $tx,
            "state" => $state,
            "op" => $op,
            "ts" => time()
        ];

        $json = json_encode($entry, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        $line = self::encodeLine($json) . "\n";
        return self::appendLine($appendFile . self::WAL_SUFFIX, $line);
    }

    /**
     * Kodiert eine Journal-Zeile passend zur DB-Konfiguration.
     * @param string $json JSON-Zeile.
     * @return string kodierte Zeile.
     */
    public static function encodeLine(string $json): string {
        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            return Crypt::encode($json);
        }

        return $json;
    }

    /**
     * Liefert Standard-Meta-Daten für Tabellen.
     * @param array $meta vorhandene Meta-Daten.
     * @return array normalisierte Meta-Daten.
     */
    public static function normalizeMeta(array $meta = []): array {
        $now = time();

        $defaults = [
            "last_id" => 0,
            "rows" => 0,
            "append_ops" => 0,
            "deleted_rows" => 0,
            "version" => 0,
            "schema_version" => 1,
            "indexes" => [],
            "constraints" => [],
            "checksum" => "",
            "created_at" => $now,
            "updated_at" => $now,
            "last_compaction" => 0,
            "last_snapshot" => 0
        ];

        $meta = array_merge($defaults, $meta);

        if (!is_array($meta["indexes"])) {
            $meta["indexes"] = [];
        }

        if (!is_array($meta["constraints"])) {
            $meta["constraints"] = [];
        }

        return $meta;
    }

    /**
     * Aktualisiert Meta-Daten vor einem Schreibzugriff.
     * @param array $meta Meta-Daten.
     * @param bool $bumpVersion Version erhöhen.
     * @return array aktualisierte Meta-Daten.
     */
    public static function touchMeta(array $meta, bool $bumpVersion = true): array {
        $meta = self::normalizeMeta($meta);
        $meta["updated_at"] = time();

        if ($bumpVersion) {
            $meta["version"] = (int)($meta["version"] ?? 0) + 1;
        }

        return $meta;
    }

    /**
     * Prüft, ob eine Tabelle komprimiert werden sollte.
     * @param array $meta Meta-Daten.
     * @param string $appendFile Append-Datei.
     * @return bool true wenn Komprimierung sinnvoll ist.
     */
    public static function shouldCompact(array $meta, string $appendFile): bool {
        $meta = self::normalizeMeta($meta);
        $appendOps = (int)($meta["append_ops"] ?? 0);
        $rows = max(1, (int)($meta["rows"] ?? 0));
        $appendSize = is_file($appendFile) ? (int)@filesize($appendFile) : 0;

        if ($appendOps <= 0) return false;
        if ($appendOps >= 100) return true;
        if ($appendOps >= max(25, (int)ceil($rows * 0.20))) return true;
        if ($appendSize >= 1024 * 1024) return true;

        return false;
    }

    /**
     * Erzeugt eine Prüfsumme für Tabelleninhalt.
     * @param array $rows Tabellenzeilen.
     * @return string Prüfsumme.
     */
    public static function checksum(array $rows): string {
        $json = json_encode($rows, JSON_UNESCAPED_UNICODE);
        return hash("sha256", $json === false ? "" : $json);
    }

    /**
     * Gibt den Index-Dateipfad für eine Spalte zurück.
     * @param string $dataFile Tabellen-Datei.
     * @param string $column Spalte.
     * @return string Index-Datei.
     */
    public static function indexFile(string $dataFile, string $column): string {
        $column = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $column);
        return dirname($dataFile) . "/" . self::INDEX_PREFIX . basename($dataFile) . "__" . $column . ".idx";
    }

    /**
     * Baut eine Index-Map aus Tabellenzeilen.
     * @param array $rows Tabellenzeilen inkl. Header.
     * @param string $column Spalte.
     * @return array Index-Map.
     */
    public static function buildIndex(array $rows, string $column): array {
        $idx = [];

        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;
            if ($i === 0 && isset($row["id"]) && (int)$row["id"] === -1) continue;
            if (!array_key_exists($column, $row) || !isset($row["id"])) continue;

            $key = self::indexKey($row[$column]);

            if (!isset($idx[$key])) {
                $idx[$key] = [];
            }

            $idx[$key][] = (int)$row["id"];
        }

        return $idx;
    }

    /**
     * Schreibt einen Spaltenindex.
     * @param string $dataFile Tabellen-Datei.
     * @param string $column Spalte.
     * @param array $rows Tabellenzeilen.
     * @return bool true bei Erfolg.
     */
    public static function writeIndex(string $dataFile, string $column, array $rows): bool {
        $payload = json_encode([
            "column" => $column,
            "created_at" => time(),
            "map" => self::buildIndex($rows, $column)
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($payload === false) {
            return false;
        }

        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            $payload = Crypt::encode($payload);
        }

        return self::atomicWrite(self::indexFile($dataFile, $column), $payload);
    }

    /**
     * Löscht einen Spaltenindex.
     * @param string $dataFile Tabellen-Datei.
     * @param string $column Spalte.
     * @return bool true bei Erfolg.
     */
    public static function deleteIndex(string $dataFile, string $column): bool {
        $file = self::indexFile($dataFile, $column);
        return !is_file($file) || @unlink($file);
    }


    /**
     * Prüft einfache Tabellen-Constraints.
     * @param array $rows vorhandene Tabellenzeilen.
     * @param array $candidate neue oder geänderte Zeile.
     * @param array $constraints Constraints aus Meta.
     * @param int|null $excludeId ID, die beim Unique-Check ignoriert wird.
     * @return bool true wenn gültig.
     */
    public static function validateConstraints(array $rows, array $candidate, array $constraints, ?int $excludeId = null): bool {
        if (empty($constraints)) return true;

        foreach ($constraints as $column => $rules) {
            $column = (string)$column;
            $rules = is_array($rules) ? $rules : [];

            if (($rules["required"] ?? false) === true) {
                if (!array_key_exists($column, $candidate) || $candidate[$column] === "" || $candidate[$column] === null) {
                    return false;
                }
            }

            if (($rules["unique"] ?? false) === true && array_key_exists($column, $candidate)) {
                $value = self::indexKey($candidate[$column]);

                foreach ($rows as $i => $row) {
                    if (!is_array($row)) continue;
                    if ($i === 0 && isset($row["id"]) && (int)$row["id"] === -1) continue;
                    if ($excludeId !== null && isset($row["id"]) && (int)$row["id"] === $excludeId) continue;
                    if (!array_key_exists($column, $row)) continue;

                    if (self::indexKey($row[$column]) === $value) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Erzeugt einen stabilen Index-Key.
     * @param mixed $value Wert.
     * @return string Index-Key.
     */
    public static function indexKey(mixed $value): string {
        if (is_bool($value)) return $value ? "bool:true" : "bool:false";
        if ($value === null) return "null";
        if (is_int($value) || is_float($value)) return "num:" . (string)$value;
        return "str:" . (string)$value;
    }

    /**
     * Schreibt alle bekannten Indexe neu.
     * @param string $dataFile Tabellen-Datei.
     * @param array $meta Meta-Daten.
     * @param array $rows Tabellenzeilen.
     * @return void
     */
    public static function rebuildIndexes(string $dataFile, array $meta, array $rows): void {
        $meta = self::normalizeMeta($meta);

        foreach ($meta["indexes"] as $column) {
            $column = (string)$column;
            if ($column === "" || $column === "id") continue;
            self::writeIndex($dataFile, $column, $rows);
        }
    }

    /**
     * Erstellt einen Snapshot der Tabellen-Dateien.
     * @param string $dataFile Tabellen-Datei.
     * @param array $extraFiles zusätzliche Dateien.
     * @param string $reason Grund.
     * @return string Snapshot-ID oder leer.
     */
    public static function snapshot(string $dataFile, array $extraFiles = [], string $reason = "manual"): string {
        if (!is_file($dataFile)) {
            return "";
        }

        $safeReason = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $reason);
        $id = date("Ymd_His") . "_" . substr(hash("sha1", $dataFile . microtime(true)), 0, 8) . "_" . $safeReason;
        $dir = dirname($dataFile) . "/" . self::SNAPSHOT_DIR . "/" . basename($dataFile) . "/" . $id;

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $files = array_merge([$dataFile], $extraFiles);
        $copied = [];

        foreach ($files as $file) {
            if (!is_file($file)) continue;

            $target = $dir . "/" . basename($file);

            if (@copy($file, $target)) {
                $copied[] = basename($file);
            }
        }

        $manifest = [
            "id" => $id,
            "reason" => $reason,
            "created_at" => time(),
            "source" => basename($dataFile),
            "files" => $copied
        ];

        self::atomicWrite($dir . "/manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

        return empty($copied) ? "" : $id;
    }


    /**
     * Entfernt zusätzliche Tabellen-Artefakte wie WAL, Indexe und Snapshots.
     * @param string $dataFile Tabellen-Datei.
     * @return void
     */
    public static function deleteTableArtifacts(string $dataFile): void {
        $dir = dirname($dataFile);
        $base = basename($dataFile);

        foreach (glob($dir . "/" . self::INDEX_PREFIX . $base . "__*.idx") ?: [] as $file) {
            if (is_file($file)) @unlink($file);
        }

        if (is_file($dataFile . self::WAL_SUFFIX)) {
            @unlink($dataFile . self::WAL_SUFFIX);
        }

        $snapDir = $dir . "/" . self::SNAPSHOT_DIR . "/" . $base;

        if (is_dir($snapDir)) {
            self::deleteDir($snapDir);
        }

        $rootSnapDir = $dir . "/" . self::SNAPSHOT_DIR;

        if (is_dir($rootSnapDir)) {
            $items = array_diff(scandir($rootSnapDir) ?: [], [".", ".."]);

            if (empty($items)) {
                @rmdir($rootSnapDir);
            }
        }
    }

    /**
     * Löscht ein Verzeichnis rekursiv.
     * @param string $dir Verzeichnis.
     * @return bool true bei Erfolg.
     */
    public static function deleteDir(string $dir): bool {
        if (!is_dir($dir)) return true;

        $items = scandir($dir);

        if (!$items) return @rmdir($dir);

        foreach ($items as $item) {
            if ($item === "." || $item === "..") continue;

            $path = $dir . "/" . $item;

            if (is_dir($path)) {
                self::deleteDir($path);
            } else {
                @unlink($path);
            }
        }

        return @rmdir($dir);
    }


    /**
     * Dekodiert eine Journal-Zeile passend zur DB-Konfiguration.
     * @param string $line kodierte Zeile.
     * @return string|null dekodierte JSON-Zeile oder null.
     */
    public static function decodeLine(string $line): ?string {
        $line = trim($line);

        if ($line === "") {
            return null;
        }

        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            $decoded = Crypt::decode($line);
            return is_string($decoded) ? $decoded : null;
        }

        return $line;
    }

    /**
     * Liest WAL-Einträge einer Append-Datei.
     * @param string $appendFile Append-Datei.
     * @return array WAL-Einträge.
     */
    public static function readWal(string $appendFile): array {
        $walFile = $appendFile . self::WAL_SUFFIX;

        if (!is_file($walFile)) {
            return [];
        }

        $handle = @fopen($walFile, "r");

        if (!$handle) {
            return [];
        }

        $entries = [];

        try {
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line === false) break;

                $json = self::decodeLine($line);
                if ($json === null) continue;

                $entry = json_decode($json, true);

                if (is_array($entry) && isset($entry["tx"], $entry["state"], $entry["op"])) {
                    $entries[] = $entry;
                }
            }
        } finally {
            @fclose($handle);
        }

        return $entries;
    }

    /**
     * Stellt committed WAL-Operationen sicher in der Append-Datei wieder her.
     * @param string $appendFile Append-Datei.
     * @return array Recovery-Status.
     */
    public static function recoverWal(string $appendFile): array {
        $entries = self::readWal($appendFile);

        if (empty($entries)) {
            return ["ok" => true, "replayed" => 0, "dangling" => 0];
        }

        $states = [];
        $ops = [];

        foreach ($entries as $entry) {
            $tx = (string)$entry["tx"];
            $state = (string)$entry["state"];
            $states[$tx][$state] = true;
            $ops[$tx] = $entry["op"];
        }

        $existing = [];

        if (is_file($appendFile)) {
            $handle = @fopen($appendFile, "r");

            if ($handle) {
                try {
                    while (!feof($handle)) {
                        $line = fgets($handle);
                        if ($line === false) break;

                        $json = self::decodeLine($line);
                        if ($json === null) continue;
                        $existing[hash("sha256", $json)] = true;
                    }
                } finally {
                    @fclose($handle);
                }
            }
        }

        $replayed = 0;
        $dangling = 0;

        foreach ($states as $tx => $txStates) {
            if (!empty($txStates["committed"]) && isset($ops[$tx])) {
                $json = json_encode($ops[$tx], 0);
                if ($json === false) continue;

                $hash = hash("sha256", $json);

                if (!isset($existing[$hash])) {
                    self::appendLine($appendFile, self::encodeLine($json) . "\n");
                    $existing[$hash] = true;
                    $replayed++;
                }
            } elseif (!empty($txStates["prepared"]) && empty($txStates["failed"])) {
                $dangling++;
            }
        }

        return ["ok" => true, "replayed" => $replayed, "dangling" => $dangling];
    }

    /**
     * Stellt einen Snapshot wieder her.
     * @param string $dataFile Tabellen-Datei.
     * @param string $snapshotId Snapshot-ID.
     * @return bool true bei Erfolg.
     */
    public static function restoreSnapshot(string $dataFile, string $snapshotId): bool {
        $snapshotId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $snapshotId);

        if ($snapshotId === "") {
            return false;
        }

        $dir = dirname($dataFile) . "/" . self::SNAPSHOT_DIR . "/" . basename($dataFile) . "/" . $snapshotId;

        if (!is_dir($dir)) {
            return false;
        }

        $ok = true;
        $items = scandir($dir) ?: [];

        foreach ($items as $item) {
            if ($item === "." || $item === ".." || $item === "manifest.json") continue;

            $src = $dir . "/" . $item;
            $target = dirname($dataFile) . "/" . $item;

            if (is_file($src)) {
                $payload = @file_get_contents($src);

                if ($payload === false || !self::atomicWrite($target, $payload)) {
                    $ok = false;
                }
            }
        }

        return $ok;
    }

    /**
     * Liest einen Index und gibt die Row-IDs für einen Wert zurück.
     * @param string $dataFile Tabellen-Datei.
     * @param string $column Spalte.
     * @param mixed $value Suchwert.
     * @return array Row-IDs.
     */
    public static function indexLookup(string $dataFile, string $column, mixed $value): array {
        $file = self::indexFile($dataFile, $column);

        if (!is_file($file)) {
            return [];
        }

        $payload = @file_get_contents($file);

        if ($payload === false || $payload === "") {
            return [];
        }

        if (class_exists("Vars") && method_exists("Vars", "crypt_data") && Vars::crypt_data() && class_exists("Crypt")) {
            $decoded = Crypt::decode($payload);
            if (is_string($decoded)) {
                $payload = $decoded;
            }
        }

        $idx = json_decode($payload, true);

        if (!is_array($idx) || !isset($idx["map"]) || !is_array($idx["map"])) {
            return [];
        }

        $key = self::indexKey($value);
        return array_values(array_map("intval", $idx["map"][$key] ?? []));
    }

    /**
     * Synchronisiert ein Verzeichnis, sofern möglich.
     * @param string $dir Verzeichnis.
     * @return void
     */
    private static function syncDir(string $dir): void {
        if (!function_exists("fsync")) {
            return;
        }

        $handle = @fopen($dir, "r");

        if ($handle) {
            @fsync($handle);
            @fclose($handle);
        }
    }
}
