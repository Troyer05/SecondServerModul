<?php

class Cache {

    /**
     * Lädt Daten aus dem Cache, bei Änderung automatisch neu.
     */
    public static function load(string $db, string $table, mixed $cache): array {
        if (!isset($_SESSION["cache"]["updates"])) {
            $_SESSION["cache"]["updates"] = [];
        }

        // Hole den aktuellen Update-Wert aus der globalen CACHE-Konstante
        $currentUpdate = null;
        foreach ($cache as $entry) {
            if ($entry["table"] === $table) {
                $currentUpdate = $entry["update"];
                break;
            }
        }

        if ($currentUpdate === null) {
            // Kein Cache-Eintrag → leeres Array
            return [];
        }

        // Prüfe, ob sich der Update-Wert geändert hat
        $needsUpdate = (
            !isset($_SESSION["cache"]["updates"][$table]) ||
            $_SESSION["cache"]["updates"][$table] !== $currentUpdate
        );

        if ($needsUpdate) {
            // Daten neu aus DB laden
            $_SESSION["cache"]["data"][$table] = GBDB::getData($db, $table);
            $_SESSION["cache"]["updates"][$table] = $currentUpdate;
        }

        return $_SESSION["cache"]["data"][$table];
    }

    /**
     * Erzwingt Cache-Aktualisierung für eine Tabelle
     * (z. B. nach Änderungen in GBDB)
     */
    public static function update(string $db, string $table): void {
        GBDB::editData($db, "cache", "table", $table, ["update" => uniqid()]);
    }

    /**
     * Löscht einen Cache-Eintrag aus der Session
     */
    public static function clear(string $table): void {
        unset($_SESSION["cache"]["data"][$table]);
        unset($_SESSION["cache"]["updates"][$table]);
    }

    /**
     * Kompletten Cache löschen (Session-Ebene)
     */
    public static function flush(): void {
        unset($_SESSION["cache"]);
    }

    /**
     * Prüft, ob Tabelle im Cache existiert
     */
    public static function exists(string $table): bool {
        return isset($_SESSION["cache"]["data"][$table]);
    }

    /**
     * Gibt alle Caches zurück (optional für Debug)
     */
    public static function all(): array {
        return $_SESSION["cache"]["data"] ?? [];
    }
}
