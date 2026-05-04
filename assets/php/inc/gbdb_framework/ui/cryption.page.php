<?php
declare(strict_types=1);
require_once __DIR__ . "/_shared.php";
require_once __DIR__ . "/cryption.logic.php";
?>
<!doctype html>
<html lang="de">

<head>
    <link rel="stylesheet" href="assets/css/gbdb/gbdb_ui.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>GBDB Crypto Migrator</title>
    <link rel="stylesheet" href="assets/css/gbdb/cryption.css">
</head>

<body>
    <?php gbdbui_nav('cryption'); ?>
    <div class="wrap">
        <div class="card">
            <div class="hd">
                <h1>GBDB Crypto Migrator (UI)</h1>
                <p>Konvertiert deine komplette GBDB-Struktur zwischen <span class="k">unverschlüsselt</span>
                    (Ordner/Dateien normal, Inhalt JSON)
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
                                <div class="note">Erzeugt Token-Ordner/Dateien + Index-Dateien + verschlüsselt Inhalte
                                    in <b><?=h($EXT_ENC)?></b>.</div>
                            </div>
                        </label>

                        <label>
                            <input type="radio" name="action" value="decrypt" <?=($action==='decrypt'?'checked':'')?>>
                            <div>
                                <div><b>Verschlüsselt → Unverschlüsselt</b></div>
                                <div class="note">Liest Index-Dateien, entschlüsselt Inhalte und schreibt
                                    Klartext-Struktur als <b><?=h($EXT_PLAIN)?></b>.</div>
                            </div>
                        </label>

                        <label>
                            <input type="checkbox" name="confirm" value="yes">
                            <div>
                                <div><b>Ich bestätige:</b> es wird ein Backup des kompletten <code>GBDB</code>-Ordners
                                    erstellt und danach umgeschaltet.</div>
                                <div class="note warn">ENV.php wird NICHT angefasst – danach musst du
                                    <b>crypt_data()</b> manuell passend setzen.</div>
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
                    Tipp: Wenn „Erkannt: unknown“ angezeigt wird, kann das heißen, dass der Ordner leer ist oder
                    gemischte Daten enthält.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>