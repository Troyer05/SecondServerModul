<?php

class SQL {
    /**
     * @var mixed $pdo PDO SQL Connection
     */
    public static $pdo;

    /**
     * Stellt die Verbindung zum SQL Server her
     * @return bool true wenn es keine Probleme gab
     */
    public static function connect(): bool {
        if (Vars::__DEV__()) {
            $dsn = "mysql:host=" . Vars::sql_dev_server();
            $dsn .= ";dbname=" . Vars::sql_dev_database();
            $u = Vars::sql_dev_user();
            $p = Vars::sql_dev_password();
        } else {
            $dsn = "mysql:host=" . Vars::sql_server();
            $dsn .= ";dbname=" . Vars::sql_database();
            $u = Vars::sql_user();
            $p = Vars::sql_password();
        }

        try {
            self::$pdo = new PDO($dsn, $u, $p);
            return true;
        } catch (PDOException $e) {
            echo "Error when connecting to SQL database: " . $e;
            return false;
        }
    }

    /**
     * Sendet eine SQL Abfrage an den SQL Server
     * @param string $query die zu sendene SQL Abfrage
     * @return mixed die Antwort des SQL Servers / Das Ergebnis der SQL Abfrage
     */
    public static function sendSQL(string $query): mixed {
        $ergebnis = self::$pdo->query($query);
    
        if ($ergebnis) {
            return $ergebnis->fetchAll(PDO::FETCH_ASSOC);
        }
    
        return false;
    }

    /**
     * Einfacher Select Befehl
     * @param string $table Name der Tabelle
     * @param string $select Was sie Selectieren wollen (Optional, Standard: *)
     * @param string $where (Optional)
     * @param string $is (Optional, @example $where = "name" $is = "Max")
     * @return mixed Ergebnis der SELECT Abfrage
     */
    public static function select(string $table, string $select = "*", string $where = "", string $is = ""): mixed {
        if ($where != "") {
            $query = "SELECT $select FROM $table WHERE $where = $is";
        } else {
            $query = "SELECT $select FROM $table";
        }

        return self::sendSQL($query);
    }

    /**
     * Einfacher Insert-Befehl
     * @param string $table Name der Tabelle
     * @param array $data Daten zum Einfügen (assoziatives Array)
     * @return mixed Ergebnis des Insert-Befehls
     */
    public static function insert(string $table, array $data): mixed {
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";
        $query = "INSERT INTO $table ($columns) VALUES ($values)";

        return self::sendSQL($query);
    }

    /**
     * Einfacher Update-Befehl
     * @param string $table Name der Tabelle
     * @param array $data Neue Daten (assoziatives Array)
     * @param string $where Spalte für die Bedingung
     * @param mixed $is Wert für die Bedingung
     * @return mixed Ergebnis des Update-Befehls
     */
    public static function update(string $table, array $data, string $where, mixed $is): mixed {
        $set = '';

        foreach ($data as $column => $value) {
            $set .= "$column = '$value', ";
        }

        $set = rtrim($set, ', ');
        $query = "UPDATE $table SET $set WHERE $where = '$is'";
        
        return self::sendSQL($query);
    }

    /**
     * Einfacher Delete-Befehl
     * @param string $table Name der Tabelle
     * @param string $where Spalte für die Bedingung
     * @param mixed $is Wert für die Bedingung
     * @return mixed Ergebnis des Delete-Befehls
     */
    public static function delete(string $table, string $where, mixed $is): mixed {
        $query = "DELETE FROM $table WHERE $where = '$is'";
        return self::sendSQL($query);
    }
}
