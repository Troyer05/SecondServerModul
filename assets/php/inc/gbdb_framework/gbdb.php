<?php
declare(strict_types=1);

require_once __DIR__ . "/ENV.php";

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

$BASE = __DIR__;

gbdb_loadLocal($BASE . "/core");
gbdb_loadLocal($BASE . "/plugins");
