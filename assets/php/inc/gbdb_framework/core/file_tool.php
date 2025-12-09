<?php

class FileTool {
    /** Prüft, ob eine Datei existiert */
    public static function exists(string $path): bool {
        return is_file($path);
    }

    /** Liest den Inhalt einer Datei (UTF-8, mit Locking) */
    public static function read(string $path): string {
        if (!is_file($path)) {
            return '';
        }

        $content = @file_get_contents($path);

        return $content !== false ? $content : '';
    }

    /**
     * Sicheres Schreiben (atomic write + locking)
     * - schreibt zuerst in temp-file
     * - dann rename() → atomarer System-Call
     */
    public static function write(string $path, string $content): bool {
        self::ensureDir(dirname($path));

        $tmp = $path . '.' . uniqid('tmp_', true);

        // temp write
        if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
            error_log("[FileTool] Failed to write temp file: {$tmp}");
            return false;
        }

        // atomarer replace
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            error_log("[FileTool] Failed to rename {$tmp} → {$path}");

            return false;
        }

        return true;
    }

    /** Liest JSON und gibt Array zurück */
    public static function readJson(string $path): array {
        if (!is_file($path)) {
            return [];
        }

        $json = self::read($path);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[FileTool] JSON decode error in {$path}: " . json_last_error_msg());
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /** Sicheres JSON-Schreiben (atomic) */
    public static function writeJson(string $path, array $data): bool {
        self::ensureDir(dirname($path));

        $json = json_encode(
            $data,
            Vars::jpretty() | JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            error_log("[FileTool] JSON encode failed for {$path}");
            return false;
        }

        return self::write($path, $json);
    }

    /** Löscht Datei */
    public static function delete(string $path): bool {
        return is_file($path) ? @unlink($path) : false;
    }

    /** Kopiert ein ganzes Verzeichnis rekursiv */
    public static function copyDir(string $src, string $dest): void {
        if (!is_dir($src)) {
            return;
        }

        self::ensureDir($dest);

        $items = scandir($src);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $srcPath  = rtrim($src, '/') . '/' . $item;
            $destPath = rtrim($dest, '/') . '/' . $item;

            if (is_dir($srcPath)) {
                self::copyDir($srcPath, $destPath);
            } else {
                if (!copy($srcPath, $destPath)) {
                    error_log("[FileTool] Failed copying file {$srcPath}");
                }
            }
        }
    }

    /** Löscht Dateien älter als X Tage */
    public static function deleteOldFiles(string $dir, int $days): void {
        if (!is_dir($dir)) {
            return;
        }

        $limit = time() - ($days * 86400);

        foreach (glob(rtrim($dir, '/') . '/*') as $file) {
            if (is_file($file) && filemtime($file) < $limit) {
                @unlink($file);
            }
        }
    }

    /** Berechnet Gesamtgröße eines Verzeichnisses in MB */
    public static function dirSize(string $dir): float {
        if (!is_dir($dir)) {
            return 0.0;
        }

        $size = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return round($size / 1048576, 2);
    }

    /** Listet Dateien nach Endung */
    public static function listFiles(string $dir, string $ext = ''): array {
        if (!is_dir($dir)) {
            return [];
        }

        $result = [];

        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;

            $full = rtrim($dir, '/') . '/' . $f;

            if (!is_file($full)) continue;

            if ($ext === '' || str_ends_with($f, $ext)) {
                $result[] = $f;
            }
        }

        sort($result); // deterministisch

        return $result;
    }

    /** Backup eines gesamten Verzeichnisses */
    public static function backupDir(string $src, string $dest): bool {
        if (!is_dir($src)) {
            return false;
        }

        self::copyDir($src, $dest);

        return true;
    }

    /** Verzeichnis sicher erstellen */
    private static function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                error_log("[FileTool] Failed to create directory: {$dir}");
            }
        }
    }

    /** Alle Unterordner auflisten */
    public static function listDirs(string $path): array {
        if (!is_dir($path)) {
            return [];
        }

        $dirs = glob(rtrim($path, '/') . "/*", GLOB_ONLYDIR);

        return array_map("basename", $dirs ?: []);
    }
}
