<?php
include 'assets/php/inc/.config/_config.inc.php';

if (!Vars::__DEV__()) exit;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nowStamp(): string { return date('Ymd_His'); }

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
}

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_dir($p)) rrmdir($p);
        else @unlink($p);
    }
    @rmdir($dir);
}

function atomic_write(string $file, string $payload): bool {
    $dir = dirname($file);
    ensure_dir($dir);
    $tmp = $file . '.tmp_' . uniqid('', true);
    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) return false;
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
    return true;
}

/**
 * deterministischer Token (wie GBDB)
 */
function name_token(string $plain, string $ns='g'): string {
    $key  = (string)Vars::cryptKey();
    $data = $ns . '|' . (string)$plain;

    $raw  = hash_hmac('sha256', $data, $key, true);
    $b64  = base64_encode($raw);
    $safe = rtrim(strtr($b64, '+/', '-_'), '=');

    return 'gb_' . $safe;
}

/**
 * Index-Tabellen (Header+Zeilen)
 */
function build_index_table(array $mapPlainToToken): array {
    $db = [];
    $db[] = ["id" => -1, "plain" => "-header-", "token" => "-header-"];
    $id = 0;
    foreach ($mapPlainToToken as $plain => $token) {
        $db[] = ["id" => $id++, "plain" => (string)$plain, "token" => (string)$token];
    }
    return $db;
}

function parse_index_table(array $table): array {
    if (empty($table) || !isset($table[0]) || !is_array($table[0])) return [];
    unset($table[0]);
    $table = array_values($table);

    $map = [];
    foreach ($table as $r) {
        if (!is_array($r)) continue;
        if (!isset($r['plain'], $r['token'])) continue;
        $p = (string)$r['plain'];
        $t = (string)$r['token'];
        if ($p !== '' && $t !== '') $map[$p] = $t;
    }
    return $map;
}

/**
 * Reads ANY table file (.json or encrypted .db)
 */
function read_table_any(string $file): array {
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    if ($raw === false) return [];

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'db') {
        $decoded = Crypt::decode($raw);
        if ($decoded === null) return [];
        $arr = json_decode($decoded, true);
        return is_array($arr) ? $arr : [];
    }

    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}

/**
 * Writes table in TARGET MODE:
 * - if $encrypt=true -> Crypt::encode(JSON)
 * - else -> JSON plain
 */
function write_table_target(string $file, array $data, bool $encrypt, int $jsonFlags, bool $backupIfExists = true): bool {
    ensure_dir(dirname($file));

    if ($backupIfExists && is_file($file)) {
        @copy($file, $file . '.bak_' . nowStamp());
    }

    $json = json_encode($data, $jsonFlags);
    if ($json === false) return false;

    $payload = $encrypt ? Crypt::encode($json) : $json;

    return atomic_write($file, $payload);
}

function ensure_header(array $table, array $fallbackCols = []): array {
    if (!empty($table) && isset($table[0]) && is_array($table[0]) && isset($table[0]['id']) && (int)$table[0]['id'] === -1) {
        return $table;
    }

    $header = ['id' => -1];

    if (!empty($table) && is_array($table[0])) {
        foreach (array_keys($table[0]) as $k) {
            if ($k === 'id') continue;
            $header[$k] = '-header-';
        }
    } else {
        foreach ($fallbackCols as $k) {
            $k = (string)$k;
            if ($k === '' || $k === 'id') continue;
            $header[$k] = '-header-';
        }
    }

    array_unshift($table, $header);
    return $table;
}

function compute_meta_from_table(array $table): array {
    $lastId = 0;
    $rows = 0;

    foreach ($table as $i => $r) {
        if (!is_array($r)) continue;
        if ($i === 0 && isset($r['id']) && (int)$r['id'] === -1) continue;

        $rows++;
        if (isset($r['id'])) $lastId = max($lastId, (int)$r['id']);
    }

    return [
        "last_id"     => $lastId,
        "rows"        => $rows,
        "append_ops"  => 0,
        "indexes"     => [],
        "migrated_at" => time(),
        "updated_at"  => time(),
    ];
}

/**
 * New per-table meta/append filenames
 * plain:
 *   __meta__<table>.json
 *   __append__<table>.json
 * crypt (needs tblToken):
 *   token('__meta__|<tblToken>').db
 *   token('__append__|<tblToken>').db
 */
