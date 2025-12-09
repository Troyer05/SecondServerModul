<?php
declare(strict_types=1);
require 'ENV.php';

$plugins = 'assets/php/inc/gbdb_framework/core';
$pluginPath = realpath($plugins);

if ($pluginPath && is_dir($pluginPath)) {
    foreach (glob($pluginPath . '/*.php') as $pluginFile) {
        try {
            include_once $pluginFile;
        } catch (Throwable $e) {
            error_log("[PluginLoader] Fehler beim Laden von {$pluginFile}: " . $e->getMessage());
        }
    }
}

$plugins = 'assets/php/inc/gbdb_framework/plugins';
$pluginPath = realpath($plugins);

if ($pluginPath && is_dir($pluginPath)) {
    foreach (glob($pluginPath . '/*.php') as $pluginFile) {
        try {
            include_once $pluginFile;
        } catch (Throwable $e) {
            error_log("[PluginLoader] Fehler beim Laden von {$pluginFile}: " . $e->getMessage());
        }
    }
}

Session::handler();
Cookie::init();

if (Vars::enable_https_redirect()) {
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO']) {
        $_SERVER['HTTPS'] = 1;
    }
}
?>
