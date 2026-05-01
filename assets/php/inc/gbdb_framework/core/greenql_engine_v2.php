<?php

class GreenQLv2 {
    private static string $driver = "GBDB";
    private static string $instance = "";

    /**
     * Bereinigt Namen für Datenbanken, Tabellen, Felder und Instanzen.
     * @param string $name Übergabewert.
     * @return string Rückgabewert.
     */
    public static function cleanName(string $name): string {
        $name = trim($name);
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?? '';
    }

    /**
     * Gibt den aktiven Datenbank-Treiber zurück.
     * @return string Rückgabewert.
     */
    private static function db(): string {
        return self::$driver;
    }

    /**
     * Synchronisiert den aktiven Treiber anhand des Contextes.
     * @param array $ctx Übergabewert.
     * @return void Rückgabewert.
     */
    private static function syncInstance(array $ctx = []): void {
        $instance = self::cleanName((string)($ctx["instance"] ?? self::$instance));

        if ($instance !== "" && class_exists("GBDBv2")) {
            self::$driver = "GBDBv2";
            self::$instance = $instance;
            GBDBv2::setInstance($instance);
            return;
        }

        self::$driver = "GBDB";
    }

    /**
     * Aktiviert eine GBDBv2-Instanz.
     * @param string $instance Übergabewert.
     * @param array $ctx Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function useInstance(string $instance, array &$ctx = []): bool {
        $instance = self::cleanName($instance);

        if ($instance === "" || !class_exists("GBDBv2")) {
            return false;
        }

        self::$driver = "GBDBv2";
        self::$instance = $instance;

        GBDBv2::setInstance($instance);

        $ctx["instance"] = $instance;

        return true;
    }

    /**
     * Entfernt Quotes und wandelt einfache Werte um.
     * @param string $value Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function unquote(string $value): mixed {
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return stripcslashes(substr($value, 1, -1));
            }
        }

        $low = strtolower($value);

        if ($low === "true") return true;
        if ($low === "false") return false;
        if ($low === "null") return null;
        if (is_numeric($value)) return $value + 0;

        return $value;
    }

    /**
     * Entfernt Kommentare aus einem Script.
     * @param string $script Übergabewert.
     * @return string Rückgabewert.
     */
    public static function stripComments(string $script): string {
        $lines = preg_split('/\r\n|\r|\n/', $script);
        $out = [];

        foreach ($lines as $line) {
            $clean = "";
            $quote = "";
            $len = strlen((string)$line);

            for ($i = 0; $i < $len; $i++) {
                $ch = $line[$i];
                $next = $i + 1 < $len ? $line[$i + 1] : "";

                if ($quote !== "") {
                    if ($ch === "\\" && $i + 1 < $len) {
                        $clean .= $ch . $line[$i + 1];
                        $i++;
                        continue;
                    }

                    if ($ch === $quote) {
                        $quote = "";
                    }

                    $clean .= $ch;
                    continue;
                }

                if ($ch === '"' || $ch === "'") {
                    $quote = $ch;
                    $clean .= $ch;
                    continue;
                }

                if ($ch === "#") {
                    break;
                }

                if ($ch === "/" && $next === "/") {
                    break;
                }

                $clean .= $ch;
            }

            $out[] = rtrim($clean);
        }

        return trim(implode("\n", $out));
    }

    /**
     * Trennt ein Script in einzelne Befehle.
     * @param string $script Übergabewert.
     * @return array Rückgabewert.
     */
    public static function splitCommands(string $script): array {
        $script = self::stripComments($script);
        $commands = [];
        $buffer = "";
        $quote = "";
        $len = strlen($script);

        for ($i = 0; $i < $len; $i++) {
            $ch = $script[$i];

            if ($quote !== "") {
                if ($ch === "\\" && $i + 1 < $len) {
                    $buffer .= $ch . $script[$i + 1];
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = "";
                }

                $buffer .= $ch;
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $buffer .= $ch;
                continue;
            }

            if ($ch === ";") {
                $command = trim($buffer);

                if ($command !== "") {
                    $commands[] = $command;
                }

                $buffer = "";
                continue;
            }

            $buffer .= $ch;
        }

        $buffer = trim($buffer);

        if ($buffer !== "") {
            $commands[] = $buffer;
        }

        return $commands;
    }

