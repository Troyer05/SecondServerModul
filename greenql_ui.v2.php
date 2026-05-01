<?php

declare(strict_types=1);

require_once __DIR__ . "/assets/php/inc/.config/_config.inc.php";
require_once __DIR__ . "/assets/php/inc/gbdb_framework/dev/gql_v2/greenql_ui_v2_helper.php";

GreenQLUIv2Helper::boot();

function e(mixed $value): string {
    return GreenQLUIv2Helper::e($value);
}

function csrf(): string {
    return GreenQLUIv2Helper::csrf();
}

function selfUrl(array $params = []): string {
    $script = (string)($_SERVER["SCRIPT_NAME"] ?? "");

    if ($script === "") {
        $script = "/" . basename(__FILE__);
    }

    if (!str_starts_with($script, "/")) {
        $script = "/" . ltrim($script, "/");
    }

    $query = http_build_query($params);
    return $script . ($query !== "" ? "?" . $query : "");
}

function redirectSelf(array $params = []): void {
    header("Location: " . selfUrl($params));
    exit;
}

function flash(string $type, string $text): void {
    $_SESSION["gqlui_v2_flash"][] = ["type" => $type, "text" => $text];
}

function flashes(): array {
    $items = $_SESSION["gqlui_v2_flash"] ?? [];
    unset($_SESSION["gqlui_v2_flash"]);
    return is_array($items) ? $items : [];
}

function selectedMode(): string {
    $mode = (string)($_GET["mode"] ?? $_POST["mode"] ?? "ui");
    return in_array($mode, ["ui", "query", "users"], true) ? $mode : "ui";
}

function selectedInstance(): string {
    if (!isset($_GET["instance"]) && !isset($_POST["instance"])) {
        return "";
    }

    $instance = GreenQLUIv2Helper::clean((string)($_GET["instance"] ?? $_POST["instance"] ?? ""));

    if ($instance !== "" && GreenQLUIv2Helper::canAccessInstance($instance)) {
        return $instance;
    }

    return "";
}

function selectedDb(string $instance): string {
    $db = GreenQLUIv2Helper::clean((string)($_GET["db"] ?? $_POST["db"] ?? ""));

    if ($db !== "" && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
        return $db;
    }

    $dbs = $instance !== "" ? GreenQLUIv2Helper::databases($instance) : [];
    return $dbs[0] ?? "";
}

function selectedTable(string $instance, string $db): string {
    $table = GreenQLUIv2Helper::clean((string)($_GET["table"] ?? $_POST["table"] ?? ""));
    $tables = ($instance !== "" && $db !== "") ? GreenQLUIv2Helper::tables($instance, $db) : [];

    if ($table !== "" && in_array($table, $tables, true)) {
        return $table;
    }

    return $tables[0] ?? "";
}

function queryResultBox(array $result): void {
    if (empty($result)) {
        return;
    }

    echo '<section class="result-card">';
    echo '<div class="result-head"><div><span class="status-dot ' . (!empty($result["ok"]) ? 'ok' : 'bad') . '"></span>' . (!empty($result["ok"]) ? 'Ausführung erfolgreich' : 'Ausführung fehlgeschlagen') . '</div></div>';

    if (!empty($result["messages"]) && is_array($result["messages"])) {
        echo '<div class="message-stack">';
        foreach ($result["messages"] as $msg) {
            $ok = !empty($msg["ok"]);
            echo '<div class="mini-message ' . ($ok ? 'ok' : 'bad') . '">' . e($msg["text"] ?? "") . '</div>';
        }
        echo '</div>';
    }

    $keys = $result["keys"] ?? [];
    $rows = $result["rows"] ?? [];

    if (is_array($keys) && is_array($rows) && !empty($keys)) {
        echo '<div class="table-wrap result-table"><table><thead><tr>';
        foreach ($keys as $key) {
            echo '<th>' . e($key) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            echo '<tr>';
            foreach ($keys as $key) {
                $value = $row[$key] ?? "";
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }
                echo '<td><code>' . e($value) . '</code></td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    if (!empty($result["results"]) && is_array($result["results"])) {
        echo '<details class="raw-details"><summary>Alle Query Ergebnisse</summary><pre>' . e(json_encode($result["results"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre></details>';
    }

    echo '</section>';
}

function paramTextFromPost(): string {
    return (string)($_POST["params"] ?? "");
}

$action = (string)($_POST["action"] ?? "");
$queryResult = [];
$loadedScript = (string)($_POST["script"] ?? "USE INSTANCE test;\nROOT main;\nSHOW TABLES;");
$loadedPath = (string)($_POST["script_path"] ?? "");
$paramsText = paramTextFromPost();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST["csrf"] ?? ""))) {
        flash("bad", "Ungültiger Sicherheits-Token.");
        redirectSelf();
    }

    if ($action === "setup") {
        $username = (string)($_POST["username"] ?? "");
        $password = (string)($_POST["password"] ?? "");
        $password2 = (string)($_POST["password2"] ?? "");

        if (GreenQLUIv2Helper::hasUsers()) {
            flash("bad", "Setup ist bereits abgeschlossen.");
        } elseif ($password !== $password2) {
            flash("bad", "Passwörter stimmen nicht überein.");
        } elseif (GreenQLUIv2Helper::createUser($username, $password, "admin", "*", "*")) {
            GreenQLUIv2Helper::login($username, $password);
            flash("ok", "Admin angelegt und eingeloggt.");
        } else {
            flash("bad", "Admin konnte nicht angelegt werden. Passwort mindestens 8 Zeichen.");
        }

        redirectSelf();
    }

    if ($action === "login") {
        if (GreenQLUIv2Helper::login((string)($_POST["username"] ?? ""), (string)($_POST["password"] ?? ""))) {
            flash("ok", "Willkommen zurück.");
        } else {
            flash("bad", "Login fehlgeschlagen.");
        }

        redirectSelf();
    }

    if ($action === "logout") {
        GreenQLUIv2Helper::logout();
        redirectSelf();
    }
}