function meta_file_for_table_plain(string $dbDir, string $tablePlain, string $ext): string {
    return rtrim($dbDir, "/") . "/__meta__" . Format::cleanString($tablePlain) . $ext;
}
function append_file_for_table_plain(string $dbDir, string $tablePlain, string $ext): string {
    return rtrim($dbDir, "/") . "/__append__" . Format::cleanString($tablePlain) . $ext;
}
function meta_file_for_table_crypt(string $dbDir, string $tblToken, string $ext): string {
    return rtrim($dbDir, "/") . "/" . name_token('__meta__|' . $tblToken, 'meta') . $ext;
}
function append_file_for_table_crypt(string $dbDir, string $tblToken, string $ext): string {
    return rtrim($dbDir, "/") . "/" . name_token('__append__|' . $tblToken, 'meta') . $ext;
}

/**
 * GBDB schema detection (very rough)
 */
function detect_plain_structure(string $gbdbRoot): bool {
    foreach (scandir($gbdbRoot) ?: [] as $d) {
        if ($d === '.' || $d === '..') continue;
        $p = $gbdbRoot . $d;
        if (!is_dir($p)) continue;

        foreach (scandir($p) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with(strtolower($f), '.json')) return true;
        }
    }
    return false;
}

function gbdb_parent_from_root(string $gbdbRoot): string {
    return rtrim(dirname(rtrim($gbdbRoot, "/")), "/") . "/";
}

/**
 * For crypt schema: index filenames
 */
function db_index_filename(string $extEnc): string {
    return name_token('__db_index__', 'meta') . $extEnc;
}
function table_index_filename(string $extEnc): string {
    return name_token('__table_index__', 'meta') . $extEnc;
}

/**
 * Dump plain: [dbPlain => [tablePlain => tableArray]]
 * Reads both .json and .db, skips meta/append-ish files.
 */
function dump_plain_any(string $gbdbRoot): array {
    $out = [];
    foreach (scandir($gbdbRoot) ?: [] as $dbDir) {
        if ($dbDir === '.' || $dbDir === '..') continue;
        $dbPath = $gbdbRoot . $dbDir;
        if (!is_dir($dbPath)) continue;

        $tables = [];
        foreach (scandir($dbPath) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with($f, ".lock")) continue;

            $full = $dbPath . '/' . $f;
            if (!is_file($full)) continue;

            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if ($ext !== 'json' && $ext !== 'db') continue;

            $base = substr($f, 0, -(strlen($ext) + 1));

            // skip any meta/append prefixes (old/new)
            if (str_starts_with($base, '__meta__')) continue;
            if (str_starts_with($base, '__append__')) continue;

            // skip obvious index files (tokenized)
            if (str_starts_with($base, 'gb_')) {
                // could be normal table in crypt schema; but if we're dumping "plain", we still include it
                // -> we keep it only if user wants "any". Here: allow it.
            }

            $tables[$base] = read_table_any($full);
        }

        if (!empty($tables)) {
            $out[$dbDir] = $tables;
        }
    }
    return $out;
}

/**
 * Write plain schema into targetRoot (dbPlain dirs + tablePlain files)
 * + per-table meta/append
 */
function write_plain_schema_new(string $targetRoot, array $dump, string $extPlain, int $jsonFlags): array {
    $log = [];
    ensure_dir($targetRoot);

    foreach ($dump as $dbPlain => $tables) {
        $dbPlain = Format::cleanString($dbPlain);
        if ($dbPlain === '') continue;

        $dbDir = rtrim($targetRoot, "/") . "/" . $dbPlain . "/";
        ensure_dir($dbDir);

        foreach ($tables as $tblPlain => $content) {
            $tblPlain = Format::cleanString($tblPlain);
            if ($tblPlain === '') continue;

            $table = is_array($content) ? $content : [];
            $table = ensure_header($table);
            $meta  = compute_meta_from_table($table);

            $tblFile = $dbDir . $tblPlain . $extPlain;
            if (!write_table_target($tblFile, $table, false, $jsonFlags, true)) {
                $log[] = "❌ Plain table write failed: {$dbPlain}/{$tblPlain}";
                continue;
            }

            $metaFile   = meta_file_for_table_plain($dbDir, $tblPlain, $extPlain);
            $appendFile = append_file_for_table_plain($dbDir, $tblPlain, $extPlain);

            if (!write_table_target($metaFile, [ $meta ], false, $jsonFlags, true)) {
                $log[] = "❌ Meta write failed: {$dbPlain}/{$tblPlain}";
            }
            // append is line-based -> create empty
            ensure_dir(dirname($appendFile));
            if (is_file($appendFile)) @copy($appendFile, $appendFile . '.bak_' . nowStamp());
            if (@file_put_contents($appendFile, "", LOCK_EX) === false) {
                $log[] = "❌ Append init failed: {$dbPlain}/{$tblPlain}";
            }

            $log[] = "✅ Plain migrated: {$dbPlain}/{$tblPlain}";
        }
    }

    return $log;
}

