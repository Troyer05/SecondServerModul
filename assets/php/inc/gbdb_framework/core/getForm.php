<?php

class GetForm {
    /**
     * Liest ein Dropdown aus und gibt die explizite Auswahl wieder
     * @param mixed $dropdown die POST Variable des Dropdowns (@example $_POST["drop1"])
     * @return mixed Explizite Userauswahl
     */
    public static function getDropdown(mixed $dropdown): mixed {
        $e = "";

        foreach ($dropdown as $val) {
            $e = $val;
        }

        return $e;
    }

    /**
     * Funktion zum Hochladen von Dateien (Max. 2 MB Zugelassen)
     * @param mixed $file Datei(en) der POST Methode zum Hochladen
     * @param string $path (OPTIONAL) Path Wohin die Datei(en) hochgeladen werden sollen
     * @param string $useName (OPTIONAL) Wenn die Datei einen speziellen Namen haben soll
     * @return bool true wenn es keine Probleme gab
     */
    public static function upload(mixed $file, string $path = "./", string $useName = ""): bool {
        if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > (250 * 1024)) {
            return false;
        }
    
        $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($file['name']));
        $fileName = str_replace('..', '', $fileName); 
    
        // Erlaubte Dateiendungen
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'txt', 'docx', 'doc', 'xls', 'ppt', 'ppts', 'webp'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return false;
        }
    
        if ($useName == "") {
            $fileName = uniqid() . '_' . $fileName;
        } else {
            $fileName = rtrim($useName, '.') . '.' . $fileExtension;
        }
    
        if (!is_dir($path) || !is_writable($path)) {
            return false;
        }
    
        if (move_uploaded_file($file['tmp_name'], $path . '/' . $fileName)) {
            return true;
        } else {
            return false;
        }
    }    

    public static function check_required_fields(mixed $post_data): mixed {
        $empty_fields = [];
    
        foreach ($post_data as $field_name => $value) {
            if (substr($field_name, -3) === '_rf') {
                if (empty($value) || $value == " " || $value == "  ") {
                    $empty_fields[] = $field_name;
                }
            }
        }
    
        if (empty($empty_fields)) {
            return 0;
        }
    
        return $empty_fields;
    }
    
    public static function createInput(string $name, string $type, mixed $form_data, string $placeholder = "", string $class = "", string $id = ""): string {
        $value = htmlspecialchars($form_data[$name] ?? '');
        $element = '<input type="' . $type . '" name="' . $name . '" placeholder="' . $placeholder . '" value="' . $value . '"';
        
        if (empty($class)) {
            $class = "";
        }

        // $element .= ' class="gLFyf gsfi ' . $class . '"';
        // $element .= ' jsaction="paste:puy29d;" maxlength="2048"';
        // $element .= ' aria-autocomplete="both" aria-haspopup="false" autocapitalize="off" autocomplete="off" autocorrect="off" autofocus=""';
        // $element .= ' spellcheck="false"';
        // $element .= ' aria-label="Pesquisar" data-ved="0ahUKEwjw0svW6brxAhWdqJUCHXoYDRsQ39UDCAQ"';

        if (!empty($id)) {
            $element .= ' id="' . $id . '"';
        }
        
        $element .= ' />';
        
        return $element;
    }

    public static function checkPost(): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return true;
        }

        return false;
    }
}
