<?php
declare(strict_types=1);

require_once __DIR__ . "/ENV.php";

/**
 * Lädt alle PHP-Dateien in einem Ordner
 */
function gbdb_loadLocal(string $folder): void {

    $path = rtrim($folder, '/\\');

    if (!is_dir($path)) {
        error_log("[GBDB Loader] Ordner fehlt: $path");
        return;
    }

    $files = glob($path . "/*.php");

    if (!$files) return;

    sort($files, SORT_STRING);

    foreach ($files as $file) {
        require_once $file;
    }
}


// === KORREKTE PFADBASIS ===
// gbdb.php befindet sich in: /assets/php/inc/gbdb_framework/
$BASE = __DIR__;


// === CORE LADEN ===
gbdb_loadLocal($BASE . "/core");


// === PLUGINS LADEN ===
gbdb_loadLocal($BASE . "/plugins");


// === GBDB SYSTEM LADEN ===
// Falls du gbdb_sys.php etc. hast, wird das automatisch geladen.
