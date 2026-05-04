<?php
declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/greenql_v2_helper.php';

GreenQLUIv2Helper::boot();
gbdbui_require_tool('users');

/**
 * Leitet zur Benutzerverwaltung zurück.
 * @return void
 */
function gbdbui_users_redirect(): void {
    header('Location: ' . gbdbui_url('users'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['user_action'])) {
    if (!GreenQLUIv2Helper::checkCsrf((string)($_POST['csrf'] ?? ''))) {
        gbdbui_flash('bad', 'Ungültiger Sicherheits-Token.');
        gbdbui_users_redirect();
    }

    $action = (string)$_POST['user_action'];

    if ($action === 'create_user') {
        $ok = GreenQLUIv2Helper::createUser(
            (string)($_POST['username'] ?? ''),
            (string)($_POST['password'] ?? ''),
            (string)($_POST['role'] ?? 'viewer'),
            (string)($_POST['instances'] ?? '*'),
            (string)($_POST['bases'] ?? '*')
        );

        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'Benutzer angelegt.' : 'Benutzer konnte nicht angelegt werden. Benutzername eindeutig, Passwort mindestens 8 Zeichen.');
        gbdbui_users_redirect();
    }

    if ($action === 'update_user') {
        $ok = GreenQLUIv2Helper::updateUser((int)($_POST['id'] ?? 0), [
            'username' => (string)($_POST['username'] ?? ''),
            'role' => (string)($_POST['role'] ?? 'viewer'),
            'active' => (string)($_POST['active'] ?? '0'),
            'instances' => (string)($_POST['instances'] ?? '*'),
            'bases' => (string)($_POST['bases'] ?? '*')
        ]);

        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'Benutzer aktualisiert.' : 'Benutzer konnte nicht aktualisiert werden. Prüfe Benutzername/Rechte oder ob noch mindestens ein aktiver Admin bleibt.');
        gbdbui_users_redirect();
    }

    if ($action === 'reset_password') {
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');
        $ok = $password === $password2 && GreenQLUIv2Helper::resetPassword((int)($_POST['id'] ?? 0), $password);

        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'Passwort wurde zurückgesetzt.' : 'Passwort konnte nicht zurückgesetzt werden. Beide Felder müssen gleich sein und mindestens 8 Zeichen haben.');
        gbdbui_users_redirect();
    }

    if ($action === 'delete_user') {
        $ok = GreenQLUIv2Helper::deleteUser((int)($_POST['id'] ?? 0));
        gbdbui_flash($ok ? 'ok' : 'bad', $ok ? 'Benutzer gelöscht.' : 'Benutzer konnte nicht gelöscht werden. Eigenen Account oder letzten aktiven Admin kannst du nicht löschen.');
        gbdbui_users_redirect();
    }
}

$uiUsers = GreenQLUIv2Helper::users();
usort($uiUsers, function (array $a, array $b): int {
    $roleOrder = ['admin' => 0, 'editor' => 1, 'viewer' => 2];
    $ra = $roleOrder[(string)($a['role'] ?? 'viewer')] ?? 9;
    $rb = $roleOrder[(string)($b['role'] ?? 'viewer')] ?? 9;

    if ($ra !== $rb) {
        return $ra <=> $rb;
    }

    return strcasecmp((string)($a['username'] ?? ''), (string)($b['username'] ?? ''));
});

$activeUsers = count(array_filter($uiUsers, fn($u) => (string)($u['active'] ?? '1') === '1'));
$admins = count(array_filter($uiUsers, fn($u) => (string)($u['role'] ?? '') === 'admin'));
$editors = count(array_filter($uiUsers, fn($u) => (string)($u['role'] ?? '') === 'editor'));
$csrf = GreenQLUIv2Helper::csrf();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB UI Benutzer</title>
    <link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css">
    <link rel="stylesheet" href="assets/css/gbdb/greenql_ui.v2.css?v=2.3">
