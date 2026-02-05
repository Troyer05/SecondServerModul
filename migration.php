<?php
include 'assets/php/inc/.config/_config.inc.php';

/**
 * Diese Datei unter keinen Umständen zugänglich in Produktivumgebungen machen!
 * Diese Datei ist nur für Entwickler gedacht!
 * 
 * GBDB Crypto UI Migrator
 * - UI: Encrypt / Decrypt
 * - ENV wird NICHT verändert
 * - Macht Backup des kompletten GBDB-Ordners
 *
 * Erwartete Endungen (laut deiner ENV):
 *  - crypt=false => .json
 *  - crypt=true  => .db
 */

if (!Vars::__DEV__()) exit;

$EXT_PLAIN = '.json';
$EXT_ENC   = '.db';

$GBDB_ROOT = rtrim(Vars::DB_PATH(), "/") . "/";          // .../assets/DB/GBDB/
$GBDB_PARENT = rtrim(dirname(rtrim($GBDB_ROOT, "/")), "/") . "/"; // .../assets/DB/

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function ensure_dir(string $dir): void {
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
}

function atomic_write(string $file, string $payload): bool {
    $dir = dirname($file);
    ensure_dir($dir);
    $tmp = $file . '.' . uniqid('tmp_', true);
    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) return false;
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
    return true;
}

/**
 * Deterministischer Token (stabil, geheimnisbasiert)
 * -> entspricht dem Ansatz aus deiner neuen gbdb_sys.php
 */
function name_token(string $plain, string $ns = 'g'): string {
    $key  = (string)Vars::cryptKey();
    $data = $ns . '|' . $plain;

    $raw  = hash_hmac('sha256', $data, $key, true);
    $b64  = base64_encode($raw);
    $safe = rtrim(strtr($b64, '+/', '-_'), '=');

    return 'gb_' . $safe;
}

function json_pretty_flags(): int {
    // nur für UI-Tool: orientiert sich an Vars::jpretty()
    return Vars::jpretty();
}

function read_plain_json(string $file): array {
    $raw = @file_get_contents($file);
    if ($raw === false) return [];
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}

function read_enc_db(string $file): array {
    $raw = @file_get_contents($file);
    if ($raw === false) return [];
    $decoded = Crypt::decode($raw);
    if ($decoded === null) return [];
    $arr = json_decode($decoded, true);
    return is_array($arr) ? $arr : [];
}

function write_plain_json(string $file, array $data): bool {
    $json = json_encode($data, json_pretty_flags());
    if ($json === false) return false;
    return atomic_write($file, $json);
}

function write_enc_db(string $file, array $data): bool {
    $json = json_encode($data, json_pretty_flags());
    if ($json === false) return false;
    $payload = Crypt::encode($json);
    return atomic_write($file, $payload);
}

/**
 * Index-Dateien (wie in gbdb_sys.php):
 * - global: __db_index__
 * - pro DB: __table_index__
 *
 * Datei-Name ist tokenisiert, und Inhalt ist im enc-mode verschlüsselt (.db)
 */
function db_index_filename(string $extEnc): string {
    return name_token('__db_index__', 'meta') . $extEnc;
}
function table_index_filename(string $extEnc): string {
    return name_token('__table_index__', 'meta') . $extEnc;
}

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

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        if (is_dir($p)) rrmdir($p);
        else @unlink($p);
    }
    @rmdir($dir);
}

function detect_state(string $gbdbRoot, string $extEnc, string $extPlain): string {
    // heuristisch:
    // - wenn db_index(.db) existiert => encrypted schema
    // - wenn viele .json Tabellen => plain
    $encIdx = $gbdbRoot . db_index_filename($extEnc);
    if (is_file($encIdx)) return 'encrypted';

    // scan for any .json table files
    foreach (@scandir($gbdbRoot) ?: [] as $d) {
        if ($d === '.' || $d === '..') continue;
        $p = $gbdbRoot . $d;
        if (!is_dir($p)) continue;
        foreach (@scandir($p) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with($f, $extPlain)) return 'plain';
        }
    }
    return 'unknown';
}

/**
 * Dump: plain structure -> [dbPlain => [tablePlain => tableArray]]
 */
