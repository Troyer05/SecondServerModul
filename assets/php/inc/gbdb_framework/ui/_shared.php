<?php
declare(strict_types=1);

/**
 * Zentrale Helfer fuer die GBDB UI.
 */
function gbdbui_e(mixed $value): string {
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Baut eine URL zur zentralen GBDB UI.
 */
function gbdbui_url(string $tool = '', array $params = []): string {
    $base = (string)($_SERVER['SCRIPT_NAME'] ?? 'gbdb_ui.php');
    if ($base === '') $base = 'gbdb_ui.php';

    if ($tool !== '') {
        $params = ['tool' => $tool] + $params;
    }

    $query = http_build_query($params);
    return $base . ($query !== '' ? '?' . $query : '');
}

/**
 * Rendert die globale Navigation der gebuendelten GBDB UI.
 */
function gbdbui_nav(string $active = ''): void {
    $items = [
        'dashboard' => 'Dashboard',
        'greenql_v2' => 'GreenQL v2',
        'greenql' => 'GreenQL v1',
        'php_exec' => 'PHP Exec',
        'users' => 'Benutzer',
        'migration' => 'Migration',
        'cryption' => 'Crypto',
        'optimize' => 'Optimize',
    ];

    $user = class_exists('GreenQLUIv2Helper') ? GreenQLUIv2Helper::user() : [];

    echo '<div class="gbdbui-topbar">';
    echo '<a class="gbdbui-brand" href="' . gbdbui_e(gbdbui_url('dashboard')) . '">GBDB UI</a>';
    echo '<nav>';
    foreach ($items as $key => $label) {
        if (class_exists('GreenQLUIv2Helper') && GreenQLUIv2Helper::loggedIn() && !gbdbui_can_tool($key)) {
            continue;
        }

        $class = $key === $active ? ' class="active"' : '';
        echo '<a' . $class . ' href="' . gbdbui_e(gbdbui_url($key)) . '">' . gbdbui_e($label) . '</a>';
    }
    echo '</nav>';

    if (!empty($user)) {
        echo '<form class="gbdbui-logout" method="post" action="' . gbdbui_e(gbdbui_url($active ?: 'dashboard')) . '">';
        echo '<input type="hidden" name="csrf" value="' . gbdbui_e(GreenQLUIv2Helper::csrf()) . '">';
        echo '<input type="hidden" name="gbdbui_action" value="logout">';
        echo '<span>' . gbdbui_e($user['username'] ?? '') . ' · ' . gbdbui_e($user['role'] ?? '') . '</span>';
        echo '<button type="submit">Logout</button>';
        echo '</form>';
    }

    echo '</div>';
}


/**
 * Speichert eine UI-Meldung in der Session.
 * @param string $type Meldungstyp.
 * @param string $text Meldungstext.
 * @return void
 */
function gbdbui_flash(string $type, string $text): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['gbdbui_flash'][] = ['type' => $type, 'text' => $text];
}

/**
 * Gibt alle UI-Meldungen zurück und leert den Flash-Speicher.
 * @return array Rückgabewert.
 */
function gbdbui_flashes(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $items = $_SESSION['gbdbui_flash'] ?? [];
    unset($_SESSION['gbdbui_flash']);
    return is_array($items) ? $items : [];
}

/**
 * Rendert globale Flash-Meldungen.
 * @return void
 */
function gbdbui_render_flashes(): void {
    foreach (gbdbui_flashes() as $f) {
        echo '<div class="gbdbui-flash ' . gbdbui_e($f['type'] ?? '') . '">' . gbdbui_e($f['text'] ?? '') . '</div>';
    }
}

/**
 * Prüft ob der aktuelle UI-User ein Tool öffnen darf.
 * @param string $tool Tool-Key.
 * @return bool Rückgabewert.
 */
function gbdbui_can_tool(string $tool): bool {
    if (!class_exists('GreenQLUIv2Helper')) {
        return false;
    }

    if (!GreenQLUIv2Helper::loggedIn()) {
        return false;
    }

    if ($tool === 'dashboard' || $tool === 'greenql_v2') {
        return true;
    }

    if ($tool === 'greenql') {
        return GreenQLUIv2Helper::canWrite();
    }

    return GreenQLUIv2Helper::isAdmin();
}

/**
 * Erzwingt Zugriff auf ein UI-Tool.
 * @param string $tool Tool-Key.
 * @return void
 */
function gbdbui_require_tool(string $tool): void {
    if (!gbdbui_can_tool($tool)) {
        http_response_code(403);
        echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kein Zugriff</title><link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css"></head><body class="gbdbui-dashboard">';
        gbdbui_nav('dashboard');
        echo '<main><section class="gbdbui-hero"><h1>Kein Zugriff</h1><p>Deine aktuelle Rolle darf diesen Bereich nicht öffnen.</p><a class="gbdbui-btn" href="' . gbdbui_e(gbdbui_url('dashboard')) . '">Zurück zum Dashboard</a></section></main></body></html>';
        exit;
    }
}
