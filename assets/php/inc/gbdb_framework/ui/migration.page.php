<?php
declare(strict_types=1);
require_once __DIR__ . "/_shared.php";
require_once __DIR__ . "/migration.logic.php";
?>
<!doctype html>
<html lang="de">

<head>
    <link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GBDB Migration</title>
    <link rel="stylesheet" href="assets/css/gbdb/migration.css">
</head>

<body>
    <?php gbdbui_nav('migration'); ?>
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
            Hinweis: Wenn du auf <code>crypt=true</code> willst, nimm <b>„In Zielschema umwandeln“</b>. In-place wäre
            sonst Mischzustand.
        </p>
    </div>
</body>

</html>