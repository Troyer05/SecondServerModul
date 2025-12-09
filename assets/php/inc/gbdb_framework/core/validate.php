<?php

class Validate {

    /** Prüft, ob Felder gesetzt und nicht leer sind */
    public static function required(array $data, array $fields): bool {
        foreach ($fields as $f) {
            if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
                return false;
            }
        }
        return true;
    }

    /** Prüft, ob eine gültige E-Mail-Adresse übergeben wurde */
    public static function email(string $value): bool {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /** Prüft, ob der Wert eine Zahl oder Kommazahl ist */
    public static function number(string|int|float $value): bool {
        $v = str_replace(',', '.', (string)$value);
        return is_numeric($v);
    }

    /** Prüft Mindestlänge eines Strings */
    public static function minLength(string $value, int $min): bool {
        return mb_strlen(trim($value)) >= $min;
    }

    /** Prüft Maximallänge eines Strings */
    public static function maxLength(string $value, int $max): bool {
        return mb_strlen(trim($value)) <= $max;
    }

    /** Prüft, ob ein Wert einem regulären Ausdruck entspricht */
    public static function regex(string $value, string $pattern): bool {
        return (bool) preg_match($pattern, $value);
    }

    /** Prüft, ob eine Zahl zwischen zwei Werten liegt */
    public static function between(float|int $value, float|int $min, float|int $max): bool {
        return $value >= $min && $value <= $max;
    }

    /** Prüft, ob ein Wert in einer erlaubten Liste vorkommt */
    public static function in(string|int $value, array $allowed): bool {
        return in_array($value, $allowed, true);
    }

    /** Prüft, ob zwei Strings exakt gleich sind */
    public static function match(string $a, string $b): bool {
        return $a === $b;
    }

    /**
     * Prüft ein komplettes Datenarray anhand definierter Regeln
     * Beispiel:
     * Validate::validateArray($_POST, [
     *     'email' => 'required|email',
     *     'password' => 'required|min:8|max:32'
     * ]);
     */
    public static function validateArray(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? '';
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                $param = null;

                // Parameter extrahieren (z. B. min:8)
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $valid = true;

                switch ($rule) {
                    case 'required':
                        $valid = trim((string)$value) !== '';
                        break;
                    case 'email':
                        $valid = self::email((string)$value);
                        break;
                    case 'number':
                        $valid = self::number($value);
                        break;
                    case 'min':
                        $valid = self::minLength((string)$value, (int)$param);
                        break;
                    case 'max':
                        $valid = self::maxLength((string)$value, (int)$param);
                        break;
                    case 'regex':
                        $valid = self::regex((string)$value, (string)$param);
                        break;
                    case 'in':
                        $valid = self::in((string)$value, explode(',', (string)$param));
                        break;
                }

                if (!$valid) {
                    $errors[$field][] = $rule;
                }
            }
        }

        return $errors;
    }
}
