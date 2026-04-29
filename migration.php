<?php
include 'assets/php/inc/.config/_config.inc.php';

if (!Vars::__DEV__()) exit;

include DEV_INC . "/migration.php";
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
