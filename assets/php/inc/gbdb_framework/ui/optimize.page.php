<?php
declare(strict_types=1);
require_once __DIR__ . "/_shared.php";
require_once __DIR__ . "/optimize.logic.php";
?>
<!doctype html>
<html lang="de">

<head>
    <link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Optimize</title>
    <link rel="stylesheet" href="assets/css/gbdb/optimize.css">
</head>

<body>
    <?php gbdbui_nav('optimize'); ?>
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
                <div class="muted">Mode</div>
                <div><code><?=h($stats["mode"])?></code></div>

                <div class="muted">Rows / last_id</div>
                <div><code><?=h($stats["rows"])?> / <?=h($stats["last_id"])?></code></div>

                <div class="muted">append_ops (meta)</div>
                <div><code><?=h($stats["append_ops"])?></code></div>

                <div class="muted">Append lines</div>
                <div><code><?=h($stats["append_lines"])?></code></div>

                <div class="muted">Base/Meta/Append size</div>
                <div>
                    <code><?=h($stats["base_size"])?> / <?=h($stats["meta_size"])?> / <?=h($stats["append_size"])?></code>
                </div>

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