/**
 * Write crypt schema into targetRoot:
 * - db_index + table_index
 * - token dirs/files
 * - per-table meta/append using tblToken
 */
function write_crypt_schema_new(string $targetRoot, array $dump, string $extEnc, int $jsonFlags): array {
    $log = [];
    ensure_dir($targetRoot);

    // build db map
    $dbMap = [];
    foreach ($dump as $dbPlain => $_tables) {
        $dbPlain = Format::cleanString($dbPlain);
        if ($dbPlain === '') continue;
        $dbMap[$dbPlain] = name_token('db:' . $dbPlain, 'db');
    }

    // write db_index
    $dbIdxPath = rtrim($targetRoot, "/") . "/" . db_index_filename($extEnc);
    $dbIdxTable = build_index_table($dbMap);
    if (!write_table_target($dbIdxPath, $dbIdxTable, true, 0, true)) {
        $log[] = "❌ Could not write db_index: " . basename($dbIdxPath);
        return $log;
    }

    foreach ($dump as $dbPlain => $tables) {
        $dbPlain = Format::cleanString($dbPlain);
        if ($dbPlain === '' || !isset($dbMap[$dbPlain])) continue;

        $dbToken = $dbMap[$dbPlain];
        $dbDir = rtrim($targetRoot, "/") . "/" . $dbToken . "/";
        ensure_dir($dbDir);

        // table map
        $tblMap = [];
        foreach ($tables as $tblPlain => $_content) {
            $tblPlain = Format::cleanString($tblPlain);
            if ($tblPlain === '') continue;
            $tblMap[$tblPlain] = name_token('tbl:' . $dbPlain . '|' . $tblPlain, 'tbl');
        }

        // write table_index
        $tblIdxPath = $dbDir . table_index_filename($extEnc);
        $tblIdxTable = build_index_table($tblMap);
        if (!write_table_target($tblIdxPath, $tblIdxTable, true, 0, true)) {
            $log[] = "❌ Could not write table_index: {$dbPlain}";
            continue;
        }

        foreach ($tables as $tblPlain => $content) {
            $tblPlain = Format::cleanString($tblPlain);
            if ($tblPlain === '' || !isset($tblMap[$tblPlain])) continue;

            $tblToken = $tblMap[$tblPlain];

            $table = is_array($content) ? $content : [];
            $table = ensure_header($table);
            $meta  = compute_meta_from_table($table);

            $tblFile = $dbDir . $tblToken . $extEnc;
            if (!write_table_target($tblFile, $table, true, 0, true)) {
                $log[] = "❌ Encrypted table write failed: {$dbPlain}/{$tblPlain}";
                continue;
            }

            $metaFile   = meta_file_for_table_crypt($dbDir, $tblToken, $extEnc);
            $appendFile = append_file_for_table_crypt($dbDir, $tblToken, $extEnc);

            if (!write_table_target($metaFile, [ $meta ], true, 0, true)) {
                $log[] = "❌ Meta write failed: {$dbPlain}/{$tblPlain}";
            }

            ensure_dir(dirname($appendFile));
            if (is_file($appendFile)) @copy($appendFile, $appendFile . '.bak_' . nowStamp());
            if (@file_put_contents($appendFile, "", LOCK_EX) === false) {
                $log[] = "❌ Append init failed: {$dbPlain}/{$tblPlain}";
            }

            $log[] = "✅ Crypt migrated: {$dbPlain}/{$tblPlain}";
        }
    }

    return $log;
}

/**
 * swap root with backup
 */
