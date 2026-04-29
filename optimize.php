<?php
include 'assets/php/inc/.config/_config.inc.php';

if (!Vars::__DEV__()) exit;

include DEV_INC . "/optimize.php";
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GBDB Optimize</title>
  <style>
    :root{--bg:#0b1020;--panel:rgba(255,255,255,.06);--border:rgba(255,255,255,.12);--text:rgba(255,255,255,.92);--muted:rgba(255,255,255,.65);--accent:#7c5cff;--r:16px;--font:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;}
    *{box-sizing:border-box}
    body{margin:0;font-family:var(--font);background:var(--bg);color:var(--text)}
    .wrap{max-width:980px;margin:0 auto;padding:22px}
    .card{background:var(--panel);border:1px solid var(--border);border-radius:var(--r);padding:16px}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    select,button{border-radius:12px;border:1px solid var(--border);background:rgba(0,0,0,.2);color:var(--text);padding:10px 12px}
    button{cursor:pointer}
    button.primary{background:var(--accent);border-color:transparent;color:#fff}
    .muted{color:var(--muted)}
    .msgs{margin:12px 0;display:grid;gap:8px}
    .msg{padding:10px 12px;border-radius:12px;border:1px solid var(--border);background:rgba(0,0,0,.2)}
    .kv{display:grid;grid-template-columns:220px 1fr;gap:8px 12px;margin-top:12px}
    code{background:rgba(0,0,0,.25);padding:2px 6px;border-radius:8px}
  </style>
</head>
<body>
<div class="wrap">
  <h1>GBDB Optimize</h1>
  <p class="muted">Compaction schreibt Base neu und leert Append. Bitte nicht bei jedem Request ausführen.</p>

  <?php if (!empty($msgs)): ?>
    <div class="msgs">
      <?php foreach($msgs as $m): ?>
        <div class="msg"><?=h($m)?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="get" class="row">
      <label class="muted">DB:</label>
      <select name="db" onchange="this.form.submit()">
        <option value="">– auswählen –</option>
        <?php foreach($dbs as $db): ?>
          <option value="<?=h($db)?>" <?=($db===$selDb?'selected':'')?>><?=h($db)?></option>
        <?php endforeach; ?>
      </select>

      <label class="muted">Table:</label>
      <select name="table">
        <option value="">– auswählen –</option>
        <?php foreach($tables as $tb): ?>
          <option value="<?=h($tb)?>" <?=($tb===$selTable?'selected':'')?>><?=h($tb)?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Anzeigen</button>
    </form>

    <hr style="border:0;border-top:1px solid rgba(255,255,255,.12);margin:14px 0;">

    <?php if ($selDb !== ''): ?>
      <div class="row">
        <form method="post">
          <input type="hidden" name="action" value="compact_all">
          <input type="hidden" name="db" value="<?=h($selDb)?>">
          <button class="primary" type="submit">Compact ALL in <?=h($selDb)?></button>
        </form>

        <?php if ($selTable !== ''): ?>
          <form method="post">
            <input type="hidden" name="action" value="compact_one">
            <input type="hidden" name="db" value="<?=h($selDb)?>">
            <input type="hidden" name="table" value="<?=h($selTable)?>">
            <button type="submit">Compact: <?=h($selTable)?></button>
          </form>
        <?php endif; ?>
      </div>

      <?php if ($selTable !== ''): ?>
        <?php if (isset($stats["error"])): ?>
          <p class="muted" style="margin-top:12px;">❌ <?=h($stats["error"])?></p>
        <?php else: ?>
          <div class="kv">
            <div class="muted">Mode</div><div><code><?=h($stats["mode"])?></code></div>

            <div class="muted">Rows / last_id</div>
            <div><code><?=h($stats["rows"])?> / <?=h($stats["last_id"])?></code></div>

            <div class="muted">append_ops (meta)</div>
            <div><code><?=h($stats["append_ops"])?></code></div>

            <div class="muted">Append lines</div>
            <div><code><?=h($stats["append_lines"])?></code></div>

            <div class="muted">Base/Meta/Append size</div>
            <div><code><?=h($stats["base_size"])?> / <?=h($stats["meta_size"])?> / <?=h($stats["append_size"])?></code></div>

            <div class="muted">Paths</div>
            <div>
              <div>Base: <code><?=h($stats["paths"]["base"] ?? '')?></code></div>
              <div>Meta: <code><?=h($stats["paths"]["meta"] ?? '')?></code></div>
              <div>Append: <code><?=h($stats["paths"]["append"] ?? '')?></code></div>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

    <?php else: ?>
      <p class="muted">Wähle zuerst eine Datenbank.</p>
    <?php endif; ?>
  </div>

  <p class="muted" style="margin-top:14px;">
    Tipp: Nach vielen Inserts/Edits/Deletes -> “Compact”.
  </p>
</div>
</body>
</html>
