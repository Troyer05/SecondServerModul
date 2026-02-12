<?php

class FS {
    /** Sichere Ordner-Erstellung */
    public static function createFolder(string $pathAndName): void {
        if (!is_dir($pathAndName)) {
            @mkdir($pathAndName, 0777, true);
        }
    }

    /** File schreiben + Stream-Option */
    public static function write(string $file, mixed $data, bool $stream = false, bool $overwrite = false): bool {
        self::createFolder(dirname($file));

        if ($stream) {
            $mode = $overwrite ? 'w' : 'a';
            $f = @fopen($file, $mode);

            if (!$f) {
                error_log("[FS] Failed to open stream for {$file}");
                return false;
            }

            fwrite($f, $data);
            fclose($f);

            return true;
        }

        return file_put_contents($file, $data) !== false;
    }

    /** Datei lesen */
    public function read(string $file): mixed {
        if (!is_file($file)) return "";
        return file_get_contents($file);
    }

    /** Sicheres rekursives Löschen eines Ordners */
    public static function deleteDirectory(string $dir): bool {
        $dir = rtrim($dir, "/");

        // Schutz: gefährliche Pfade blockieren
        if ($dir === "" || $dir === "/" || strlen($dir) < 2) {
            error_log("[FS] Attempt to delete dangerous directory: {$dir}");
            return false;
        }

        if (!is_dir($dir)) {
            return false;
        }

        foreach (scandir($dir) as $file) {
            if ($file === "." || $file === "..") continue;

            $path = $dir . "/" . $file;

            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        return @rmdir($dir);
    }

    /** Ordnergröße */
    public static function getFolderSize(string $path): string {
        if (!is_dir($path)) {
            return "0 B";
        }

        $size = 0;

        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        ) as $file) {
            $size += $file->getSize();
        }

        // Formatierung
        return FileTool::dirSize($path);
    }

    /** Löscht alle Dateien innerhalb eines Ordners */
    public static function deleteFiles(string $path): bool {
        if (!is_dir($path)) {
            return false;
        }

        foreach (scandir($path) as $file) {
            if ($file === "." || $file === "..") continue;

            $filePath = $path . "/" . $file;

            if (is_file($filePath)) {
                if (!@unlink($filePath)) {
                    return false;
                }
            }
        }

        return true;
    }
}