function swap_with_backup(string $gbdbRoot, string $parent, string $tmpRoot): string {
    $ts = date('Ymd_His');
    $backup = rtrim($parent, "/") . "/GBDB_backup_" . $ts;

    $currentPath = rtrim($gbdbRoot, "/");

    if (is_dir($currentPath)) {
        if (!@rename($currentPath, $backup)) {
            return "❌ Backup failed (rename): {$currentPath} -> {$backup}";
        }
    }

    if (!@rename(rtrim($tmpRoot, "/"), $currentPath)) {
        @rename($backup, $currentPath);
        return "❌ Swap failed (rename): {$tmpRoot} -> {$currentPath} (Rollback attempted)";
    }

    return "✅ Backup created: {$backup}";
}

/* ===============================
   UI
   =============================== */

$GBDB_ROOT   = rtrim(Vars::DB_PATH(), "/") . "/";
$GBDB_PARENT = gbdb_parent_from_root($GBDB_ROOT);

$do = $_POST['do'] ?? '';
$convertMode = ($_POST['convert_mode'] ?? 'no'); // no | to_target_schema
$forceRewrite = isset($_POST['force']) ? true : false;

$targetCrypt = Vars::crypt_data();
$targetExt   = Vars::data_extension();
$jsonFlags   = Vars::jpretty(); // dev pretty, prod compact (but dev-only script anyway)

$logs = [];
$errors = [];

