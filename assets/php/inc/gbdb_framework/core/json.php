<?php

class Json {
    /**
     * Dekodiert einen JSON-String in ein PHP-Array oder Objekt
     * @param string $json Der zu dekodierende JSON-String
     * @param bool $assoc Gibt an, ob das zurückgegebene Objekt ein assoziatives Array sein soll oder nicht
     * @return mixed Das dekodierte JSON als Array oder Objekt
     */
    public static function decode($json, $assoc = false) {
        return json_decode($json, $assoc);
    }

    /**
     * Kodiert ein PHP-Array oder Objekt in einen JSON-String
     * @param mixed $data Das zu kodierende Array oder Objekt
     * @return string Der JSON-String
     */
    public static function encode($data) {
        return json_encode($data);
    }

    /**
     * Überprüft, ob eine Zeichenkette ein gültiges JSON ist
     * @param string $json Die zu überprüfende Zeichenkette
     * @return bool Gibt zurück, ob die Zeichenkette ein gültiges JSON ist (true) oder nicht (false)
     */
    public static function isJson($json) {
        json_decode($json);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Iteriert über jedes Element eines Arrays oder Objekts und wendet eine Callback-Funktion darauf an
     * @param mixed $data Das Array oder Objekt, über das iteriert werden soll
     * @param callable $callback Die Callback-Funktion, die auf jedes Element angewendet werden soll
     * @return mixed Das modifizierte Array oder Objekt
     */
    public static function loop($data, $callback) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = call_user_func($callback, $value, $key);
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = call_user_func($callback, $value, $key);
            }
        }
        
        return $data;
    }

    /**
     * Überprüft, ob ein bestimmtes Element in einem JSON-Array oder Objekt existiert
     * @param mixed $data Das JSON-Array oder -Objekt
     * @param string $key Der Schlüssel des zu überprüfenden Elements
     * @return bool Gibt zurück, ob das Element existiert (true) oder nicht (false)
     */
    public static function elementExists($data, $key) {
        if (is_array($data)) {
            return array_key_exists($key, $data);
        } elseif (is_object($data)) {
            return property_exists($data, $key);
        }

        return false;
    }

    /**
     * Ruft die Daten eines bestimmten Elements aus einem JSON-Array oder -Objekt ab, falls es existiert
     * @param mixed $data Das JSON-Array oder -Objekt
     * @param string $key Der Schlüssel des Elements
     * @return mixed Die Daten des Elements, falls vorhanden, ansonsten null
     */
    public static function getElement($data, $key) {
        if (self::elementExists($data, $key)) {
            if (is_array($data)) {
                return $data[$key];
            } elseif (is_object($data)) {
                return $data->$key;
            }
        }

        return null;
    }
}
