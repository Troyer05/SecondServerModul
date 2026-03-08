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
 * Erwartete Endungen:
 *  - plain  => .json
 *  - enc    => .db
 */

if (!Vars::__DEV__()) exit;

include DEV_INC . '/cryption.php';
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
