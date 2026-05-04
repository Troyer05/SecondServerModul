<?php

trait GreenQLv2_RowsTrait {

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
}