$foundPlain = detect_plain_structure($GBDB_ROOT);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $do === 'migrate') {

    if (!$foundPlain) {
        $errors[] = "Keine plain .json Struktur erkannt (oder Ordner leer). Dieses Script ist primär für alte plain Projekte.";
    } else {

        $dump = dump_plain_any($GBDB_ROOT);

        if (empty($dump)) {
            $errors[] = "Konnte keine Tabellen dumpen (leer/korrupt?).";
        } else {

            // Mode: only upgrade in-place (plain target) OR full rebuild to target schema
            if ($convertMode === 'to_target_schema') {

                $tmpRoot = $GBDB_PARENT . "GBDB__tmp_migrate__/";
                if (is_dir($tmpRoot)) rrmdir($tmpRoot);
                ensure_dir($tmpRoot);

                if ($targetCrypt) {
                    $logs[] = "Ziel: crypt=true ({$targetExt}) → baue tokenisierte Struktur + Indices + per-table Meta/Append.";
                    $logs = array_merge($logs, write_crypt_schema_new($tmpRoot, $dump, $targetExt, $jsonFlags));
                    $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
                    $logs[] = "⚠️ ENV bleibt unverändert. Achte darauf, dass crypt_data() in ENV zu diesem Schema passt.";
                } else {
                    $logs[] = "Ziel: crypt=false ({$targetExt}) → baue plain Struktur + per-table Meta/Append.";
                    $logs = array_merge($logs, write_plain_schema_new($tmpRoot, $dump, $targetExt, $jsonFlags));
                    $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
                    $logs[] = "⚠️ ENV bleibt unverändert. Achte darauf, dass crypt_data() in ENV zu diesem Schema passt.";
                }

                // cleanup if still exists
                if (is_dir($tmpRoot)) rrmdir($tmpRoot);

            } else {
                // "no": only create per-table meta/append next to existing tables (IN-PLACE),
                // does NOT rebuild indices/token dirs.
                $logs[] = "Modus: Nur Struktur-Upgrade IN-PLACE (Base bleibt liegen).";

                foreach ($dump as $dbPlain => $tables) {
                    $dbDir = $GBDB_ROOT . $dbPlain . "/";
                    if (!is_dir($dbDir)) continue;

                    foreach ($tables as $tblPlain => $content) {
                        $tblPlain = Format::cleanString($tblPlain);
                        if ($tblPlain === '') continue;

                        $table = is_array($content) ? $content : [];
                        if (empty($table)) {
                            $logs[] = "❌ Skip empty/unreadable: {$dbPlain}/{$tblPlain}";
                            continue;
                        }

                        $table = ensure_header($table);
                        $meta  = compute_meta_from_table($table);

                        // write meta/append as NEW plain per-table names (based on TARGET EXT!)
                        // because GBDB expects meta/append in current mode.
                        $ext = $targetExt;

                        if ($targetCrypt) {
                            // In-place crypto upgrade is NOT safe (would require token dirs + indices).
                            $logs[] = "⚠️ Skip {$dbPlain}/{$tblPlain}: In-place upgrade in crypt=true ist nicht supported. Nutze 'In Zielschema umwandeln'.";
                            continue;
                        }

                        $metaFile   = meta_file_for_table_plain($dbDir, $tblPlain, $ext);
                        $appendFile = append_file_for_table_plain($dbDir, $tblPlain, $ext);

                        if ($forceRewrite || !is_file($metaFile)) {
                            if (!write_table_target($metaFile, [ $meta ], false, $jsonFlags, true)) {
                                $logs[] = "❌ Meta failed: {$dbPlain}/{$tblPlain}";
                                continue;
                            }
                        }

                        if ($forceRewrite || !is_file($appendFile)) {
                            if (is_file($appendFile)) @copy($appendFile, $appendFile . '.bak_' . nowStamp());
                            if (@file_put_contents($appendFile, "", LOCK_EX) === false) {
                                $logs[] = "❌ Append init failed: {$dbPlain}/{$tblPlain}";
                                continue;
                            }
                        }

                        $logs[] = "✅ Upgraded: {$dbPlain}/{$tblPlain}";
                    }
                }

                $logs[] = "✅ Fertig. (Hinweis: Indices/Token-Struktur werden in diesem Modus NICHT gebaut.)";
            }
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GBDB Migration</title>
  <style>
    :root{--bg:#0b1020;--panel:rgba(255,255,255,.06);--border:rgba(255,255,255,.12);--text:rgba(255,255,255,.92);--muted:rgba(255,255,255,.65);--accent:#7c5cff;--good:#2ee59d;--bad:#ff5c7a;--r:16px;--font:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;}
    *{box-sizing:border-box}
    body{margin:0;font-family:var(--font);background:var(--bg);color:var(--text)}
    .wrap{max-width:1100px;margin:0 auto;padding:22px}
    .card{background:var(--panel);border:1px solid var(--border);border-radius:var(--r);padding:16px}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
    select,input[type="checkbox"],button{border-radius:12px;border:1px solid var(--border);background:rgba(0,0,0,.2);color:var(--text);padding:10px 12px}
    button{cursor:pointer}
    button.primary{background:var(--accent);border-color:transparent;color:#fff}
    .muted{color:var(--muted)}
    .msgs{display:grid;gap:8px;margin-top:12px}
    .msg{padding:10px 12px;border-radius:12px;border:1px solid var(--border);background:rgba(0,0,0,.2)}
    .ok{border-left:4px solid var(--good)}
    .err{border-left:4px solid var(--bad)}
    code{background:rgba(0,0,0,.25);padding:2px 6px;border-radius:8px}
    pre{white-space:pre-wrap;background:rgba(0,0,0,.35);border:1px solid var(--border);border-radius:12px;padding:12px;max-height:420px;overflow:auto}
  </style>
</head>
<body>
<div class="wrap">
  <h1>GBDB Migration (Dev-only)</h1>

  <div class="card">
    <p class="muted">
      Root: <code><?=h($GBDB_ROOT)?></code><br>
      Ziel laut ENV: <code><?= $targetCrypt ? 'crypt=true (.db)' : 'crypt=false (.json)' ?></code>
      — Extension: <code><?=h($targetExt)?></code>
    </p>

    <?php if ($errors): ?>
      <div class="msgs">
        <?php foreach($errors as $e): ?>
          <div class="msg err">❌ <?=h($e)?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="row" style="margin-top:12px;">
      <input type="hidden" name="do" value="migrate">

      <label class="muted">Modus:</label>
      <select name="convert_mode">
        <option value="no">Nur Struktur-Upgrade (Meta/Append pro Tabelle, Base bleibt)</option>
        <option value="to_target_schema">In Zielschema umwandeln (Backup + Swap)</option>
      </select>

      <label class="muted">
        <input type="checkbox" name="force" value="1">
        Meta/Append neu schreiben (Backup)
      </label>

      <button class="primary" type="submit">Migration starten</button>
    </form>

    <?php if ($logs): ?>
      <pre><?php foreach($logs as $l) echo h($l)."\n"; ?></pre>
    <?php endif; ?>
  </div>

  <p class="muted" style="margin-top:14px;">
    Hinweis: Wenn du auf <code>crypt=true</code> willst, nimm <b>„In Zielschema umwandeln“</b>. In-place wäre sonst Mischzustand.
  </p>
</div>
</body>
</html>