</head>
<body class="gbdbui-dashboard">
    <?php gbdbui_nav('users'); ?>
    <main class="gbdbui-wide">
        <?php gbdbui_render_flashes(); ?>
        <section class="gbdbui-hero">
            <p class="gbdbui-kicker">Zentrale Rechteverwaltung</p>
            <h1>Benutzerverwaltung</h1>
            <p>Diese User gelten für die komplette GBDB UI. Admins verwalten alles, Editor dürfen GreenQL-Schreibbereiche nutzen und Viewer sehen nur freigegebene GreenQL-v2-Daten.</p>
        </section>

        <section class="panel users-panel gbdbui-panel-fix">
            <div class="panel-head users-head">
                <div>
                    <h2>UI Benutzer</h2>
                    <p class="muted">Interner Store: <?= gbdbui_e(GreenQLUIv2Helper::systemInstance()) ?> / system / users</p>
                </div>
                <div class="user-stats">
                    <div><strong><?= count($uiUsers) ?></strong><span>User</span></div>
                    <div><strong><?= $activeUsers ?></strong><span>aktiv</span></div>
                    <div><strong><?= $admins ?></strong><span>Admins</span></div>
                    <div><strong><?= $editors ?></strong><span>Editor</span></div>
                </div>
            </div>

            <div class="role-help-grid">
                <div><strong>admin</strong><span>Voller Zugriff auf alle UI-Bereiche, Migration, Crypto, Optimize, PHP Exec und Benutzerverwaltung.</span></div>
                <div><strong>editor</strong><span>Darf GreenQL v1/v2 nutzen und schreiben, aber keine Admin-/Maintenance-Bereiche öffnen.</span></div>
                <div><strong>viewer</strong><span>Darf GreenQL v2 nur für erlaubte Instanzen und Bases lesend verwenden.</span></div>
            </div>

            <details class="user-create-card">
                <summary>Neuen Benutzer anlegen <em>Username, Rolle und Rechte vergeben</em></summary>
                <form method="post" class="user-create-grid">
                    <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                    <input type="hidden" name="user_action" value="create_user">
                    <label>Benutzername<input name="username" placeholder="z.B. markus" required></label>
                    <label>Passwort<input name="password" type="password" placeholder="mindestens 8 Zeichen" required></label>
                    <label>Rolle
                        <select name="role">
                            <option value="viewer">viewer</option>
                            <option value="editor">editor</option>
                            <option value="admin">admin</option>
                        </select>
                    </label>
                    <label>Instanzen<input name="instances" value="*" placeholder="* oder instanz1,instanz2"></label>
                    <label class="wide">Bases<input name="bases" value="*" placeholder='* oder main,crm oder {"instanz1":["main"]}'></label>
                    <button class="primary">Benutzer anlegen</button>
                </form>
            </details>

            <div class="user-list-shell">
                <div class="user-list-head">
                    <div>
                        <h3>Benutzerliste</h3>
                        <p class="muted">Bearbeiten, Passwort zurücksetzen oder Benutzer löschen.</p>
                    </div>
                </div>

                <?php if (empty($uiUsers)): ?>
                    <div class="empty-state">Noch keine Benutzer vorhanden.</div>
                <?php else: ?>
                    <div class="user-table-wrap">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Rolle</th>
                                    <th>Status</th>
                                    <th>Instanzen</th>
                                    <th>Bases</th>
                                    <th>Angelegt</th>
                                    <th>Letzter Login</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uiUsers as $u): ?>
                                <?php
                                    $id = (int)($u['id'] ?? 0);
                                    $username = (string)($u['username'] ?? '');
                                    $role = (string)($u['role'] ?? 'viewer');
                                    $active = (string)($u['active'] ?? '1') === '1';
                                ?>
                                <tr class="<?= $active ? 'is-active' : 'is-disabled' ?>">
                                    <td>
                                        <div class="user-ident">
                                            <span class="avatar mini-avatar"><?= gbdbui_e(strtoupper(substr($username !== '' ? $username : '?', 0, 1))) ?></span>
                                            <div><strong><?= gbdbui_e($username) ?></strong><small>ID <?= gbdbui_e((string)$id) ?></small></div>
                                        </div>
                                    </td>
                                    <td><span class="role-pill role-<?= gbdbui_e($role) ?>"><?= gbdbui_e($role) ?></span></td>
                                    <td><span class="status-pill <?= $active ? 'ok' : 'off' ?>"><?= $active ? 'aktiv' : 'deaktiviert' ?></span></td>
                                    <td><code><?= gbdbui_e((string)($u['instances'] ?? '*')) ?></code></td>
                                    <td><code><?= gbdbui_e((string)($u['bases'] ?? '*')) ?></code></td>
                                    <td><?= gbdbui_e((string)($u['created_at'] ?? '')) ?></td>
                                    <td><?= gbdbui_e((string)($u['last_login'] ?? '')) ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <button type="button" class="secondary xs" data-toggle="edit-<?= $id ?>">Bearbeiten</button>
                                            <button type="button" class="secondary xs" data-toggle="pwd-<?= $id ?>">Passwort</button>
                                            <form method="post" onsubmit="return confirm('Benutzer <?= gbdbui_e($username) ?> wirklich löschen?')">
                                                <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                                <input type="hidden" name="user_action" value="delete_user">
                                                <input type="hidden" name="id" value="<?= gbdbui_e((string)$id) ?>">
                                                <button class="danger xs">Löschen</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="edit-<?= $id ?>" class="user-foldout" hidden>
                                    <td colspan="8">
                                        <form method="post" class="user-inline-grid">
                                            <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                            <input type="hidden" name="user_action" value="update_user">
                                            <input type="hidden" name="id" value="<?= gbdbui_e((string)$id) ?>">
                                            <label>Benutzername<input name="username" value="<?= gbdbui_e($username) ?>" required></label>
                                            <label>Rolle
                                                <select name="role">
                                                    <?php foreach (['viewer', 'editor', 'admin'] as $r): ?>
                                                    <option value="<?= gbdbui_e($r) ?>" <?= $role === $r ? 'selected' : '' ?>><?= gbdbui_e($r) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label>Status
                                                <select name="active">
                                                    <option value="1" <?= $active ? 'selected' : '' ?>>aktiv</option>
                                                    <option value="0" <?= !$active ? 'selected' : '' ?>>deaktiviert</option>
                                                </select>
                                            </label>
                                            <label>Instanzen<input name="instances" value="<?= gbdbui_e((string)($u['instances'] ?? '*')) ?>"></label>
                                            <label class="wide">Bases<input name="bases" value="<?= gbdbui_e((string)($u['bases'] ?? '*')) ?>"></label>
                                            <div class="user-inline-actions">
                                                <button class="primary">Änderungen speichern</button>
                                                <button type="button" class="secondary" data-toggle="edit-<?= $id ?>">Schließen</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <tr id="pwd-<?= $id ?>" class="user-foldout" hidden>
                                    <td colspan="8">
                                        <form method="post" class="user-inline-grid pwd-grid">
                                            <input type="hidden" name="csrf" value="<?= gbdbui_e($csrf) ?>">
                                            <input type="hidden" name="user_action" value="reset_password">
                                            <input type="hidden" name="id" value="<?= gbdbui_e((string)$id) ?>">
                                            <label>Neues Passwort<input name="password" type="password" placeholder="mindestens 8 Zeichen" required></label>
                                            <label>Wiederholen<input name="password2" type="password" placeholder="nochmal eingeben" required></label>
                                            <div class="user-inline-actions">
                                                <button class="primary">Passwort zurücksetzen</button>
                                                <button type="button" class="secondary" data-toggle="pwd-<?= $id ?>">Schließen</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
    document.querySelectorAll('[data-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.getAttribute('data-toggle'));
            if (!target) return;
            target.hidden = !target.hidden;
        });
    });
    </script>
</body>
</html>
