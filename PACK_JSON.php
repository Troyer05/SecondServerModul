<?php

/**
 * Exportiert das gesamte Projekt in eine JSON-Datei (framework_files.json),
 * als key = Dateipfad, value = base64 Inhalt.
 *
 * Ignoriert automatisch: .git, framework_files.json, export_framework.php
 */

$root = __DIR__;
$outputFile = $root . "/framework_files.json";

$ignore = [
    ".git",
    ".gitignore",
    "framework_files.json",
    "export_framework.php"
];

$files = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {

    /** @var SplFileInfo $file */

    if ($file->isDir()) continue;

    $path = $file->getPathname();
    $relative = str_replace($root . DIRECTORY_SEPARATOR, "", $path);

    // Normalize path
    $relative = str_replace("\\", "/", $relative);

    // Skip ignored paths
    foreach ($ignore as $i) {
        if (str_starts_with($relative, $i)) {
            continue 2;
        }
    }

    $content = file_get_contents($path);

    if ($content !== false) {
        $files[$relative] = base64_encode($content);
    }
}

$json = json_encode(["files" => $files], JSON_PRETTY_PRINT);

file_put_contents($outputFile, $json);

echo "Export completed!\nCreated: framework_files.json\n";