function dump_plain(string $gbdbRoot, string $extPlain): array {
    $out = [];
    foreach (scandir($gbdbRoot) as $dbDir) {
        if ($dbDir === '.' || $dbDir === '..') continue;
        $dbPath = $gbdbRoot . $dbDir;
        if (!is_dir($dbPath)) continue;

        $tables = [];
        foreach (scandir($dbPath) as $f) {
            if ($f === '.' || $f === '..') continue;
            if (!str_ends_with($f, $extPlain)) continue;
            $tablePlain = substr($f, 0, -strlen($extPlain));
            $tables[$tablePlain] = read_plain_json($dbPath . '/' . $f);
        }
        $out[$dbDir] = $tables;
    }
    return $out;
}

/**
 * Dump: encrypted schema -> [dbPlain => [tablePlain => tableArray]]
 * (liest db_index + table_index + tokenfiles)
 */
function dump_encrypted(string $gbdbRoot, string $extEnc): array {
    $out = [];

    $dbIdxFile = $gbdbRoot . db_index_filename($extEnc);
    if (!is_file($dbIdxFile)) return [];

    $dbIdxTable = read_enc_db($dbIdxFile);
    $dbMap = parse_index_table($dbIdxTable);

    foreach ($dbMap as $dbPlain => $dbToken) {
        $dbPath = $gbdbRoot . $dbToken . '/';
        if (!is_dir($dbPath)) continue;

        $tblIdxFile = $dbPath . table_index_filename($extEnc);
        if (!is_file($tblIdxFile)) {
            $out[$dbPlain] = [];
            continue;
        }

        $tblIdxTable = read_enc_db($tblIdxFile);
        $tblMap = parse_index_table($tblIdxTable);

        $tables = [];
        foreach ($tblMap as $tblPlain => $tblToken) {
            $tblFile = $dbPath . $tblToken . $extEnc;
            if (!is_file($tblFile)) continue;
            $tables[$tblPlain] = read_enc_db($tblFile);
        }

        $out[$dbPlain] = $tables;
    }

    return $out;
}

/**
 * Write encrypted schema (token dirs/files + indices) into targetRoot
 */
function write_encrypted_schema(string $targetRoot, array $dump, string $extEnc): array {
    $log = [];

    ensure_dir($targetRoot);

    $dbMap = [];
    foreach ($dump as $dbPlain => $_tables) {
        $dbToken = name_token('db:' . $dbPlain, 'db');
        $dbMap[$dbPlain] = $dbToken;
    }

    // db_index schreiben
    $dbIdxPath = $targetRoot . db_index_filename($extEnc);
    $dbIdxTable = build_index_table($dbMap);
    if (!write_enc_db($dbIdxPath, $dbIdxTable)) {
        $log[] = "❌ Konnte DB-Index nicht schreiben: {$dbIdxPath}";
        return $log;
    }

    foreach ($dump as $dbPlain => $tables) {
        $dbToken = $dbMap[$dbPlain];
        $dbDir = $targetRoot . $dbToken . '/';
        ensure_dir($dbDir);

        // table_index erstellen
        $tblMap = [];
        foreach ($tables as $tblPlain => $_content) {
            $tblToken = name_token('tbl:' . $dbPlain . '|' . $tblPlain, 'tbl');
            $tblMap[$tblPlain] = $tblToken;
        }

        $tblIdxPath = $dbDir . table_index_filename($extEnc);
        $tblIdxTable = build_index_table($tblMap);
        if (!write_enc_db($tblIdxPath, $tblIdxTable)) {
            $log[] = "❌ Konnte Table-Index nicht schreiben: {$tblIdxPath}";
            continue;
        }

        foreach ($tables as $tblPlain => $content) {
            $tblToken = $tblMap[$tblPlain];
            $tblFile  = $dbDir . $tblToken . $extEnc;

            if (!write_enc_db($tblFile, is_array($content) ? $content : [])) {
                $log[] = "❌ Konnte Tabelle nicht schreiben: {$dbPlain}/{$tblPlain}";
            } else {
                $log[] = "✅ Encoded: {$dbPlain}/{$tblPlain}";
            }
        }
    }

    return $log;
}

/**
 * Write plain structure into targetRoot
 */
function write_plain_schema(string $targetRoot, array $dump, string $extPlain): array {
    $log = [];
    ensure_dir($targetRoot);

    foreach ($dump as $dbPlain => $tables) {
        $dbDir = $targetRoot . $dbPlain . '/';
        ensure_dir($dbDir);

        foreach ($tables as $tblPlain => $content) {
            $tblFile = $dbDir . $tblPlain . $extPlain;
            if (!write_plain_json($tblFile, is_array($content) ? $content : [])) {
                $log[] = "❌ Konnte Tabelle nicht schreiben: {$dbPlain}/{$tblPlain}";
            } else {
                $log[] = "✅ Decoded: {$dbPlain}/{$tblPlain}";
            }
        }
    }

    return $log;
}