    /**
     * Wertet einen Wert aus.
     * @param string $value Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function evaluateValue(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if ($value === "") {
            return "";
        }

        if (preg_match('/^param\(("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\')\)$/i', $value, $m)) {
            $key = (string)self::unquote((string)$m[1]);
            return $params[$key] ?? null;
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value) && array_key_exists($value, $vars)) {
            return $vars[$value];
        }

        return self::unquote($value);
    }

    /**
     * Löst einen Namen aus Token oder Variable auf.
     * @param string $token Übergabewert.
     * @param array $vars Übergabewert.
     * @return string Rückgabewert.
     */
    public static function resolveNameToken(string $token, array $vars = []): string {
        $token = trim($token);

        if ($token === "") {
            return "";
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $token) && array_key_exists($token, $vars)) {
            return self::cleanName((string)$vars[$token]);
        }

        return self::cleanName($token);
    }

    /**
     * Parst eine kommagetrennte Liste.
     * @param string $raw Übergabewert.
     * @param array $vars Übergabewert.
     * @return array Rückgabewert.
     */
    public static function parseList(string $raw, array $vars = []): array {
        $parts = preg_split('/\s*,\s*/', trim($raw));
        $out = [];

        foreach ($parts as $part) {
            $part = self::resolveNameToken((string)$part, $vars);

            if ($part === "") {
                continue;
            }

            $out[] = $part;
        }

        return array_values(array_filter($out));
    }

    /**
     * Parst Zuweisungen.
     * @param string $raw Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function parseAssignments(string $raw, array $vars = [], array $params = []): array {
        $raw = trim($raw);

        if ($raw === "") {
            return [];
        }

        preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\'|[^,]+)(?:,|$)/', $raw, $matches, PREG_SET_ORDER);

        $out = [];

        foreach ($matches as $match) {
            $key = self::cleanName((string)$match[1]);

            if ($key === "" || $key === "id") {
                continue;
            }

            $out[$key] = self::evaluateValue(trim((string)$match[2]), $vars, $params);
        }

        return $out;
    }

    /**
     * Parst WHERE-Bedingungen.
     * @param string $raw Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return ?array Rückgabewert.
     */
    public static function parseWhere(string $raw, array $vars = [], array $params = []): ?array {
        $raw = trim($raw);

        if ($raw === "") {
            return null;
        }

        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(==|=|!=|>=|<=|>|<|~=)\s*(.+)$/', $raw, $m)) {
            return null;
        }

        return [
            "field" => self::cleanName((string)$m[1]),
            "op" => (string)$m[2],
            "value" => self::evaluateValue((string)$m[3], $vars, $params)
        ];
    }

    /**
     * Prüft, ob eine Zeile zur WHERE-Bedingung passt.
     * @param array $row Übergabewert.
     * @param ?array $where Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function rowMatch(array $row, ?array $where): bool {
        if ($where === null) {
            return true;
        }

        $field = $where["field"];
        $op = $where["op"];
        $value = $where["value"];
        $left = $row[$field] ?? null;

        switch ($op) {
            case "=":
            case "==":
                return $left == $value;

            case "!=":
                return $left != $value;

            case ">":
                return $left > $value;

            case "<":
                return $left < $value;

            case ">=":
                return $left >= $value;

            case "<=":
                return $left <= $value;

            case "~=":
                return mb_stripos((string)$left, (string)$value) !== false;
        }

        return false;
    }

    /**
     * Sortiert Zeilen.
     * @param array $rows Übergabewert.
     * @param ?string $field Übergabewert.
     * @param string $dir Übergabewert.
     * @return void Rückgabewert.
     */
    public static function sortRows(array &$rows, ?string $field, string $dir = "ASC"): void {
        if ($field === null || $field === "") {
            return;
        }

        usort($rows, function ($a, $b) use ($field, $dir) {
            $av = $a[$field] ?? "";
            $bv = $b[$field] ?? "";

            if (is_numeric($av) && is_numeric($bv)) {
                $cmp = $av <=> $bv;
            } else {
                $cmp = strnatcasecmp((string)$av, (string)$bv);
            }

            return strtoupper($dir) === "DESC" ? -$cmp : $cmp;
        });
    }

    /**
     * Holt Tabellenzeilen aus dem aktiven Treiber.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getRows(string $db, string $table): array {
        $driver = self::db();
        $rows = $driver::getData($db, $table);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Holt Tabellenfelder aus dem aktiven Treiber.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @return array Rückgabewert.
     */
    public static function getTableKeys(string $db, string $table): array {
        $driver = self::db();
        $keys = $driver::getKeys($db, $table);

        if (!empty($keys)) {
            return $keys;
        }

        $rows = self::getRows($db, $table);

        if (!empty($rows) && is_array($rows[0])) {
            return array_keys($rows[0]);
        }

        return [];
    }

    /**
     * Selektiert Tabellenzeilen.
     * @param string $db Übergabewert.
     * @param string $table Übergabewert.
     * @param array $columns Übergabewert.
     * @param ?array $where Übergabewert.
     * @param ?string $sortField Übergabewert.
     * @param string $sortDir Übergabewert.
     * @param ?int $limit Übergabewert.
     * @return array Rückgabewert.
     */
    public static function selectRows(
        string $db,
        string $table,
        array $columns = ["*"],
        ?array $where = null,
        ?string $sortField = null,
        string $sortDir = "ASC",
        ?int $limit = null
    ): array {
        $rows = self::getRows($db, $table);

        $rows = array_values(array_filter($rows, function ($row) use ($where) {
            return is_array($row) && self::rowMatch($row, $where);
        }));

        self::sortRows($rows, $sortField, $sortDir);

        if ($limit !== null && $limit >= 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        $keys = self::getTableKeys($db, $table);

        if ($columns !== ["*"]) {
            $rows = array_map(function ($row) use ($columns) {
                $tmp = [];

                foreach ($columns as $col) {
                    $tmp[$col] = $row[$col] ?? "";
                }

                return $tmp;
            }, $rows);

            $keys = $columns;
        }

        return [
            "keys" => $keys,
            "rows" => $rows
        ];
    }

    /**
     * Gibt Statistiken zu einer Base zurück.
     * @param string $db Übergabewert.
     * @return array Rückgabewert.
     */
    public static function stats(string $db): array {
        $driver = self::db();
        $tables = $driver::listTables($db);
        $rows = 0;

        foreach ($tables as $table) {
            $data = $driver::getData($db, $table);

            if (is_array($data)) {
                $rows += count($data);
            }
        }

        return [
            "tables" => count($tables),
            "rows" => $rows
        ];
    }

    /**
     * Führt einen einzelnen GreenQL-Befehl aus.
     * @param string $command Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function command(string $command, array &$ctx = [], array &$vars = [], array $params = []): array {
        $command = trim($command);

        self::syncInstance($ctx);
        $driver = self::db();

        if ($command === "") {
            return [
                "ok" => true,
                "message" => ""
            ];
        }

        if (preg_match('/^(?:DECLARE|DECALRE)\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/i', $command, $m)) {
            $name = self::cleanName((string)$m[1]);
            $vars[$name] = self::evaluateValue((string)$m[2], $vars, $params);

            return [
                "ok" => true,
                "message" => "Variable gesetzt: " . $name,
                "ctx" => $ctx,
                "vars" => $vars
            ];
        }

        if (preg_match('/^USE\s+INSTANCE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $instance = self::resolveNameToken((string)$m[1], $vars);

            if (!self::useInstance($instance, $ctx)) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht aktiviert werden.",
                    "ctx" => $ctx
                ];
            }

            return [
                "ok" => true,
                "message" => "Instanz aktiv: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^ROOT\s+INSTANCE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $instance = self::resolveNameToken((string)$m[1], $vars);

            if (!self::useInstance($instance, $ctx)) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht aktiviert werden.",
                    "ctx" => $ctx
                ];
            }

            return [
                "ok" => true,
                "message" => "Instanz fokussiert: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^SHOW\s+INSTANCES$/i', $command)) {
            if (!class_exists("GBDBv2")) {
                return [
                    "ok" => false,
                    "message" => "GBDBv2 ist nicht verfügbar.",
                    "ctx" => $ctx
                ];
            }

            $rows = [];
            $oldInstance = GBDBv2::getInstance();
            $oldCtxInstance = $ctx["instance"] ?? "";

            foreach (GBDBv2::listInstances() as $instance) {
                GBDBv2::setInstance($instance);
                self::$driver = "GBDBv2";
                self::$instance = $instance;

                $dbs = GBDBv2::listDBs();
                $tables = 0;
                $records = 0;

                foreach ($dbs as $db) {
                    $stats = self::stats($db);
                    $tables += $stats["tables"];
                    $records += $stats["rows"];
                }

                $rows[] = [
                    "instance" => $instance,
                    "bases" => count($dbs),
                    "tables" => $tables,
                    "rows" => $records
                ];
            }

            GBDBv2::setInstance($oldInstance);

            if ($oldCtxInstance !== "") {
                self::useInstance((string)$oldCtxInstance, $ctx);
            } else {
                self::$driver = "GBDB";
                self::$instance = "";
            }

            return [
                "ok" => true,
                "message" => count($rows) . " Instanzen gefunden.",
                "keys" => ["instance", "bases", "tables", "rows"],
                "rows" => $rows,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^GROW\s+INSTANCE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            if (!class_exists("GBDBv2")) {
                return [
                    "ok" => false,
                    "message" => "GBDBv2 ist nicht verfügbar.",
                    "ctx" => $ctx
                ];
            }

            $instance = self::resolveNameToken((string)$m[1], $vars);

            if ($instance === "") {
                return [
                    "ok" => false,
                    "message" => "Ungültiger Instanz-Name.",
                    "ctx" => $ctx
                ];
            }

            $ok = GBDBv2::createInstance($instance);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht erstellt werden.",
                    "ctx" => $ctx
                ];
            }

            self::useInstance($instance, $ctx);

            return [
                "ok" => true,
                "message" => "Instanz erstellt: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DROP\s+INSTANCE\s+([a-zA-Z0-9_\-]+)(?:\s+(FORCE))?$/i', $command, $m)) {
            if (!class_exists("GBDBv2")) {
                return [
                    "ok" => false,
                    "message" => "GBDBv2 ist nicht verfügbar.",
                    "ctx" => $ctx
                ];
            }

            $instance = self::resolveNameToken((string)$m[1], $vars);
            $force = strtoupper((string)($m[2] ?? "")) === "FORCE";

            if ($instance === "") {
                return [
                    "ok" => false,
                    "message" => "Ungültiger Instanz-Name.",
                    "ctx" => $ctx
                ];
            }

            $ok = GBDBv2::deleteInstance($instance, $force);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht gelöscht werden. Sie muss leer sein oder FORCE genutzt werden.",
                    "ctx" => $ctx
                ];
            }

            if (($ctx["instance"] ?? "") === $instance) {
                unset($ctx["instance"]);
                self::$instance = "";
                self::$driver = "GBDB";
            }

            return [
                "ok" => true,
                "message" => "Instanz gelöscht: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^ROOT\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $ctx["db"] = self::resolveNameToken((string)$m[1], $vars);
            $ctx["table"] = "";

            return [
                "ok" => true,
                "message" => "Base fokussiert: " . $ctx["db"],
                "refresh" => true,
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^BRANCH\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $ctx["table"] = self::resolveNameToken((string)$m[1], $vars);

            return [
                "ok" => true,
                "message" => "Tabelle fokussiert: " . $ctx["table"],
                "refresh" => true,
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^SHOW\s+BASES$/i', $command)) {
            $rows = [];

            foreach ($driver::listDBs() as $db) {
                $stats = self::stats($db);

                $rows[] = [
                    "base" => $db,
                    "tables" => $stats["tables"],
                    "rows" => $stats["rows"]
                ];
            }

            return [
                "ok" => true,
                "message" => count($rows) . " Basen gefunden.",
                "keys" => ["base", "tables", "rows"],
                "rows" => $rows,
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^SHOW\s+TABLES(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $db = self::resolveNameToken((string)($m[1] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "") {
                return [
                    "ok" => false,
                    "message" => "Keine Base aktiv.",
                    "ctx" => $ctx
                ];
            }

            $rows = [];

            foreach ($driver::listTables($db) as $table) {
                $rows[] = [
                    "table" => $table,
                    "fields" => count(self::getTableKeys($db, $table)),
                    "rows" => count(self::getRows($db, $table))
                ];
            }

            $ctx["db"] = $db;

            return [
                "ok" => true,
                "message" => count($rows) . " Tabellen in " . $db . ".",
                "keys" => ["table", "fields", "rows"],
                "rows" => $rows,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^GROW\s+BASE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $db = self::resolveNameToken((string)$m[1], $vars);

            if ($db === "") {
                return [
                    "ok" => false,
                    "message" => "Ungültiger Base-Name.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::createDatabase($db);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Base konnte nicht erstellt werden.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = "";

            return [
                "ok" => true,
                "message" => "Base erstellt: " . $db,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DROP\s+BASE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $db = self::resolveNameToken((string)$m[1], $vars);
            $ok = $driver::deleteDatabase($db);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Base konnte nicht gelöscht werden. Sie muss leer sein.",
                    "ctx" => $ctx
                ];
            }

            if (($ctx["db"] ?? "") === $db) {
                $ctx["db"] = "";
                $ctx["table"] = "";
            }

            return [
                "ok" => true,
                "message" => "Base gelöscht: " . $db,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^GROW\s+TABLE\s+([a-zA-Z0-9_\-]+)\s*\(([^\)]+)\)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $cols = self::parseList((string)$m[2], $vars);
            $db = self::resolveNameToken((string)($m[3] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "") {
                return [
                    "ok" => false,
                    "message" => "Keine Base aktiv.",
                    "ctx" => $ctx
                ];
            }

            if ($table === "" || empty($cols)) {
                return [
                    "ok" => false,
                    "message" => "Tabelle oder Felder ungültig.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::createTable($db, $table, $cols);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Tabelle konnte nicht erstellt werden.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Tabelle erstellt: " . $table,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^ALTER\s+TABLE\s+([a-zA-Z0-9_\-]+)\s+ADD(?:\s+COLUMN)?\s+([a-zA-Z0-9_\-]+)(?:\s+(?:DEFAULT\s+)?(.+?))?(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $column = self::resolveNameToken((string)$m[2], $vars);
            $default = isset($m[3]) ? self::evaluateValue((string)$m[3], $vars, $params) : "";
            $db = self::resolveNameToken((string)($m[4] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if ($column === "" || $column === "id") {
                return [
                    "ok" => false,
                    "message" => "Spaltenname ungültig.",
                    "ctx" => $ctx
                ];
            }

            $keysBefore = self::getTableKeys($db, $table);
            $existsBefore = in_array($column, $keysBefore, true);
            $ok = $driver::addColumn($db, $table, $column, $default);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Spalte konnte nicht hinzugefügt werden.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => $existsBefore
                    ? "Spalte bereits vorhanden: " . $column
                    : "Spalte hinzugefügt: " . $column,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DROP\s+TABLE\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken((string)($m[2] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::deleteTable($db, $table);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Tabelle konnte nicht gelöscht werden.",
                    "ctx" => $ctx
                ];
            }

            if (($ctx["db"] ?? "") === $db && ($ctx["table"] ?? "") === $table) {
                $tables = $driver::listTables($db);
                $ctx["table"] = $tables[0] ?? "";
            }

            return [
                "ok" => true,
                "message" => "Tabelle gelöscht: " . $table,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DESCRIBE\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken((string)($m[2] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $keys = self::getTableKeys($db, $table);
            $rows = [];

            foreach ($keys as $key) {
                $rows[] = [
                    "field" => $key,
                    "kind" => $key === "id" ? "auto" : "mixed"
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Schema geladen: " . $table,
                "keys" => ["field", "kind"],
                "rows" => $rows,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^PACK\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken((string)($m[2] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::compactTable($db, $table);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Compact fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Tabelle gepackt: " . $table,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^PEEK\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken((string)($m[2] ?? ($ctx["db"] ?? "")), $vars);
            $limit = isset($m[3]) ? (int)$m[3] : 50;

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            $result = self::selectRows($db, $table, ["*"], null, "id", "ASC", $limit);

            return [
                "ok" => true,
                "message" => "Vorschau: " . $table,
                "keys" => $result["keys"],
                "rows" => $result["rows"],
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^PICK\s+(.+?)\s+FROM\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+WHERE\s+(.+?))?(?:\s+SORT\s+([a-zA-Z0-9_\-]+)\s+(ASC|DESC))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $m)) {
            $colsRaw = trim((string)$m[1]);
            $table = self::resolveNameToken((string)$m[2], $vars);
            $db = self::resolveNameToken((string)($m[3] ?? ($ctx["db"] ?? "")), $vars);
            $where = isset($m[4]) ? self::parseWhere((string)$m[4], $vars, $params) : null;
            $sortField = isset($m[5]) ? self::resolveNameToken((string)$m[5], $vars) : null;
            $sortDir = strtoupper((string)($m[6] ?? "ASC"));
            $limit = isset($m[7]) ? (int)$m[7] : 50;
            $columns = $colsRaw === "*" ? ["*"] : self::parseList($colsRaw, $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            $result = self::selectRows(
                $db,
                $table,
                empty($columns) ? ["*"] : $columns,
                $where,
                $sortField,
                $sortDir,
                $limit
            );

            return [
                "ok" => true,
                "message" => count($result["rows"]) . " Treffer aus " . $table . ".",
                "keys" => $result["keys"],
                "rows" => $result["rows"],
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^SEED\s+([a-zA-Z0-9_\-]+)\s+WITH\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $assignments = self::parseAssignments((string)$m[2], $vars, $params);
            $db = self::resolveNameToken((string)($m[3] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if (empty($assignments)) {
                return [
                    "ok" => false,
                    "message" => "Keine Daten gefunden.",
                    "ctx" => $ctx
                ];
            }

            $id = $driver::insertData($db, $table, $assignments);

            if ($id <= 0) {
                return [
                    "ok" => false,
                    "message" => "Insert fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Datensatz angelegt. Neue ID: " . $id,
                "insert_id" => $id,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^RESHAPE\s+([a-zA-Z0-9_\-]+)\s+WITH\s+(.+?)\s+WHERE\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $assignments = self::parseAssignments((string)$m[2], $vars, $params);
            $where = self::parseWhere((string)$m[3], $vars, $params);
            $db = self::resolveNameToken((string)($m[4] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if (empty($assignments) || $where === null) {
                return [
                    "ok" => false,
                    "message" => "WITH oder WHERE ungültig.",
                    "ctx" => $ctx
                ];
            }

            if (!in_array($where["op"], ["=", "=="], true)) {
                return [
                    "ok" => false,
                    "message" => "RESHAPE unterstützt aktuell nur WHERE feld = wert.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::editData($db, $table, $where["field"], $where["value"], $assignments);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Update fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Datensatz aktualisiert.",
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^ERASE\s+FROM\s+([a-zA-Z0-9_\-]+)\s+WHERE\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $where = self::parseWhere((string)$m[2], $vars, $params);
            $db = self::resolveNameToken((string)($m[3] ?? ($ctx["db"] ?? "")), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if ($where === null) {
                return [
                    "ok" => false,
                    "message" => "WHERE ungültig.",
                    "ctx" => $ctx
                ];
            }

            if (!in_array($where["op"], ["=", "=="], true)) {
                return [
                    "ok" => false,
                    "message" => "ERASE unterstützt aktuell nur WHERE feld = wert.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::deleteData($db, $table, $where["field"], $where["value"]);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Löschen fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Datensatz entfernt.",
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        return [
            "ok" => false,
            "message" => "Befehl nicht erkannt: " . $command,
            "ctx" => $ctx
        ];
    }

    /**
     * Führt ein komplettes GreenQL-Script aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function run(string $script, array $ctx = [], array $params = []): array {
        self::syncInstance($ctx);

        $commands = self::splitCommands(trim($script));
        $messages = [];
        $results = [];
        $lastKeys = [];
        $lastRows = [];
        $refresh = false;
        $okAll = true;
        $vars = [];

        foreach ($commands as $command) {
            $command = trim((string)$command);

            if ($command === "") {
                continue;
            }

            $result = self::command($command, $ctx, $vars, $params);

            if (($result["message"] ?? "") !== "") {
                $messages[] = [
                    "ok" => (bool)($result["ok"] ?? false),
                    "text" => (string)$result["message"]
                ];
            }

            if (isset($result["keys"], $result["rows"])) {
                $lastKeys = $result["keys"];
                $lastRows = $result["rows"];

                $results[] = [
                    "command" => $command,
                    "keys" => $result["keys"],
                    "rows" => $result["rows"]
                ];
            }

            if (!empty($result["refresh"])) {
                $refresh = true;
            }

            if (empty($result["ok"])) {
                $okAll = false;
                break;
            }
        }

        return [
            "ok" => $okAll,
            "messages" => $messages,
            "results" => $results,
            "keys" => $lastKeys,
            "rows" => $lastRows,
            "ctx" => [
                "instance" => self::cleanName((string)($ctx["instance"] ?? self::$instance)),
                "db" => self::cleanName((string)($ctx["db"] ?? "")),
                "table" => self::cleanName((string)($ctx["table"] ?? ""))
            ],
            "vars" => $vars,
            "refresh" => $refresh
        ];
    }
}
