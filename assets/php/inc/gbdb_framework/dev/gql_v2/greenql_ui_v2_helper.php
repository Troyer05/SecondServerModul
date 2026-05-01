<?php

declare(strict_types=1);

class GreenQLUIv2Helper {
    private const SYSTEM_INSTANCE = "__greenql_ui_v2_system";
    private const SYSTEM_DB = "system";
    private const USERS_TABLE = "users";

    /**
     * startet die session und initialisiert das interne benutzersystem.
     * @return void Rückgabewert.
     */
    public static function boot(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION["gqlui_v2_csrf"])) {
            $_SESSION["gqlui_v2_csrf"] = bin2hex(random_bytes(24));
        }

        self::ensureAuthStore();
    }

    /**
     * gibt den csrf token zurück.
     * @return string Rückgabewert.
     */
    public static function csrf(): string {
        return (string)($_SESSION["gqlui_v2_csrf"] ?? "");
    }

    /**
     * prüft den csrf token.
     * @param string $token Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function checkCsrf(string $token): bool {
        return hash_equals(self::csrf(), $token);
    }

    /**
     * gibt den internen system-instanznamen zurück.
     * @return string Rückgabewert.
     */
    public static function systemInstance(): string {
        return self::SYSTEM_INSTANCE;
    }

    /**
     * führt eine aktion im system-store aus und stellt die alte instanz wieder her.
     * @param callable $fn Übergabewert.
     * @return mixed Rückgabewert.
     */
    private static function inSystem(callable $fn): mixed {
        $old = class_exists("GBDBv2") ? GBDBv2::getInstance() : "default";

        try {
            GBDBv2::setInstance(self::SYSTEM_INSTANCE);
            return $fn();
        } finally {
            if (class_exists("GBDBv2")) {
                GBDBv2::setInstance($old);
            }
        }
    }

    /**
     * initialisiert die versteckte system-db.
     * @return void Rückgabewert.
     */
    private static function ensureAuthStore(): void {
        if (!class_exists("GBDBv2")) {
            return;
        }

        self::inSystem(function () {
            if (!in_array(self::SYSTEM_DB, GBDBv2::listDBs(), true)) {
                GBDBv2::createDatabase(self::SYSTEM_DB);
            }

            if (!in_array(self::USERS_TABLE, GBDBv2::listTables(self::SYSTEM_DB), true)) {
                GBDBv2::createTable(self::SYSTEM_DB, self::USERS_TABLE, [
                    "uid",
                    "username",
                    "password",
                    "role",
                    "active",
                    "instances",
                    "bases",
                    "created_at",
                    "last_login"
                ]);
                return;
            }

            $need = [
                "active" => "1",
                "instances" => "*",
                "bases" => "*"
            ];

            foreach ($need as $col => $default) {
                if (!in_array($col, GBDBv2::getKeys(self::SYSTEM_DB, self::USERS_TABLE), true)) {
                    GBDBv2::addColumn(self::SYSTEM_DB, self::USERS_TABLE, $col, $default);
                }
            }
        });
    }

    /**
     * prüft ob bereits benutzer existieren.
     * @return bool Rückgabewert.
     */
    public static function hasUsers(): bool {
        if (!class_exists("GBDBv2")) {
            return false;
        }

        return (bool)self::inSystem(function () {
            $users = GBDBv2::getData(self::SYSTEM_DB, self::USERS_TABLE);
            return is_array($users) && count($users) > 0;
        });
    }

    /**
     * legt einen benutzer an.
     * @param string $username Übergabewert.
     * @param string $password Übergabewert.
     * @param string $role Übergabewert.
     * @param string $instances Übergabewert.
     * @param string $bases Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function createUser(string $username, string $password, string $role = "admin", string $instances = "*", string $bases = "*"): bool {
        $username = self::clean($username);
        $role = self::normalizeRole($role);
        $instances = self::normalizeAccessValue($instances);
        $bases = self::normalizeAccessValue($bases);

        if ($username === "" || strlen($password) < 8) {
            return false;
        }

        return (bool)self::inSystem(function () use ($username, $password, $role, $instances, $bases) {
            if (GBDBv2::elementExists(self::SYSTEM_DB, self::USERS_TABLE, "username", $username)) {
                return false;
            }

            $id = GBDBv2::insertData(self::SYSTEM_DB, self::USERS_TABLE, [
                "uid" => bin2hex(random_bytes(12)),
                "username" => $username,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "role" => $role,
                "active" => "1",
                "instances" => $instances,
                "bases" => $bases,
                "created_at" => date("d.m.Y H:i"),
                "last_login" => ""
            ]);

            return $id > 0;
        });
    }

    /**
     * aktualisiert einen benutzer.
     * @param int $id Übergabewert.
     * @param array $data Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function updateUser(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }

        return (bool)self::inSystem(function () use ($id, $data) {
            $set = [];

            if (isset($data["role"])) {
                $set["role"] = self::normalizeRole((string)$data["role"]);
            }

            if (isset($data["active"])) {
                $set["active"] = ((string)$data["active"] === "1") ? "1" : "0";
            }

            if (isset($data["instances"])) {
                $set["instances"] = self::normalizeAccessValue((string)$data["instances"]);
            }

            if (isset($data["bases"])) {
                $set["bases"] = self::normalizeAccessValue((string)$data["bases"]);
            }

            if (!empty((string)($data["password"] ?? ""))) {
                if (strlen((string)$data["password"]) < 8) {
                    return false;
                }

                $set["password"] = password_hash((string)$data["password"], PASSWORD_DEFAULT);
            }

            if (empty($set)) {
                return false;
            }

            return GBDBv2::editData(self::SYSTEM_DB, self::USERS_TABLE, "id", $id, $set);
        });
    }

    /**
     * löscht einen benutzer.
     * @param int $id Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function deleteUser(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        $me = self::user();

        return (bool)self::inSystem(function () use ($id, $me) {
            $user = GBDBv2::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "id", $id);

            if (!is_array($user) || empty($user)) {
                return false;
            }

            if (($user["uid"] ?? "") === ($me["uid"] ?? "")) {
                return false;
            }

            return GBDBv2::deleteData(self::SYSTEM_DB, self::USERS_TABLE, "id", $id);
        });
    }

    /**
     * loggt einen benutzer ein.
     * @param string $username Übergabewert.
     * @param string $password Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function login(string $username, string $password): bool {
        $username = self::clean($username);

        if ($username === "" || $password === "") {
            return false;
        }

        return (bool)self::inSystem(function () use ($username, $password) {
            $user = GBDBv2::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "username", $username);

            if (!is_array($user) || empty($user)) {
                return false;
            }

            if ((string)($user["active"] ?? "1") !== "1") {
                return false;
            }

            if (!password_verify($password, (string)($user["password"] ?? ""))) {
                return false;
            }

            $_SESSION["gqlui_v2_user"] = self::publicUser($user);

            GBDBv2::editData(self::SYSTEM_DB, self::USERS_TABLE, "username", $username, [
                "last_login" => date("d.m.Y H:i")
            ]);

            return true;
        });
    }

    /**
     * loggt den benutzer aus.
     * @return void Rückgabewert.
     */
    public static function logout(): void {
        unset($_SESSION["gqlui_v2_user"]);
    }

    /**
     * gibt den aktuellen benutzer zurück.
     * @return array Rückgabewert.
     */
    public static function user(): array {
        $user = $_SESSION["gqlui_v2_user"] ?? [];
        return is_array($user) ? $user : [];
    }

    /**
     * lädt den aktuellen benutzer aus der datenbank neu.
     * @return array Rückgabewert.
     */
    public static function freshUser(): array {
        $current = self::user();
        $uid = (string)($current["uid"] ?? "");

        if ($uid === "") {
            return [];
        }

        $user = self::inSystem(function () use ($uid) {
            return GBDBv2::getData(self::SYSTEM_DB, self::USERS_TABLE, true, "uid", $uid);
        });

        if (!is_array($user) || empty($user) || (string)($user["active"] ?? "1") !== "1") {
            self::logout();
            return [];
        }

        $_SESSION["gqlui_v2_user"] = self::publicUser($user);
        return $_SESSION["gqlui_v2_user"];
    }

    /**
     * prüft ob ein benutzer eingeloggt ist.
     * @return bool Rückgabewert.
     */
    public static function loggedIn(): bool {
        return !empty(self::freshUser());
    }

    /**
     * entfernt sensible daten aus einem user-array.
     * @param array $user Übergabewert.
     * @return array Rückgabewert.
     */
    private static function publicUser(array $user): array {
        return [
            "id" => (int)($user["id"] ?? 0),
            "uid" => (string)($user["uid"] ?? ""),
            "username" => (string)($user["username"] ?? ""),
            "role" => self::normalizeRole((string)($user["role"] ?? "viewer")),
            "active" => (string)($user["active"] ?? "1"),
            "instances" => self::normalizeAccessValue((string)($user["instances"] ?? "*")),
            "bases" => self::normalizeAccessValue((string)($user["bases"] ?? "*"))
        ];
    }

    /**
     * gibt alle benutzer zurück.
     * @return array Rückgabewert.
     */
    public static function users(): array {
        return (array)self::inSystem(function () {
            $users = GBDBv2::getData(self::SYSTEM_DB, self::USERS_TABLE);

            if (!is_array($users)) {
                return [];
            }

            return array_map(function ($user) {
                if (is_array($user)) {
                    unset($user["password"]);
                }
                return $user;
            }, $users);
        });
    }

    /**
     * normalisiert rollen.
     * @param string $role Übergabewert.
     * @return string Rückgabewert.
     */
    public static function normalizeRole(string $role): string {
        $role = strtolower(self::clean($role));
        return in_array($role, ["admin", "editor", "viewer"], true) ? $role : "viewer";
    }

    /**
     * normalisiert access-angaben.
     * @param string $value Übergabewert.
     * @return string Rückgabewert.
     */
    private static function normalizeAccessValue(string $value): string {
        $value = trim($value);

        if ($value === "") {
            return "*";
        }

        if ($value === "*") {
            return "*";
        }

        $json = json_decode($value, true);

        if (is_array($json)) {
            return json_encode($json, JSON_UNESCAPED_UNICODE) ?: "*";
        }

        $items = array_values(array_filter(array_map(function ($item) {
            return self::clean(trim((string)$item));
        }, explode(",", $value))));

        return empty($items) ? "*" : implode(",", $items);
    }

    /**
     * prüft ob der aktuelle benutzer admin ist.
     * @return bool Rückgabewert.
     */
    public static function isAdmin(): bool {
        return (string)(self::user()["role"] ?? "") === "admin";
    }

    /**
     * prüft ob der aktuelle benutzer schreiben darf.
     * @return bool Rückgabewert.
     */
    public static function canWrite(): bool {
        return in_array((string)(self::user()["role"] ?? ""), ["admin", "editor"], true);
    }

    /**
     * prüft ob der aktuelle benutzer strukturen ändern darf.
     * @return bool Rückgabewert.
     */
    public static function canStructure(): bool {
        return self::isAdmin();
    }

    /**
     * bereinigt einen namen.
     * @param string $value Übergabewert.
     * @return string Rückgabewert.
     */
    public static function clean(string $value): string {
        if (class_exists("GreenQLv2")) {
            return GreenQLv2::cleanName($value);
        }

        $value = trim($value);
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value) ?? "";
    }

    /**
     * normalisiert reservierte namen für sichere vergleiche.
     * @param string $value Übergabewert.
     * @return string Rückgabewert.
     */
    private static function reservedSlug(string $value): string {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? "";
    }

    /**
     * prüft ob eine instanz reserviert ist.
     * @param string $instance Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function reservedInstance(string $instance): bool {
        $instance = self::clean($instance);
        $slug = self::reservedSlug($instance);
        $systemSlug = self::reservedSlug(self::SYSTEM_INSTANCE);

        return $instance === self::SYSTEM_INSTANCE
            || $slug === $systemSlug
            || str_starts_with($slug, "greenqlui");
    }

    /**
     * prüft ob ein name reserviert ist.
     * @param string $name Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function reservedName(string $name): bool {
        $name = self::clean($name);
        return $name === "" || str_starts_with($name, "__greenql_ui") || $name === self::SYSTEM_DB && class_exists("GBDBv2") && GBDBv2::getInstance() === self::SYSTEM_INSTANCE;
    }

    /**
     * parst eine access-liste.
     * @param string $value Übergabewert.
     * @return mixed Rückgabewert.
     */
    private static function accessValue(string $value): mixed {
        $value = trim($value);

        if ($value === "" || $value === "*") {
            return "*";
        }

        $json = json_decode($value, true);

        if (is_array($json)) {
            return $json;
        }

        return array_values(array_filter(array_map(function ($item) {
            return self::clean(trim((string)$item));
        }, explode(",", $value))));
    }

    /**
     * prüft ob ein user eine instanz sehen darf.
     * @param string $instance Übergabewert.
     * @param ?array $user Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function canAccessInstance(string $instance, ?array $user = null): bool {
        $instance = self::clean($instance);
        $user = $user ?? self::user();

        if ($instance === "" || self::reservedInstance($instance)) {
            return false;
        }

        if ((string)($user["role"] ?? "") === "admin") {
            return true;
        }

        $allowed = self::accessValue((string)($user["instances"] ?? "*"));

        if ($allowed === "*") {
            return true;
        }

        return is_array($allowed) && in_array($instance, array_map("strval", $allowed), true);
    }

    /**
     * prüft ob ein user eine base sehen darf.
     * @param string $instance Übergabewert.
     * @param string $db Übergabewert.
     * @param ?array $user Übergabewert.
     * @return bool Rückgabewert.
     */
    public static function canAccessDb(string $instance, string $db, ?array $user = null): bool {
        $instance = self::clean($instance);
        $db = self::clean($db);
        $user = $user ?? self::user();

        if ($instance === "" || $db === "" || !self::canAccessInstance($instance, $user)) {
            return false;
        }

        if ((string)($user["role"] ?? "") === "admin") {
            return true;
        }

        $allowed = self::accessValue((string)($user["bases"] ?? "*"));

        if ($allowed === "*") {
            return true;
        }

        if (is_array($allowed) && array_is_list($allowed)) {
            return in_array($db, array_map("strval", $allowed), true);
        }

        if (is_array($allowed) && isset($allowed[$instance])) {
            $item = $allowed[$instance];

            if ($item === "*") {
                return true;
            }

            if (is_array($item)) {
                return in_array($db, array_map("strval", $item), true);
            }

            return self::clean((string)$item) === $db;
        }

        return false;
    }

    /**
     * gibt sichtbare instanzen zurück.
     * @return array Rückgabewert.
     */
    public static function instances(): array {
        $items = class_exists("GBDBv2") ? GBDBv2::listInstances() : [];
        $user = self::user();

        return array_values(array_filter($items, function ($instance) use ($user) {
            return self::canAccessInstance((string)$instance, $user);
        }));
    }

    /**
     * gibt sichtbare datenbanken zurück.
     * @param string $instance Übergabewert.
     * @return array Rückgabewert.
     */
    public static function databases(string $instance): array {
        $instance = self::clean($instance);

        if (!self::canAccessInstance($instance)) {
            return [];
        }

        $old = GBDBv2::getInstance();

        try {
            GBDBv2::setInstance($instance);
            $dbs = GBDBv2::listDBs();
        } finally {
            GBDBv2::setInstance($old);
        }

        return array_values(array_filter($dbs, function ($db) use ($instance) {
            return !self::reservedName((string)$db) && self::canAccessDb($instance, (string)$db);
        }));
    }

    /**
     * gibt sichtbare tabellen zurück.
     * @param string $instance Übergabewert.
     * @param string $database Übergabewert.
     * @return array Rückgabewert.
     */
    public static function tables(string $instance, string $database): array {
        $instance = self::clean($instance);
        $database = self::clean($database);

        if (!self::canAccessDb($instance, $database)) {
            return [];
        }

        $old = GBDBv2::getInstance();

        try {
            GBDBv2::setInstance($instance);
            $tables = GBDBv2::listTables($database);
        } finally {
            GBDBv2::setInstance($old);
        }

        return array_values(array_filter($tables, function ($table) {
            return !self::reservedName((string)$table);
        }));
    }

    /**
     * parst parameter aus json oder key=value zeilen.
     * @param string $raw Übergabewert.
     * @return array Rückgabewert.
     */
    public static function parseParams(string $raw): array {
        $raw = trim($raw);

        if ($raw === "") {
            return [];
        }

        $json = json_decode($raw, true);

        if (is_array($json)) {
            return self::guardParams($json);
        }

        $params = [];
        $lines = preg_split('/\r\n|\r|\n/', $raw);

        foreach ($lines as $line) {
            $line = trim((string)$line);

            if ($line === "" || str_starts_with($line, "#")) {
                continue;
            }

            if (!str_contains($line, "=")) {
                continue;
            }

            [$key, $value] = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key !== "") {
                $params[$key] = $value;
            }
        }

        return self::guardParams($params);
    }

    /**
     * blockiert reservierte werte in parametern.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    private static function guardParams(array $params): array {
        foreach ($params as $key => $value) {
            if (is_string($value) && self::containsReserved($value)) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * prüft ob ein script vom aktuellen user ausgeführt werden darf.
     * @param string $script Übergabewert.
     * @param string $instance Übergabewert.
     * @return array Rückgabewert.
     */
    public static function scriptAllowed(string $script, string $instance): array {
        if (self::containsReserved($script)) {
            return ["ok" => false, "message" => "Dieses Script enthält reservierte Systemnamen und wurde blockiert."];
        }

        if (!self::canAccessInstance($instance)) {
            return ["ok" => false, "message" => "Du hast keinen Zugriff auf diese Instanz."];
        }

        $role = (string)(self::user()["role"] ?? "viewer");

        if ($role === "viewer" && preg_match('/\b(GROW|DROP|ALTER|SEED|RESHAPE|ERASE|PACK)\b/i', $script)) {
            return ["ok" => false, "message" => "Viewer dürfen nur lesende Queries ausführen."];
        }

        if ($role === "editor" && preg_match('/\b(GROW\s+INSTANCE|DROP\s+INSTANCE|GROW\s+BASE|DROP\s+BASE|GROW\s+TABLE|DROP\s+TABLE|ALTER\s+TABLE)\b/i', $script)) {
            return ["ok" => false, "message" => "Editor dürfen Daten bearbeiten, aber keine Struktur ändern."];
        }

        if (!self::isAdmin()) {
            if (preg_match_all('/\bIN\s+([a-zA-Z0-9_\-]+)/i', $script, $matches)) {
                foreach ($matches[1] as $db) {
                    $db = self::clean((string)$db);
                    if ($db !== "" && !self::canAccessDb($instance, $db)) {
                        return ["ok" => false, "message" => "Kein Zugriff auf Base: " . $db];
                    }
                }
            }
        }

        return ["ok" => true, "message" => ""];
    }

    /**
     * prüft text auf reservierte systemnamen.
     * @param string $value Übergabewert.
     * @return bool Rückgabewert.
     */
    private static function containsReserved(string $value): bool {
        $low = strtolower($value);
        $slug = self::reservedSlug($value);

        return str_contains($low, strtolower(self::SYSTEM_INSTANCE))
            || str_contains($low, "__greenql_ui")
            || str_contains($slug, self::reservedSlug(self::SYSTEM_INSTANCE))
            || str_contains($slug, "greenqlui");
    }

    /**
     * liest ein gql-script sicher aus dem projekt.
     * @param string $path Übergabewert.
     * @return array Rückgabewert.
     */
    public static function readScriptPath(string $path): array {
        $path = trim($path);

        if ($path === "") {
            return ["ok" => false, "script" => "", "message" => "Kein Script-Pfad angegeben."];
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== "gql") {
            return ["ok" => false, "script" => "", "message" => "Es sind nur .gql Dateien erlaubt."];
        }

        $root = realpath(dirname(__DIR__, 6));
        $full = realpath($path);

        if ($full === false && $root !== false) {
            $full = realpath($root . "/" . ltrim($path, "/"));
        }

        if ($root === false || $full === false || !str_starts_with($full, $root)) {
            return ["ok" => false, "script" => "", "message" => "Script liegt nicht innerhalb des Projekts."];
        }

        $script = @file_get_contents($full);

        if ($script === false) {
            return ["ok" => false, "script" => "", "message" => "Script konnte nicht gelesen werden."];
        }

        return ["ok" => true, "script" => $script, "message" => "Script geladen: " . str_replace($root . "/", "", $full)];
    }

    /**
     * führt greenql geschützt aus.
     * @param string $script Übergabewert.
     * @param string $instance Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function runScript(string $script, string $instance, array $params = []): array {
        $instance = self::clean($instance);
        $allowed = self::scriptAllowed($script, $instance);

        if (empty($allowed["ok"])) {
            return self::errorResult((string)$allowed["message"]);
        }

        GBDBv2::setInstance($instance);
        $result = GreenQLv2::run($script, ["instance" => $instance], $params);
        return self::filterResult($result);
    }

    /**
     * filtert systemwerte aus resultaten.
     * @param array $result Übergabewert.
     * @return array Rückgabewert.
     */
    private static function filterResult(array $result): array {
        if (isset($result["rows"]) && is_array($result["rows"])) {
            $result["rows"] = self::filterSystemRows($result["rows"]);
        }

        if (isset($result["results"]) && is_array($result["results"])) {
            foreach ($result["results"] as $i => $r) {
                if (isset($r["rows"]) && is_array($r["rows"])) {
                    $result["results"][$i]["rows"] = self::filterSystemRows($r["rows"]);
                }
            }
        }

        return $result;
    }

    /**
     * filtert systemzeilen aus query-ergebnissen.
     * @param array $rows Übergabewert.
     * @return array Rückgabewert.
     */
    private static function filterSystemRows(array $rows): array {
        return array_values(array_filter($rows, function ($row) {
            if (!is_array($row)) {
                return true;
            }

            foreach (["instance", "base", "table"] as $key) {
                if (isset($row[$key]) && self::containsReserved((string)$row[$key])) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * baut ein standard-fehlerergebnis.
     * @param string $message Übergabewert.
     * @return array Rückgabewert.
     */
    public static function errorResult(string $message): array {
        return [
            "ok" => false,
            "messages" => [
                ["ok" => false, "text" => $message]
            ],
            "results" => [],
            "keys" => [],
            "rows" => [],
            "ctx" => [],
            "vars" => [],
            "refresh" => false
        ];
    }

    /**
     * escaped html.
     * @param mixed $value Übergabewert.
     * @return string Rückgabewert.
     */
    public static function e(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
    }
}
