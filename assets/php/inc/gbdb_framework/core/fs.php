<?php

class FS {
    /**
     * Schreibt JSON Daten in eine .json Datei
     * @param string $file der path zur .json Datei
     * @param mixed $data die JSON Daten als PHP Array, der in die Datei geschrieben werden soll
     * @param bool $add (Optional, Standart true) sollen die Daten an die Datei angefügt werden (bei false wird überschrieben)
     * @param bool $pretty (Optional, Standard false) sollen die JSON Daten schön Formatiert werden (bei false = Einzeiler)
     * @return bool true wenn es keine Probleme gab
     */
    public static function write_json(string $file, mixed $data, bool $add = true, bool $pretty = false): bool {
        $data = array_values($data);

        if ($add) {
            $tmp = self::read_json($file);
            array_push($tmp, $data);
            $data = $tmp;
        }

        $file = Vars::json_path() . $file;

        $pretty
            ? file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))
            : file_put_contents($file, json_encode($data));

        return true;
    }

    /**
     * Liest JSON Daten aus einer .json Datei heraus und stellt sie PHP Formatiert bereit
     * @param string $file der path zur .json Datei
     * @return mixed die JSON Daten 
     */
    public static function read_json(string $file): mixed {
        return json_decode(file_get_contents(Vars::json_path() . $file, true), true);
    }

    public static function createFolder(string $pathAndName): void {
        mkdir($pathAndName, 0777);
    }

    /**
     * Schreibt normale Dateien in eine Datei
     * @param string $file path zur Datei
     * @param mixed $data die Daten die in die Datei geschrieben werden sollen
     * @param bool $stream (Optional, Standart false) ob es ein Filestream sein soll oder als stack etwa später geschrieben werden kann
     * @param bool $overwrite (Optional, Standart false) ob Daten überschrieben werden sollen oder angehängt werden sollen
     * @return bool true wenn es keine Probleme gab
     */
    public static function write(string $file, mixed $data, bool $stream = false, bool $overwrite = false): bool {
        if ($stream) {
            $f = ($overwrite ? fopen($file, 'w') : fopen($file, 'a+'));
            fwrite($f, $data);
            fclose($f);

            return true;
        } else {
            file_put_contents($file, $data);
            return true;
        }
    }

    /**
     * Liest  Daten aus einer Datei heraus
     * @param string $file der path zur Datei
     * @return mixed die Daten
     */
    public function read(string $file): mixed {
        return file_get_contents($file);
    }

    /**
     * Löscht ein Verzeichnis
     * @param string $dir path zum Verzeichnis
     * @return bool true wenn es keine Probleme gab
     */
    public static function deleteDirectory(string $dir): bool {
        if (is_dir($dir)) {
            $files = scandir($dir);

            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $path = $dir . "/" . $file;

                    if (is_dir($path)) {
                        FS::deleteDirectory($path);
                    } else {
                        unlink($path);
                    }
                }
            }

            rmdir($dir);
            return true;
        }

        return false;
    }

    /**
     * Gibt die Größe eines Verzeichnisses wieder
     * @param string $path path zum Verzeichnis
     * @return string die Größe des Verzeichnisses
     */
    public static function getFolderSize(string $path): string {
        $size = 0;

        if (!file_exists('assets/wasm/fssize.wasm')) {
            die("FEHLER: fssize.wasm konnte nicht gefunden werden!");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $size += $file->getSize();
        }

        if ($size >= 1125899906842624) { // PB
            return number_format($size / 1125899906842624, 2) . ' PB';
        } elseif ($size >= 1099511627776) { // TB
            return number_format($size / 1099511627776, 2) . ' TB';
        } elseif ($size >= 1073741824) { // GB
            return number_format($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) { // MB
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) { // KB
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' B';
        }
    }

    /**
     * Löscht alle Dateien in einem Ordner
     * @param string $path Path zum Ordner
     * @return bool true wenn es keine Probleme gab
    */
    public static function deleteFiles(string $path): bool {
        if (!is_dir($path)) {
            return false;
        }
    
        $dir = opendir($path);

        if (!$dir) {
            return false;
        }
    
        while (($file = readdir($dir)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
    
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
    
            if (is_file($filePath)) {
                if (!unlink($filePath)) {
                    closedir($dir);
                    return false;
                }
            }
        }
    
        closedir($dir);
        
        return true;
    }
}
