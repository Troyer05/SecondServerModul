<?php
declare(strict_types=1);

require_once __DIR__ . '/assets/php/inc/.config/_config.inc.php';

if (!Vars::__DEV__()) {
    http_response_code(403);
    echo 'GBDB UI ist nur im DEV-Modus verfuegbar.';
    exit;
}

$uiDir = __DIR__ . '/assets/php/inc/gbdb_framework/ui';
require_once $uiDir . '/_shared.php';
require_once $uiDir . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();

$tool = (string)($_GET['tool'] ?? $_POST['tool'] ?? 'dashboard');
$allowed = ['dashboard', 'greenql_v2', 'greenql', 'php_exec', 'users', 'migration', 'cryption', 'optimize'];

if (!in_array($tool, $allowed, true)) {
    $tool = 'dashboard';
}

/**
 * Leitet innerhalb der GBDB UI weiter.
 * @param string $tool Ziel-Tool.
 * @param array $params Query-Parameter.
 * @return void
 */
function gbdbui_redirect(string $tool = 'dashboard', array $params = []): void {
    header('Location: ' . gbdbui_url($tool, $params));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['gbdbui_action'])) {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Ungültiger Sicherheits-Token.');
        gbdbui_redirect($tool);
    }

    $action = (string)$_POST['gbdbui_action'];

    if ($action === 'setup') {
        $username = (string)($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');

        if (GreenQLUIv2Helper::hasUsers()) {
            gbdbui_flash('bad', 'Setup ist bereits abgeschlossen.');
        } elseif ($password !== $password2) {
            gbdbui_flash('bad', 'Passwörter stimmen nicht überein.');
        } elseif (GreenQLUIv2Helper::createUser($username, $password, 'admin', '*', '*')) {
            GreenQLUIv2Helper::login($username, $password);
            gbdbui_flash('ok', 'Admin angelegt und eingeloggt.');
        } else {
            gbdbui_flash('bad', 'Admin konnte nicht angelegt werden. Benutzername prüfen, Passwort mindestens 8 Zeichen.');
        }

        gbdbui_redirect('dashboard');
    }

    if ($action === 'login') {
        if (GreenQLUIv2Helper::login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''))) {
            gbdbui_flash('ok', 'Willkommen zurück.');
        } else {
            gbdbui_flash('bad', 'Login fehlgeschlagen.');
        }

        gbdbui_redirect('dashboard');
    }

    if ($action === 'logout') {
        GreenQLUIv2Helper::logout();
        gbdbui_flash('ok', 'Du wurdest ausgeloggt.');
        gbdbui_redirect('dashboard');
    }
}

$needsSetup = !GreenQLUIv2Helper::hasUsers();
$loggedIn = GreenQLUIv2Helper::loggedIn();

if (!$loggedIn) {
    ?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB UI Login</title>
    <link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css">
    <link rel="stylesheet" href="assets/css/gbdb/greenql_ui.v2.css?v=2.2">
</head>
<body class="gbdbui-dashboard gbdbui-auth-body">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="brand-mark">GB</div>
            <p class="eyebrow">greenbucket® Framework</p>
            <h1>GBDB UI</h1>
            <p class="muted"><?= $needsSetup ? 'Ersteinrichtung der zentralen Admin-Oberfläche.' : 'Einloggen, um die Framework-Tools zu nutzen.' ?></p>
            <?php gbdbui_render_flashes(); ?>
            <form method="post" class="stack-form">
                <input type="hidden" name="csrf" value="<?= gbdbui_e(GreenQLUIv2Helper::csrf()) ?>">
                <input type="hidden" name="gbdbui_action" value="<?= $needsSetup ? 'setup' : 'login' ?>">
                <label>Benutzername<input name="username" autocomplete="username" required></label>
                <label>Passwort<input name="password" type="password" autocomplete="<?= $needsSetup ? 'new-password' : 'current-password' ?>" required></label>
                <?php if ($needsSetup): ?>
                <label>Passwort wiederholen<input name="password2" type="password" autocomplete="new-password" required></label>
                <?php endif; ?>
                <button class="primary"><?= $needsSetup ? 'Admin anlegen' : 'Einloggen' ?></button>
            </form>
        </section>
    </main>
</body>
</html>
    <?php
    exit;
}

gbdbui_require_tool($tool);

if ($tool !== 'dashboard') {
    require $uiDir . '/' . $tool . '.page.php';
    exit;
}

$user = GreenQLUIv2Helper::user();
$cards = [
    ['tool' => 'greenql_v2', 'tag' => 'Studio', 'title' => 'GreenQL v2', 'text' => 'Instanzen, Bases, Tabellen und Query-Modus mit Rollenfilter.'],
    ['tool' => 'greenql', 'tag' => 'Legacy', 'title' => 'GreenQL v1', 'text' => 'Klassisches GreenQL Studio für die GBDB v1-Struktur.'],
    ['tool' => 'php_exec', 'tag' => 'Dev', 'title' => 'PHP Exec', 'text' => 'PHP direkt im Browser testen – mit Highlighting, Auto-Einrückung und Output.'],
    ['tool' => 'users', 'tag' => 'Admin', 'title' => 'Benutzerverwaltung', 'text' => 'Zentrale UI-User, Rollen, Instanz- und Base-Rechte verwalten.'],
    ['tool' => 'migration', 'tag' => 'Maintenance', 'title' => 'Migration', 'text' => 'Struktur-Upgrades, Meta-/Append-Aufbau und optionale Schema-Konvertierung.'],
    ['tool' => 'cryption', 'tag' => 'Security', 'title' => 'Crypto', 'text' => 'GBDB zwischen Plain-JSON und verschlüsselter/tokenisierter Struktur konvertieren.'],
    ['tool' => 'optimize', 'tag' => 'Performance', 'title' => 'Optimize', 'text' => 'Tabellen compacten, Append-Logs auswerten und GBDB-Dateien warten.'],
];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB UI</title>
    <link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css">
</head>
<body class="gbdbui-dashboard">
    <?php gbdbui_nav('dashboard'); ?>
    <main>
        <?php gbdbui_render_flashes(); ?>
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Angemeldet als <?= gbdbui_e($user['username'] ?? '') ?> · Rolle <?= gbdbui_e($user['role'] ?? '') ?></p>
            <h1>GBDB UI</h1>
            <p>Zentrale Dev-Oberfläche für GBDB, GreenQL, Migration, Crypto, Optimierung, PHP-Testausführung und Benutzerrechte. Die gesamte UI hängt jetzt am gleichen Login- und Rechte-System.</p>
        </section>

        <section class="gbdbui-grid">
            <?php foreach ($cards as $card): ?>
            <?php if (!gbdbui_can_tool($card['tool'])) continue; ?>
            <a class="gbdbui-card" href="<?= gbdbui_e(gbdbui_url($card['tool'])) ?>"><span><?= gbdbui_e($card['tag']) ?></span><h2><?= gbdbui_e($card['title']) ?></h2><p><?= gbdbui_e($card['text']) ?></p></a>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
