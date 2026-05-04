<?php

trait GBDBv2_TransactionTrait {

    /**
     * Prüft ob eine Transaktion aktiv ist.
     * @return bool Rückgabewert.
     */
    private static function inTransaction(): bool {
        return self::$txActive && !self::$txCommitting;
    }


    /**
     * Reserviert eine ID innerhalb einer laufenden Transaktion.
     * @param string $database Datenbank.
     * @param string $table Tabelle.
     * @param array $data Nutzdaten.
     * @return int reservierte ID.
     */
    private static function reserveTransactionId(string $database, string $table, array $data): int {
        if (isset($data["id"]) && (int)$data["id"] > 0) {
            return (int)$data["id"];
        }

        $next = self::nextID($database, $table);

        foreach (self::$txOps as $op) {
            if (($op["type"] ?? "") !== "insert") continue;
            if (($op["instance"] ?? "") !== self::getInstance()) continue;
            if (($op["db"] ?? "") !== $database || ($op["table"] ?? "") !== $table) continue;
            $next = max($next, (int)($op["id"] ?? 0) + 1);
        }

        return max(1, $next);
    }


    /**
     * Erzeugt einen Transaktions-Snapshot für alle betroffenen Tabellen.
     * @return array Snapshot-Mapping.
     */
    private static function createTransactionSnapshots(): array {
        $snapshots = [];

        foreach (self::$txOps as $op) {
            $instance = (string)($op["instance"] ?? self::getInstance());
            $db = (string)($op["db"] ?? "");
            $table = (string)($op["table"] ?? "");
            if ($db === "" || $table === "") continue;

            $key = $instance . "." . $db . "." . $table;
            if (isset($snapshots[$key])) continue;

            $snapshots[$key] = self::withInstance($instance, function () use ($instance, $db, $table) {
                return [
                    "instance" => $instance,
                    "db" => $db,
                    "table" => $table,
                    "snapshot" => self::snapshot($db, $table, "before_tx_" . self::$txId)
                ];
            });
        }

        return $snapshots;
    }


    /**
     * Stellt Transaktions-Snapshots wieder her.
     * @param array $snapshots Snapshot-Mapping.
     * @return void
     */
    private static function restoreTransactionSnapshots(array $snapshots): void {
        foreach ($snapshots as $item) {
            $instance = (string)($item["instance"] ?? self::getInstance());
            $db = (string)($item["db"] ?? "");
            $table = (string)($item["table"] ?? "");
            $snapshot = (string)($item["snapshot"] ?? "");

            if ($db !== "" && $table !== "" && $snapshot !== "") {
                self::withInstance($instance, function () use ($db, $table, $snapshot) {
                    self::restoreSnapshot($db, $table, $snapshot);
                });
            }
        }
    }


    /**
     * Startet eine GBDBv2-Transaktion.
     * @return bool true bei Erfolg.
     */
    public static function begin(): bool {
        if (self::$txActive) {
            return false;
        }

        self::$txActive = true;
        self::$txCommitting = false;
        self::$txId = "tx_" . bin2hex(random_bytes(8));
        self::$txOps = [];

        return true;
    }


    /**
     * Schreibt alle Transaktions-Operationen gesammelt fest.
     * @return bool true bei Erfolg.
     */
    public static function commit(): bool {
        if (!self::$txActive) {
            return false;
        }

        $ops = self::$txOps;
        $snapshots = self::createTransactionSnapshots();
        self::$txCommitting = true;
        $ok = true;

        foreach ($ops as $op) {
            $instance = (string)($op["instance"] ?? self::getInstance());
            $type = (string)($op["type"] ?? "");
            $db = (string)($op["db"] ?? "");
            $table = (string)($op["table"] ?? "");

            $ok = (bool)self::withInstance($instance, function () use ($type, $db, $table, $op) {
                if ($type === "insert") {
                    return self::insertData($db, $table, (array)($op["data"] ?? [])) > 0;
                }

                if ($type === "edit") {
                    return self::editData($db, $table, $op["where"] ?? "", $op["is"] ?? "", (array)($op["data"] ?? []));
                }

                if ($type === "delete") {
                    return self::deleteData($db, $table, $op["where"] ?? "", $op["is"] ?? "");
                }

                return false;
            });

            if (!$ok) {
                break;
            }
        }

        if (!$ok) {
            self::restoreTransactionSnapshots($snapshots);
        }

        self::$txActive = false;
        self::$txCommitting = false;
        self::$txId = "";
        self::$txOps = [];

        return $ok;
    }


    /**
     * Verwirft eine laufende Transaktion.
     * @return bool true bei Erfolg.
     */
    public static function rollback(): bool {
        if (!self::$txActive) {
            return false;
        }

        self::$txActive = false;
        self::$txCommitting = false;
        self::$txId = "";
        self::$txOps = [];

        return true;
    }


    /**
     * Gibt Transaktions-Statusdaten zurück.
     * @return array Statusdaten.
     */
    public static function transactionStatus(): array {
        return [
            "active" => self::$txActive,
            "id" => self::$txId,
            "ops" => count(self::$txOps),
            "instance" => self::getInstance()
        ];
    }
}
