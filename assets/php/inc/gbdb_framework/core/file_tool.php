<?php

class FileTool {

    /** Prüft, ob eine Datei existiert */
    public static function exists(string $path): bool {
        return file_exists($path);
    }

    /** Liest den Inhalt einer Datei (UTF-8) */
    public static function read(string $path): string {
        return file_exists($path) ? (string) file_get_contents($path) : '';
    }

    /** Schreibt Inhalt in eine Datei (legt sie ggf. an) */
    public static function write(string $path, string $content): bool {
        self::ensureDir(dirname($path));
        return (bool) file_put_contents($path, $content);
    }

    /** Liest JSON-Datei als Array */
    public static function readJson(string $path): array {
        if (!file_exists($path)) return [];

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /** Schreibt Array als JSON-Datei (formatiert) */
    public static function writeJson(string $path, array $data): bool {
        self::ensureDir(dirname($path));

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return (bool) file_put_contents($path, $json);
    }

    /** Löscht Datei */
    public static function delete(string $path): bool {
        return file_exists($path) ? unlink($path) : false;
    }

    /** Kopiert ein ganzes Verzeichnis rekursiv */
    public static function copyDir(string $src, string $dest): void {
        if (!is_dir($src)) return;

        self::ensureDir($dest);

        $items = scandir($src);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $srcPath = "$src/$item";
            $destPath = "$dest/$item";

            if (is_dir($srcPath)) {
                self::copyDir($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
    }

    /** Löscht alle Dateien in einem Verzeichnis, die älter als X Tage sind */
    public static function deleteOldFiles(string $dir, int $days): void {
        if (!is_dir($dir)) return;

        $limit = time() - ($days * 86400);

        foreach (glob("$dir/*") as $file) {
            if (is_file($file) && filemtime($file) < $limit) {
                unlink($file);
            }
        }
    }

    /** Berechnet Gesamtgröße eines Verzeichnisses (in MB) */
    public static function dirSize(string $dir): float {
        $size = 0;

        if (!is_dir($dir)) return 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }

        return round($size / 1048576, 2);
    }

    /** Listet alle Dateien in einem Verzeichnis (optional mit Filterendung) */
    public static function listFiles(string $dir, string $ext = ''): array {
        if (!is_dir($dir)) return [];

        $files = scandir($dir);
        $result = [];

        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;

            if ($ext === '' || str_ends_with($f, $ext)) {
                $result[] = $f;
            }
        }

        return $result;
    }

    /** Erstellt ein Backup eines Verzeichnisses */
    public static function backupDir(string $src, string $dest): bool {
        if (!is_dir($src)) return false;

        self::copyDir($src, $dest);

        return true;
    }

    /** Erstellt ein Verzeichnis, wenn es nicht existiert */
    private static function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public static function listDirs(string $path): array {
        return array_map("basename", glob($path . "/*", GLOB_ONLYDIR));
    }
}