/**
 * Swap GBDB_ROOT with tmpRoot and create backup
 */
function swap_with_backup(string $gbdbRoot, string $parent, string $tmpRoot): string {
    $ts = date('Ymd_His');
    $backup = rtrim($parent, "/") . "/GBDB_backup_" . $ts;

    // GBDB dir name ist am Ende "GBDB"
    $gbdbDirName = basename(rtrim($gbdbRoot, "/"));
    $currentPath = rtrim($gbdbRoot, "/");

    // move current -> backup
    if (is_dir($currentPath)) {
        if (!@rename($currentPath, $backup)) {
            return "❌ Backup fehlgeschlagen (rename): {$currentPath} -> {$backup}";
        }
    }

    // move tmp -> GBDB
    if (!@rename(rtrim($tmpRoot, "/"), $currentPath)) {
        // rollback try
        @rename($backup, $currentPath);
        return "❌ Swap fehlgeschlagen (rename): {$tmpRoot} -> {$currentPath} (Rollback versucht)";
    }

    return "✅ Backup erstellt: {$backup}";
}

/* ===============================
   UI ACTION
   =============================== */

$logs = [];
$errors = [];
$state = detect_state($GBDB_ROOT, $EXT_ENC, $EXT_PLAIN);

$action = $_POST['action'] ?? '';
$confirm = ($_POST['confirm'] ?? '') === 'yes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$confirm) {
        $errors[] = "Bitte bestätige die Checkbox (Backup + Umstellung).";
    } elseif ($action !== 'encrypt' && $action !== 'decrypt') {
        $errors[] = "Ungültige Aktion.";
    } else {

        $tmpRoot = $GBDB_PARENT . "GBDB__tmp_migrate__/";
        if (is_dir($tmpRoot)) rrmdir($tmpRoot);
        ensure_dir($tmpRoot);

        if ($action === 'encrypt') {
            // Quelle: plain
            $dump = dump_plain($GBDB_ROOT, $EXT_PLAIN);
            $logs[] = "Quelle gelesen (plain): " . count($dump) . " DB(s)";
            $logs = array_merge($logs, write_encrypted_schema($tmpRoot, $dump, $EXT_ENC));
            $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
            $logs[] = "⚠️ Danach ENV.php: crypt_data() auf TRUE setzen (manuell).";
        }

        if ($action === 'decrypt') {
            // Quelle: encrypted
            $dump = dump_encrypted($GBDB_ROOT, $EXT_ENC);
            $logs[] = "Quelle gelesen (encrypted): " . count($dump) . " DB(s)";
            $logs = array_merge($logs, write_plain_schema($tmpRoot, $dump, $EXT_PLAIN));
            $logs[] = swap_with_backup($GBDB_ROOT, $GBDB_PARENT, $tmpRoot);
            $logs[] = "⚠️ Danach ENV.php: crypt_data() auf FALSE setzen (manuell).";
        }

        // tmpRoot sollte jetzt nicht mehr existieren (renamed). Falls doch: clean
        if (is_dir($tmpRoot)) rrmdir($tmpRoot);
    }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>GBDB Crypto Migrator</title>
  <style>
    :root{
      --bg:#0b1020;
      --panel: rgba(255,255,255,.06);
      --border: rgba(255,255,255,.12);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.65);
      --good:#2ee59d;
      --bad:#ff5c7a;
      --warn:#ffcc5c;
      --r:16px;
      --font: system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;
      --shadow: 0 18px 50px rgba(0,0,0,.35);
      --max: 980px;
    }
    *{box-sizing:border-box}
    body{
      margin:0; font-family:var(--font); color:var(--text);
      background: linear-gradient(180deg, #050710, var(--bg));
      min-height:100vh;
      padding: 28px 16px;
    }
    .wrap{max-width:var(--max); margin:0 auto;}
    .card{
      background: var(--panel);
      border:1px solid var(--border);
      border-radius: var(--r);
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .hd{padding:18px 18px 10px}
    .bd{padding: 0 18px 18px}
    h1{margin:0 0 6px; font-size: 20px}
    p{margin:0 0 12px; color:var(--muted); line-height:1.5}
    .row{display:flex; flex-wrap:wrap; gap:12px; align-items:center}
    .pill{
      display:inline-flex; gap:8px; align-items:center;
      padding:6px 10px; border:1px solid var(--border);
      border-radius: 999px; color: var(--muted);
      background: rgba(255,255,255,.03);
      font-size: 13px;
    }
    .pill b{color:var(--text); font-weight:600}
    fieldset{
      border:1px solid var(--border);
      border-radius: 12px;
      padding: 12px;
      margin: 12px 0;
      background: rgba(255,255,255,.03);
    }
    legend{padding:0 8px; color:var(--muted)}
    label{display:flex; gap:10px; align-items:flex-start; padding:8px 6px; cursor:pointer}
    input[type="radio"]{margin-top:2px}
    .btns{display:flex; gap:10px; flex-wrap:wrap; margin-top: 10px}
    button{
      border:1px solid var(--border);
      background: rgba(255,255,255,.06);
      color: var(--text);
      padding: 10px 12px;
      border-radius: 12px;
      cursor:pointer;
      font-weight:600;
    }
    button.primary{border-color: rgba(46,229,157,.35); background: rgba(46,229,157,.10)}
    .note{color:var(--muted); font-size: 13px}
    .err{border-left:4px solid var(--bad); padding:10px 12px; background: rgba(255,92,122,.08); border-radius: 12px; margin: 10px 0}
    .ok{border-left:4px solid var(--good); padding:10px 12px; background: rgba(46,229,157,.08); border-radius: 12px; margin: 10px 0}
    pre{
      white-space: pre-wrap;
      background: rgba(0,0,0,.35);
      border:1px solid var(--border);
      border-radius: 12px;
      padding: 12px;
      margin: 10px 0 0;
      color: rgba(255,255,255,.9);
      overflow:auto;
      max-height: 420px;
    }
    .warn{color: var(--warn)}
    .k{color: rgba(255,255,255,.85); font-weight:600}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="hd">
        <h1>GBDB Crypto Migrator (UI)</h1>
        <p>Konvertiert deine komplette GBDB-Struktur zwischen <span class="k">unverschlüsselt</span> (Ordner/Dateien normal, Inhalt JSON)
           und <span class="k">verschlüsselt</span> (Ordner/Dateien tokenisiert + Inhalt verschlüsselt).</p>

        <div class="row">
          <span class="pill">GBDB Path: <b><?=h($GBDB_ROOT)?></b></span>
          <span class="pill">Erkannt: <b><?=h($state)?></b></span>
          <span class="pill">Plain Ext: <b><?=h($EXT_PLAIN)?></b></span>
          <span class="pill">Enc Ext: <b><?=h($EXT_ENC)?></b></span>
        </div>
      </div>

      <div class="bd">
        <?php if ($errors): ?>
          <div class="err">
            <?php foreach ($errors as $e): ?>
              <div>❌ <?=h($e)?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($logs): ?>
          <div class="ok">
            <div>✅ Vorgang abgeschlossen. Details unten.</div>
          </div>
        <?php endif; ?>

        <form method="post">
          <fieldset>
            <legend>Aktion wählen</legend>

            <label>
              <input type="radio" name="action" value="encrypt" <?=($action==='encrypt'?'checked':'')?>>
              <div>
                <div><b>Unverschlüsselt → Verschlüsselt</b></div>
                <div class="note">Erzeugt Token-Ordner/Dateien + Index-Dateien + verschlüsselt Inhalte in <b><?=h($EXT_ENC)?></b>.</div>
              </div>
            </label>

            <label>
              <input type="radio" name="action" value="decrypt" <?=($action==='decrypt'?'checked':'')?>>
              <div>
                <div><b>Verschlüsselt → Unverschlüsselt</b></div>
                <div class="note">Liest Index-Dateien, entschlüsselt Inhalte und schreibt Klartext-Struktur als <b><?=h($EXT_PLAIN)?></b>.</div>
              </div>
            </label>

            <label>
              <input type="checkbox" name="confirm" value="yes">
              <div>
                <div><b>Ich bestätige:</b> es wird ein Backup des kompletten <code>GBDB</code>-Ordners erstellt und danach umgeschaltet.</div>
                <div class="note warn">ENV.php wird NICHT angefasst – danach musst du <b>crypt_data()</b> manuell passend setzen.</div>
              </div>
            </label>
          </fieldset>

          <div class="btns">
            <button class="primary" type="submit">Migration starten</button>
          </div>
        </form>

        <?php if ($logs): ?>
          <pre><?php foreach ($logs as $l) echo h($l) . "\n"; ?></pre>
        <?php else: ?>
          <p class="note">
            Tipp: Wenn „Erkannt: unknown“ angezeigt wird, kann das heißen, dass der Ordner leer ist oder gemischte Daten enthält.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