$needsSetup = !GreenQLUIv2Helper::hasUsers();
$loggedIn = GreenQLUIv2Helper::loggedIn();
$user = GreenQLUIv2Helper::user();

if ($loggedIn && $_SERVER["REQUEST_METHOD"] === "POST") {
    $instance = selectedInstance();
    $db = selectedDb($instance);
    $table = selectedTable($instance, $db);
    $mode = selectedMode();

    if ($action === "create_instance") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Instanzen erstellen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));

        if ($name === "" || GreenQLUIv2Helper::reservedInstance($name)) {
            flash("bad", "Ungültiger Instanzname.");
        } elseif (GBDBv2::createInstance($name)) {
            flash("ok", "Instanz erstellt: " . $name);
            redirectSelf(["mode" => "ui", "instance" => $name]);
        } else {
            flash("bad", "Instanz konnte nicht erstellt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "create_db") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Bases erstellen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));

        if ($instance === "" || $name === "" || GreenQLUIv2Helper::reservedName($name)) {
            flash("bad", "Ungültiger Base-Name.");
        } else {
            GBDBv2::setInstance($instance);
            if (GBDBv2::createDatabase($name)) {
                flash("ok", "Base erstellt: " . $name);
                redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $name]);
            }
            flash("bad", "Base konnte nicht erstellt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance]);
    }

    if ($action === "create_table") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Tabellen erstellen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $name = GreenQLUIv2Helper::clean((string)($_POST["name"] ?? ""));
        $cols = array_values(array_filter(array_map(function ($v) {
            return GreenQLUIv2Helper::clean(trim((string)$v));
        }, explode(",", (string)($_POST["cols"] ?? "")))));

        if ($instance === "" || $db === "" || $name === "" || empty($cols) || GreenQLUIv2Helper::reservedName($name)) {
            flash("bad", "Tabelle oder Felder ungültig.");
        } else {
            GBDBv2::setInstance($instance);
            if (GBDBv2::createTable($db, $name, $cols)) {
                flash("ok", "Tabelle erstellt: " . $name);
                redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $name]);
            }
            flash("bad", "Tabelle konnte nicht erstellt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db]);
    }

    if ($action === "add_column") {
        if (!GreenQLUIv2Helper::canStructure()) {
            flash("bad", "Nur Admins dürfen Spalten hinzufügen.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $column = GreenQLUIv2Helper::clean((string)($_POST["column"] ?? ""));
        $default = (string)($_POST["default"] ?? "");

        if ($instance === "" || $db === "" || $table === "" || $column === "") {
            flash("bad", "Spalte ungültig.");
        } else {
            GBDBv2::setInstance($instance);
            $ok = GBDBv2::addColumn($db, $table, $column, $default);
            flash($ok ? "ok" : "bad", $ok ? "Spalte verarbeitet: " . $column : "Spalte konnte nicht verarbeitet werden: " . $column);
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "insert_row") {
        if (!GreenQLUIv2Helper::canWrite()) {
            flash("bad", "Du hast keine Schreibrechte.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        if ($instance !== "" && $db !== "" && $table !== "" && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
            GBDBv2::setInstance($instance);
            $keys = GBDBv2::getKeys($db, $table);
            $data = [];

            foreach ($keys as $key) {
                if ($key === "id") {
                    continue;
                }
                $data[$key] = $_POST["row"][$key] ?? "";
            }

            $id = GBDBv2::insertData($db, $table, $data);
            flash($id > 0 ? "ok" : "bad", $id > 0 ? "Datensatz angelegt: #" . $id : "Datensatz konnte nicht angelegt werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "update_row") {
        if (!GreenQLUIv2Helper::canWrite()) {
            flash("bad", "Du hast keine Schreibrechte.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $id = (int)($_POST["id"] ?? 0);

        if ($instance !== "" && $db !== "" && $table !== "" && $id > 0 && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
            GBDBv2::setInstance($instance);
            $keys = GBDBv2::getKeys($db, $table);
            $data = [];

            foreach ($keys as $key) {
                if ($key === "id") {
                    continue;
                }
                $data[$key] = $_POST["row"][$key] ?? "";
            }

            $ok = GBDBv2::editData($db, $table, "id", $id, $data);
            flash($ok ? "ok" : "bad", $ok ? "Datensatz aktualisiert: #" . $id : "Datensatz konnte nicht aktualisiert werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "delete_row") {
        if (!GreenQLUIv2Helper::canWrite()) {
            flash("bad", "Du hast keine Schreibrechte.");
            redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
        }

        $id = (int)($_POST["id"] ?? 0);

        if ($instance !== "" && $db !== "" && $table !== "" && $id > 0 && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
            GBDBv2::setInstance($instance);
            $ok = GBDBv2::deleteData($db, $table, "id", $id);
            flash($ok ? "ok" : "bad", $ok ? "Datensatz gelöscht: #" . $id : "Datensatz konnte nicht gelöscht werden.");
        }

        redirectSelf(["mode" => "ui", "instance" => $instance, "db" => $db, "table" => $table]);
    }

    if ($action === "create_user") {
        if (!GreenQLUIv2Helper::isAdmin()) {
            flash("bad", "Nur Admins dürfen Benutzer anlegen.");
            redirectSelf(["mode" => "users", "instance" => $instance]);
        }

        $ok = GreenQLUIv2Helper::createUser(
            (string)($_POST["username"] ?? ""),
            (string)($_POST["password"] ?? ""),
            (string)($_POST["role"] ?? "viewer"),
            (string)($_POST["instances"] ?? "*"),
            (string)($_POST["bases"] ?? "*")
        );

        flash($ok ? "ok" : "bad", $ok ? "Benutzer angelegt." : "Benutzer konnte nicht angelegt werden.");
        redirectSelf(["mode" => "users", "instance" => $instance]);
    }

    if ($action === "update_user") {
        if (!GreenQLUIv2Helper::isAdmin()) {
            flash("bad", "Nur Admins dürfen Benutzer bearbeiten.");
            redirectSelf(["mode" => "users", "instance" => $instance]);
        }

        $id = (int)($_POST["id"] ?? 0);
        $ok = GreenQLUIv2Helper::updateUser($id, [
            "role" => (string)($_POST["role"] ?? "viewer"),
            "active" => (string)($_POST["active"] ?? "0"),
            "instances" => (string)($_POST["instances"] ?? "*"),
            "bases" => (string)($_POST["bases"] ?? "*"),
            "password" => (string)($_POST["password"] ?? "")
        ]);

        flash($ok ? "ok" : "bad", $ok ? "Benutzer aktualisiert." : "Benutzer konnte nicht aktualisiert werden.");
        redirectSelf(["mode" => "users", "instance" => $instance]);
    }

    if ($action === "delete_user") {
        if (!GreenQLUIv2Helper::isAdmin()) {
            flash("bad", "Nur Admins dürfen Benutzer löschen.");
            redirectSelf(["mode" => "users", "instance" => $instance]);
        }

        $ok = GreenQLUIv2Helper::deleteUser((int)($_POST["id"] ?? 0));
        flash($ok ? "ok" : "bad", $ok ? "Benutzer gelöscht." : "Benutzer konnte nicht gelöscht werden.");
        redirectSelf(["mode" => "users", "instance" => $instance]);
    }

    if ($action === "run_query" || $action === "run_uploaded" || $action === "run_path") {
        $script = (string)($_POST["script"] ?? "");
        $params = GreenQLUIv2Helper::parseParams($paramsText);

        if ($action === "run_uploaded") {
            if (!isset($_FILES["gql_file"]) || !is_uploaded_file((string)($_FILES["gql_file"]["tmp_name"] ?? ""))) {
                $queryResult = GreenQLUIv2Helper::errorResult("Keine .gql Datei hochgeladen.");
            } elseif (strtolower(pathinfo((string)$_FILES["gql_file"]["name"], PATHINFO_EXTENSION)) !== "gql") {
                $queryResult = GreenQLUIv2Helper::errorResult("Nur .gql Dateien sind erlaubt.");
            } else {
                $script = (string)file_get_contents((string)$_FILES["gql_file"]["tmp_name"]);
                $loadedScript = $script;
            }
        }

        if ($action === "run_path") {
            $loadedPath = (string)($_POST["script_path"] ?? "");
            $read = GreenQLUIv2Helper::readScriptPath($loadedPath);

            if (empty($read["ok"])) {
                $queryResult = GreenQLUIv2Helper::errorResult((string)$read["message"]);
            } else {
                $script = (string)$read["script"];
                $loadedScript = $script;
                flash("ok", (string)$read["message"]);
            }
        }

        if (empty($queryResult)) {
            $loadedScript = $script;
            $queryResult = GreenQLUIv2Helper::runScript($script, $instance, $params);
        }
    }
}

$mode = selectedMode();
if ($mode === "users" && !GreenQLUIv2Helper::isAdmin()) {
    $mode = "ui";
}
$instance = $loggedIn ? selectedInstance() : "";
$db = $loggedIn ? selectedDb($instance) : "";
$table = $loggedIn ? selectedTable($instance, $db) : "";
$instances = $loggedIn ? GreenQLUIv2Helper::instances() : [];
$dbs = ($loggedIn && $instance !== "") ? GreenQLUIv2Helper::databases($instance) : [];
$tables = ($loggedIn && $instance !== "" && $db !== "") ? GreenQLUIv2Helper::tables($instance, $db) : [];
$search = (string)($_GET["search"] ?? "");
$keys = [];
$rows = [];

if ($loggedIn && $instance !== "" && $db !== "" && $table !== "" && GreenQLUIv2Helper::canAccessDb($instance, $db)) {
    GBDBv2::setInstance($instance);
    $keys = GBDBv2::getKeys($db, $table);
    $rows = GBDBv2::getData($db, $table);

    if (!is_array($rows)) {
        $rows = [];
    }

    if ($search !== "") {
        $needle = mb_strtolower($search);
        $rows = array_values(array_filter($rows, function ($row) use ($needle) {
            if (!is_array($row)) {
                return false;
            }
            return mb_stripos(mb_strtolower(json_encode($row, JSON_UNESCAPED_UNICODE) ?: ""), $needle) !== false;
        }));
    }
}

?><!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GreenQL v2 UI</title>
    <link rel="stylesheet" href="assets/css/greenql_ui.v2.css?v=2.2">
</head>
<body>
<?php if (!$loggedIn): ?>
    <main class="auth-shell">
        <section class="auth-card">
            <div class="brand-mark">G2</div>
            <p class="eyebrow">greenbucket®</p>
            <h1>GreenQL v2</h1>
            <p class="muted"><?= $needsSetup ? "Ersteinrichtung der lokalen Admin-Oberfläche." : "Einloggen, um Instanzen, Bases und Tabellen zu verwalten." ?></p>

            <?php foreach (flashes() as $f): ?>
                <div class="flash <?= e($f["type"] ?? "") ?>"><?= e($f["text"] ?? "") ?></div>
            <?php endforeach; ?>

            <form method="post" class="stack-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="action" value="<?= $needsSetup ? 'setup' : 'login' ?>">
                <label>Benutzername<input name="username" autocomplete="username" required></label>
                <label>Passwort<input name="password" type="password" autocomplete="<?= $needsSetup ? 'new-password' : 'current-password' ?>" required></label>
                <?php if ($needsSetup): ?>
                    <label>Passwort wiederholen<input name="password2" type="password" autocomplete="new-password" required></label>
                <?php endif; ?>
                <button class="primary"><?= $needsSetup ? "Admin anlegen" : "Einloggen" ?></button>
            </form>
        </section>
    </main>
<?php else: ?>
<div class="app-shell">
    <aside class="sidebar">
        <div class="side-top">
            <div class="brand-mark small">G2</div>
            <div>
                <strong>GreenQL v2</strong>
                <span><?= e($user["username"] ?? "") ?> · <?= e($user["role"] ?? "") ?></span>
            </div>
        </div>

        <nav class="mode-switch">
            <a class="<?= $mode === 'ui' ? 'active' : '' ?>" href="<?= e(selfUrl(['mode' => 'ui', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">UI</a>
            <a class="<?= $mode === 'query' ? 'active' : '' ?>" href="<?= e(selfUrl(['mode' => 'query', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">Query</a>
            <?php if (GreenQLUIv2Helper::isAdmin()): ?>
                <a class="<?= $mode === 'users' ? 'active' : '' ?>" href="<?= e(selfUrl(['mode' => 'users', 'instance' => $instance])) ?>">User</a>
            <?php endif; ?>
        </nav>

        <div class="tree-head"><span>Hierarchie</span><span><?= count($instances) ?></span></div>
        <div class="tree">
            <?php foreach ($instances as $inst): ?>
                <?php if ($inst == "greenqluiv2system") continue; ?>
                <?php $instDbs = GreenQLUIv2Helper::databases($inst); ?>
                <details <?= $inst === $instance ? 'open' : '' ?>>
                    <summary><a href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst])) ?>"><?= e($inst) ?></a></summary>
                    <?php foreach ($instDbs as $dbName): ?>
                        <?php $dbTables = GreenQLUIv2Helper::tables($inst, $dbName); ?>
                        <details class="level-db" <?= $inst === $instance && $dbName === $db ? 'open' : '' ?>>
                            <summary><a href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst, 'db' => $dbName])) ?>"><?= e($dbName) ?></a></summary>
                            <?php foreach ($dbTables as $tableName): ?>
                                <a class="leaf <?= $inst === $instance && $dbName === $db && $tableName === $table ? 'active' : '' ?>" href="<?= e(selfUrl(['mode' => $mode, 'instance' => $inst, 'db' => $dbName, 'table' => $tableName])) ?>"><?= e($tableName) ?></a>
                            <?php endforeach; ?>
                            <?php if (GreenQLUIv2Helper::canStructure()): ?>
                                <button class="tiny-link" type="button" data-toggle="create-table" data-instance="<?= e($inst) ?>" data-db="<?= e($dbName) ?>">+ Tabelle</button>
                            <?php endif; ?>
                        </details>
                    <?php endforeach; ?>
                    <?php if (GreenQLUIv2Helper::canStructure()): ?>
                        <button class="tiny-link tree-db" type="button" data-toggle="create-db" data-instance="<?= e($inst) ?>">+ Base</button>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>

        <?php if (GreenQLUIv2Helper::canStructure()): ?>
            <button class="secondary full" type="button" data-toggle="create-instance">Instanz erstellen</button>
        <?php endif; ?>

        <form method="post" class="logout-form">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="action" value="logout">
            <button class="ghost full">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <p class="eyebrow">GreenQL v2 Console</p>
                <h1><?= $mode === 'query' ? 'Query Mode' : ($mode === 'users' ? 'User Management' : 'UI Mode') ?></h1>
                <p class="muted"><?= e($instance ?: 'keine instanz') ?><?= $db ? ' / ' . e($db) : '' ?><?= $table ? ' / ' . e($table) : '' ?></p>
            </div>
            <div class="top-actions">
                <?php if (GreenQLUIv2Helper::canStructure()): ?>
                    <button class="secondary" type="button" data-toggle="create-instance">Neue Instanz</button>
                <?php endif; ?>
                <a class="secondary" href="<?= e(selfUrl(['mode' => 'query', 'instance' => $instance, 'db' => $db, 'table' => $table])) ?>">Query öffnen</a>
            </div>
        </header>

        <?php foreach (flashes() as $f): ?>
            <div class="flash <?= e($f["type"] ?? "") ?>"><?= e($f["text"] ?? "") ?></div>
        <?php endforeach; ?>

        <?php if (GreenQLUIv2Helper::canStructure()): ?>
            <section id="create-instance" class="inline-card hidden">
                <form method="post" class="inline-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="action" value="create_instance">
                    <input name="name" placeholder="instanz_name" required>
                    <button class="primary">Instanz erstellen</button>
                </form>
            </section>
            <section id="create-db" class="inline-card hidden">
                <form method="post" class="inline-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="action" value="create_db">
                    <input id="db-instance" type="hidden" name="instance" value="<?= e($instance) ?>">
                    <input name="name" placeholder="base_name" required>
                    <button class="primary">Base erstellen</button>
                </form>
            </section>
            <section id="create-table" class="inline-card hidden">
                <form method="post" class="inline-form wide">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="action" value="create_table">
                    <input id="table-instance" type="hidden" name="instance" value="<?= e($instance) ?>">
                    <input id="table-db" type="hidden" name="db" value="<?= e($db) ?>">
                    <input name="name" placeholder="table_name" required>
                    <input name="cols" placeholder="uid, username, email" required>
                    <button class="primary">Tabelle erstellen</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($mode === 'query'): ?>
            <section class="panel query-panel">
                <div class="panel-head">
                    <div>
                        <h2>Query Editor</h2>
                        <p class="muted">Syntax Highlighting ohne Cursor-Versatz · Upload · Pfad-Ausführung · Parameter</p>
                    </div>
                </div>
                <form method="post" enctype="multipart/form-data" class="query-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="instance" value="<?= e($instance) ?>">
                    <input type="hidden" name="db" value="<?= e($db) ?>">
                    <input type="hidden" name="table" value="<?= e($table) ?>">
                    <input type="hidden" name="mode" value="query">
                    <div class="editor-shell">
                        <pre id="gql-highlight" aria-hidden="true"></pre>
                        <textarea id="gql-editor" name="script" spellcheck="false"><?= e($loadedScript) ?></textarea>
                    </div>
                    <div class="query-grid">
                        <label>Parameter JSON oder key=value<textarea name="params" class="small-area" placeholder='{"uid":"abc123"}'><?= e($paramsText) ?></textarea></label>
                        <label>.gql Script-Pfad<input name="script_path" value="<?= e($loadedPath) ?>" placeholder="scripts/greenql/makeUser.gql"></label>
                        <label>.gql hochladen<input type="file" name="gql_file" accept=".gql"></label>
                    </div>
                    <div class="button-row">
                        <button class="primary" name="action" value="run_query">Editor ausführen</button>
                        <button class="secondary" name="action" value="run_uploaded">Upload ausführen</button>
                        <button class="secondary" name="action" value="run_path">Pfad ausführen</button>
                    </div>
                </form>
            </section>
            <?php queryResultBox($queryResult); ?>
        <?php elseif ($mode === 'users' && GreenQLUIv2Helper::isAdmin()): ?>
            <?php $uiUsers = GreenQLUIv2Helper::users(); ?>
            <section class="panel users-panel">
                <div class="panel-head users-head">
                    <div>
                        <p class="eyebrow">Access Control</p>
                        <h2>User Management</h2>
                        <p class="muted">Benutzer, Rollen und Freigaben für GreenQL v2. Die interne System-Instanz bleibt versteckt.</p>
                    </div>
                    <div class="user-stats">
                        <div><strong><?= count($uiUsers) ?></strong><span>User</span></div>
                        <div><strong><?= count(array_filter($uiUsers, fn($x) => is_array($x) && ($x["role"] ?? "") === "admin")) ?></strong><span>Admins</span></div>
                        <div><strong><?= count(array_filter($uiUsers, fn($x) => is_array($x) && (string)($x["active"] ?? "1") === "1")) ?></strong><span>Aktiv</span></div>
                    </div>
                </div>

                <details class="user-create-card" open>
                    <summary><span>Neuen Benutzer anlegen</span><em>Rolle und Zugriff direkt setzen</em></summary>
                    <form method="post" class="user-create-grid">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <input type="hidden" name="action" value="create_user">
                        <input type="hidden" name="instance" value="<?= e($instance) ?>">
                        <label>Benutzername<input name="username" placeholder="z.B. markus" required></label>
                        <label>Passwort<input name="password" type="password" minlength="8" placeholder="mind. 8 Zeichen" required></label>
                        <label>Rolle<select name="role"><option value="admin">Admin</option><option value="editor">Editor</option><option value="viewer">Viewer</option></select></label>
                        <label>Instanzen<input name="instances" value="*" placeholder="* oder instanz1,instanz2"></label>
                        <label class="wide">Bases<input name="bases" value="*" placeholder='* oder main,event_1 oder {"instanz":["main"]}'></label>
                        <button class="primary">Benutzer speichern</button>
                    </form>
                </details>

                <div class="role-help-grid">
                    <div><strong>Admin</strong><span>Kann alles sehen, User verwalten und Strukturen ändern.</span></div>
                    <div><strong>Editor</strong><span>Darf Daten lesen/schreiben, aber keine Bases oder Tabellen anlegen.</span></div>
                    <div><strong>Viewer</strong><span>Nur lesender Zugriff auf freigegebene Instanzen und Bases.</span></div>
                </div>

                <div class="user-card-grid">
                    <?php foreach ($uiUsers as $u): ?>
                        <?php if (!is_array($u)) continue; ?>
                        <?php $role = (string)($u["role"] ?? "viewer"); $active = (string)($u["active"] ?? "1") === "1"; ?>
                        <article class="user-card <?= $active ? 'is-active' : 'is-disabled' ?>">
                            <div class="user-card-top">
                                <div class="avatar"><?= e(strtoupper(substr((string)($u["username"] ?? "?"), 0, 1))) ?></div>
                                <div>
                                    <h3><?= e($u["username"] ?? "") ?></h3>
                                    <div class="user-meta"><span class="role-pill role-<?= e($role) ?>"><?= e($role) ?></span><span><?= $active ? 'aktiv' : 'inaktiv' ?></span><span>ID <?= e($u["id"] ?? "") ?></span></div>
                                </div>
                            </div>

                            <form method="post" class="user-edit-form">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="action" value="update_user">
                                <input type="hidden" name="instance" value="<?= e($instance) ?>">
                                <input type="hidden" name="id" value="<?= e($u["id"] ?? "") ?>">

                                <div class="user-form-row two">
                                    <label>Rolle<select name="role"><option value="admin" <?= $role === "admin" ? "selected" : "" ?>>Admin</option><option value="editor" <?= $role === "editor" ? "selected" : "" ?>>Editor</option><option value="viewer" <?= $role === "viewer" ? "selected" : "" ?>>Viewer</option></select></label>
                                    <label>Status<select name="active"><option value="1" <?= $active ? "selected" : "" ?>>Aktiv</option><option value="0" <?= !$active ? "selected" : "" ?>>Inaktiv</option></select></label>
                                </div>

                                <label>Instanzen<input name="instances" value="<?= e($u["instances"] ?? "*") ?>" placeholder="* oder instanz1,instanz2"></label>
                                <label>Bases<input name="bases" value="<?= e($u["bases"] ?? "*") ?>" placeholder='* oder {"instanz":["main"]}'></label>
                                <label>Neues Passwort<input name="password" type="password" placeholder="leer lassen = unverändert"></label>

                                <div class="user-card-foot">
                                    <span>Letzter Login: <code><?= e($u["last_login"] ?? "nie") ?></code></span>
                                    <button class="secondary xs">Speichern</button>
                                </div>
                            </form>

                            <form method="post" class="user-delete-form" onsubmit="return confirm('Benutzer wirklich löschen?')">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="instance" value="<?= e($instance) ?>">
                                <input type="hidden" name="id" value="<?= e($u["id"] ?? "") ?>">
                                <button class="danger xs">Benutzer löschen</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <?php if ($instance === ""): ?>
                <section class="empty-state"><h2>Keine sichtbare Instanz</h2><p>Admins können eine neue Instanz erstellen. Eingeschränkte Nutzer brauchen erst eine Freigabe.</p></section>
            <?php elseif ($db === ""): ?>
                <section class="empty-state"><h2>Keine Base sichtbar</h2><p>In dieser Instanz gibt es keine für dich freigegebene Base.</p><?php if (GreenQLUIv2Helper::canStructure()): ?><button type="button" class="primary" data-toggle="create-db">Base erstellen</button><?php endif; ?></section>
            <?php elseif ($table === ""): ?>
                <section class="empty-state"><h2>Keine Tabelle sichtbar</h2><p>In dieser Base gibt es keine sichtbare Tabelle.</p><?php if (GreenQLUIv2Helper::canStructure()): ?><button type="button" class="primary" data-toggle="create-table">Tabelle erstellen</button><?php endif; ?></section>
            <?php else: ?>
                <section class="panel table-panel">
                    <div class="panel-head">
                        <div><h2><?= e($table) ?></h2><p class="muted"><?= count($rows) ?> Datensätze · <?= count($keys) ?> Felder</p></div>
                        <form method="get" class="search-form">
                            <input type="hidden" name="mode" value="ui"><input type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db" value="<?= e($db) ?>"><input type="hidden" name="table" value="<?= e($table) ?>">
                            <input name="search" value="<?= e($search) ?>" placeholder="Suchen..."><button>Suchen</button>
                        </form>
                    </div>
                    <?php if (GreenQLUIv2Helper::canWrite()): ?>
                        <details class="insert-box"><summary>Datensatz hinzufügen</summary><form method="post" class="data-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="insert_row"><input type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db" value="<?= e($db) ?>"><input type="hidden" name="table" value="<?= e($table) ?>"><?php foreach ($keys as $key): ?><?php if ($key === "id") continue; ?><label><?= e($key) ?><input name="row[<?= e($key) ?>]"></label><?php endforeach; ?><button class="primary">Speichern</button></form></details>
                    <?php endif; ?>
                    <?php if (GreenQLUIv2Helper::canStructure()): ?>
                        <form method="post" class="add-column-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="add_column"><input type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db" value="<?= e($db) ?>"><input type="hidden" name="table" value="<?= e($table) ?>"><input name="column" placeholder="neue_spalte" required><input name="default" placeholder="default"><button>Spalte hinzufügen</button></form>
                    <?php endif; ?>
                    <div class="table-wrap"><table><thead><tr><?php foreach ($keys as $key): ?><th><?= e($key) ?></th><?php endforeach; ?><?php if (GreenQLUIv2Helper::canWrite()): ?><th>Aktionen</th><?php endif; ?></tr></thead><tbody>
                    <?php foreach ($rows as $row): ?><?php if (!is_array($row)) continue; ?><tr><?php foreach ($keys as $key): ?><?php $value = $row[$key] ?? ""; ?><td><code><?= e(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value) ?></code></td><?php endforeach; ?><?php if (GreenQLUIv2Helper::canWrite()): ?><td class="row-actions"><button type="button" class="ghost xs edit-row" data-row="<?= e(base64_encode(json_encode($row, JSON_UNESCAPED_UNICODE) ?: '{}')) ?>">Edit</button><form method="post" onsubmit="return confirm('Datensatz wirklich löschen?')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete_row"><input type="hidden" name="instance" value="<?= e($instance) ?>"><input type="hidden" name="db" value="<?= e($db) ?>"><input type="hidden" name="table" value="<?= e($table) ?>"><input type="hidden" name="id" value="<?= e($row["id"] ?? "") ?>"><button class="danger xs">Delete</button></form></td><?php endif; ?></tr><?php endforeach; ?>
                    </tbody></table></div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<dialog id="edit-dialog" class="edit-dialog">
    <form method="post" class="data-form" id="edit-form">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="action" value="update_row">
        <input type="hidden" name="instance" value="<?= e($instance) ?>">
        <input type="hidden" name="db" value="<?= e($db) ?>">
        <input type="hidden" name="table" value="<?= e($table) ?>">
        <input type="hidden" name="id" id="edit-id">
        <div class="dialog-head"><h3>Datensatz bearbeiten</h3><button type="button" class="ghost xs" id="edit-close">Schließen</button></div>
        <div id="edit-fields"></div>
        <button class="primary">Änderungen speichern</button>
    </form>
</dialog>

<script>
(function () {
    document.querySelectorAll('[data-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-toggle');
            const el = document.getElementById(id);
            if (!el) return;
            if (btn.dataset.instance && document.getElementById('db-instance')) document.getElementById('db-instance').value = btn.dataset.instance;
            if (btn.dataset.instance && document.getElementById('table-instance')) document.getElementById('table-instance').value = btn.dataset.instance;
            if (btn.dataset.db && document.getElementById('table-db')) document.getElementById('table-db').value = btn.dataset.db;
            el.classList.toggle('hidden');
            el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        });
    });

    const textarea = document.getElementById('gql-editor');
    const high = document.getElementById('gql-highlight');
    const escapeHtml = s => s.replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    const keywords = new Set(['ROOT','BRANCH','SHOW','BASES','TABLES','INSTANCES','GROW','DROP','TABLE','BASE','INSTANCE','ALTER','ADD','COLUMN','DEFAULT','DESCRIBE','PACK','PEEK','PICK','FROM','IN','WHERE','SORT','ASC','DESC','LIMIT','SEED','WITH','RESHAPE','ERASE','USE','DECLARE','DECALRE','PARAM']);
    const highlight = src => {
        let out = '', i = 0;
        while (i < src.length) {
            const ch = src[i];
            if (ch === '#') {
                const j = src.indexOf('\n', i);
                const end = j === -1 ? src.length : j;
                out += '<span class="tok-comment">' + escapeHtml(src.slice(i, end)) + '</span>';
                i = end;
                continue;
            }
            if (ch === '/' && src[i + 1] === '/') {
                const j = src.indexOf('\n', i);
                const end = j === -1 ? src.length : j;
                out += '<span class="tok-comment">' + escapeHtml(src.slice(i, end)) + '</span>';
                i = end;
                continue;
            }
            if (ch === '"' || ch === "'") {
                const q = ch; let j = i + 1;
                while (j < src.length) { if (src[j] === '\\') { j += 2; continue; } if (src[j] === q) { j++; break; } j++; }
                out += '<span class="tok-string">' + escapeHtml(src.slice(i, j)) + '</span>';
                i = j;
                continue;
            }
            const rest = src.slice(i);
            const word = rest.match(/^[A-Za-z_][A-Za-z0-9_]*/);
            if (word) {
                const w = word[0];
                out += keywords.has(w.toUpperCase()) ? '<span class="tok-key">' + escapeHtml(w) + '</span>' : escapeHtml(w);
                i += w.length;
                continue;
            }
            const num = rest.match(/^\d+(?:\.\d+)?/);
            if (num) {
                out += '<span class="tok-num">' + escapeHtml(num[0]) + '</span>';
                i += num[0].length;
                continue;
            }
            out += escapeHtml(ch);
            i++;
        }
        return out;
    };
    const paint = () => {
        if (!textarea || !high) return;
        high.innerHTML = highlight(textarea.value) + '\n';
    };
    if (textarea && high) {
        textarea.addEventListener('input', paint);
        textarea.addEventListener('scroll', () => { high.scrollTop = textarea.scrollTop; high.scrollLeft = textarea.scrollLeft; });
        paint();
    }

    const dialog = document.getElementById('edit-dialog');
    const fields = document.getElementById('edit-fields');
    const editId = document.getElementById('edit-id');
    document.querySelectorAll('.edit-row').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!dialog || !fields || !editId) return;
            const row = JSON.parse(atob(btn.dataset.row || 'e30='));
            editId.value = row.id || '';
            fields.innerHTML = '';
            Object.keys(row).forEach(key => {
                if (key === 'id') return;
                const label = document.createElement('label');
                label.textContent = key;
                const input = document.createElement('input');
                input.name = 'row[' + key + ']';
                input.value = row[key] ?? '';
                label.appendChild(input);
                fields.appendChild(label);
            });
            dialog.showModal();
        });
    });
    const close = document.getElementById('edit-close');
    if (close && dialog) close.addEventListener('click', () => dialog.close());
})();
</script>
<?php endif; ?>
</body>
</html>
