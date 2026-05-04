<?php

trait GreenQL_ParserTrait {

    /**
     * Entfernt Quotes und wandelt einfache Werte um.
     * @param string $value Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function unquote(string $value): mixed {
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return stripcslashes(substr($value, 1, -1));
            }
        }

        $low = strtolower($value);

        if ($low === "true") return 1;
        if ($low === "false") return 0;
        if ($low === "null") return null;
        if (is_numeric($value)) return $value + 0;

        return $value;
    }


    /**
     * Entfernt Kommentare aus einem Script.
     * @param string $script Übergabewert.
     * @return string Rückgabewert.
     */
    public static function stripComments(string $script): string {
        $lines = preg_split('/\r\n|\r|\n/', $script);
        $out = [];

        foreach ($lines as $line) {
            $clean = "";
            $quote = "";
            $len = strlen((string)$line);

            for ($i = 0; $i < $len; $i++) {
                $ch = $line[$i];
                $next = $i + 1 < $len ? $line[$i + 1] : "";

                if ($quote !== "") {
                    if ($ch === "\\" && $i + 1 < $len) {
                        $clean .= $ch . $line[$i + 1];
                        $i++;
                        continue;
                    }

                    if ($ch === $quote) {
                        $quote = "";
                    }

                    $clean .= $ch;
                    continue;
                }

                if ($ch === '"' || $ch === "'") {
                    $quote = $ch;
                    $clean .= $ch;
                    continue;
                }

                if ($ch === "#") {
                    break;
                }

                if ($ch === "-" && $next === "-") {
                    break;
                }

                if ($ch === "/" && $next === "/") {
                    break;
                }

                $clean .= $ch;
            }

            $out[] = rtrim($clean);
        }

        return trim(implode("\n", $out));
    }


    /**
     * Trennt ein Script in einzelne Befehle.
     * @param string $script Übergabewert.
     * @return array Rückgabewert.
     */
    public static function splitCommands(string $script): array {
        $script = self::stripComments($script);
        $commands = [];
        $buffer = '';
        $quote = '';
        $braceDepth = 0;
        $parenDepth = 0;
        $squareDepth = 0;
        $len = strlen($script);

        for ($i = 0; $i < $len; $i++) {
            $ch = $script[$i];

            if ($quote !== '') {
                if ($ch === '\\' && $i + 1 < $len) {
                    $buffer .= $ch . $script[$i + 1];
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = '';
                }

                $buffer .= $ch;
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $buffer .= $ch;
                continue;
            }

            if ($ch === '(') {
                $parenDepth++;
                $buffer .= $ch;
                continue;
            }

            if ($ch === ')') {
                $parenDepth = max(0, $parenDepth - 1);
                $buffer .= $ch;
                continue;
            }

            if ($ch === '[') {
                $squareDepth++;
                $buffer .= $ch;
                continue;
            }

            if ($ch === ']') {
                $squareDepth = max(0, $squareDepth - 1);
                $buffer .= $ch;
                continue;
            }

            if ($ch === '{') {
                $braceDepth++;
                $buffer .= $ch;
                continue;
            }

            if ($ch === '}') {
                $braceDepth = max(0, $braceDepth - 1);
                $buffer .= $ch;

                if ($braceDepth === 0 && $parenDepth === 0 && $squareDepth === 0) {
                    $trim = trim($buffer);
                    $nextRaw = substr($script, $i + 1);
                    $nextTrim = ltrim($nextRaw);

                    if (preg_match('/^IF\b/i', $trim) && preg_match('/^ELSE\b/i', $nextTrim)) {
                        continue;
                    }

                    if (preg_match('/^(IF|FOR|MAP_OBJECT|(?:PUB|PRIV)\s+F|F|C|CLASS\s+[a-zA-Z0-9_\-]+\s*\{)\b/i', $trim)) {
                        $commands[] = $trim;
                        $buffer = '';
                    }
                }

                continue;
            }

            if ($ch === ';' && $braceDepth === 0 && $parenDepth === 0 && $squareDepth === 0) {
                $command = trim($buffer);

                if ($command !== '') {
                    $commands[] = $command;
                }

                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        $buffer = trim($buffer);

        if ($buffer !== '') {
            $commands[] = $buffer;
        }

        return $commands;
    }


    /**
     * Wertet einen Wert aus.
     * @param string $value Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function evaluateValue(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/^param\(("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\')\)$/i', $value, $m)) {
            $key = (string)self::unquote((string)$m[1]);
            return $params[$key] ?? null;
        }

        if (preg_match('/^ENV\(("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\')\)$/i', $value, $m)) {
            $key = (string)self::unquote((string)$m[1]);
            return self::readEnvValue($key);
        }

        $literal = self::parseLiteral($value, $vars, $params);
        if ($literal !== null) {
            return $literal;
        }

        if (preg_match('/^(hash_sha256|hash_sha512|hash_md5|hash_adler32|hash_crc32|hash|len|ENV)\(/i', $value)) {
            return self::evaluateExpression($value, $vars, $params);
        }

        if (preg_match('/^[$]?[a-zA-Z_][a-zA-Z0-9_]*(?:\[[^\]]+\])+$/', $value)) {
            return self::resolveVariablePath($value, $vars, $params);
        }

        if (strtoupper($value) === 'NOW') {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^[$]?[a-zA-Z_][a-zA-Z0-9_]*$/', $value) && array_key_exists($value, $vars)) {
            return $vars[$value];
        }

        if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $value) && array_key_exists('_' . $value, $vars)) {
            return $vars['_' . $value];
        }

        return self::unquote($value);
    }



    /**
     * Trennt Werte quotesicher und berücksichtigt Klammer-Verschachtelungen.
     * @param string $raw Übergabewert.
     * @return array Rückgabewert.
     */
    public static function splitNested(string $raw): array {
        $out = [];
        $buffer = '';
        $quote = '';
        $round = 0;
        $square = 0;
        $curly = 0;
        $len = strlen($raw);

        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];

            if ($quote !== '') {
                if ($ch === '\\' && $i + 1 < $len) {
                    $buffer .= $ch . $raw[$i + 1];
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = '';
                }

                $buffer .= $ch;
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $buffer .= $ch;
                continue;
            }

            if ($ch === '(') $round++;
            if ($ch === ')') $round = max(0, $round - 1);
            if ($ch === '[') $square++;
            if ($ch === ']') $square = max(0, $square - 1);
            if ($ch === '{') $curly++;
            if ($ch === '}') $curly = max(0, $curly - 1);

            if ($ch === ',' && $round === 0 && $square === 0 && $curly === 0) {
                $part = trim($buffer);
                if ($part !== '') $out[] = $part;
                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        $part = trim($buffer);
        if ($part !== '') $out[] = $part;
        return $out;
    }


    /**
     * Findet einen Operator auf oberster Ebene.
     * @param string $raw Übergabewert.
     * @param string $needle Übergabewert.
     * @return int Rückgabewert.
     */
    private static function findTopLevel(string $raw, string $needle): int {
        $quote = '';
        $round = 0;
        $square = 0;
        $curly = 0;
        $len = strlen($raw);
        $nlen = strlen($needle);

        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];

            if ($quote !== '') {
                if ($ch === '\\') {
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = '';
                }

                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }

            if ($ch === '(') $round++;
            if ($ch === ')') $round = max(0, $round - 1);
            if ($ch === '[') $square++;
            if ($ch === ']') $square = max(0, $square - 1);
            if ($ch === '{') $curly++;
            if ($ch === '}') $curly = max(0, $curly - 1);

            if ($round === 0 && $square === 0 && $curly === 0 && substr($raw, $i, $nlen) === $needle) {
                return $i;
            }
        }

        return -1;
    }


    /**
     * Parst Array- und Objekt-Literale.
     * @param string $value Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function parseLiteral(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if ($value[0] === '[' && substr($value, -1) === ']') {
            $inner = trim(substr($value, 1, -1));

            if ($inner === '') {
                return [];
            }

            $assoc = self::findTopLevel($inner, ':') >= 0;
            $out = [];

            foreach (self::splitNested($inner) as $part) {
                if ($assoc) {
                    $pos = self::findTopLevel($part, ':');
                    if ($pos < 0) continue;
                    $key = trim(substr($part, 0, $pos));
                    $key = (string)self::unquote($key);
                    $out[$key] = self::evaluateValue(substr($part, $pos + 1), $vars, $params);
                } else {
                    $out[] = self::evaluateValue($part, $vars, $params);
                }
            }

            return $out;
        }

        if ($value[0] === '{' && substr($value, -1) === '}') {
            $inner = trim(substr($value, 1, -1));

            if ($inner === '') {
                return [];
            }

            $parts = self::splitNested($inner);
            $allBracketObjects = !empty($parts);

            foreach ($parts as $part) {
                $part = trim($part);
                if (!($part !== '' && $part[0] === '[' && substr($part, -1) === ']')) {
                    $allBracketObjects = false;
                    break;
                }
            }

            if ($allBracketObjects) {
                $out = [];
                foreach ($parts as $part) {
                    $out[] = self::parseLiteral($part, $vars, $params);
                }
                return $out;
            }

            $out = [];
            foreach ($parts as $part) {
                $pos = self::findTopLevel($part, ':');
                if ($pos < 0) continue;
                $key = trim(substr($part, 0, $pos));
                $key = (string)self::unquote($key);
                $out[$key] = self::evaluateValue(substr($part, $pos + 1), $vars, $params);
            }

            return $out;
        }

        return null;
    }


    /**
     * Liest verschachtelte Variablen wie _array[_i] oder _object["name"].
     * @param string $value Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function resolveVariablePath(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if (!preg_match('/^([$]?[a-zA-Z_][a-zA-Z0-9_]*)(.*)$/s', $value, $m)) {
            return null;
        }

        $name = (string)$m[1];

        if (!array_key_exists($name, $vars)) {
            return null;
        }

        $current = $vars[$name];
        $rest = trim((string)$m[2]);

        while ($rest !== '') {
            if (!preg_match('/^\[([^\]]+)\](.*)$/s', $rest, $p)) {
                return $current;
            }

            $key = self::evaluateValue((string)$p[1], $vars, $params);

            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }

            $rest = trim((string)$p[2]);
        }

        return $current;
    }


    /**
     * Wertet einfache Ausdrücke und Helper-Funktionen aus.
     * @param string $value Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return mixed Rückgabewert.
     */
    public static function evaluateExpression(string $value, array $vars = [], array $params = []): mixed {
        $value = trim($value);

        if ($value === '') return '';

        if (strlen($value) >= 2 && $value[0] === '(' && substr($value, -1) === ')' && self::findTopLevel(substr($value, 1, -1), '(') < 0) {
            return self::evaluateExpression(substr($value, 1, -1), $vars, $params);
        }

        foreach (['+', '-', '*', '/', '%'] as $op) {
            $pos = self::findMathOperator($value, $op);
            if ($pos >= 0) {
                $left = self::evaluateExpression(substr($value, 0, $pos), $vars, $params);
                $right = self::evaluateExpression(substr($value, $pos + 1), $vars, $params);

                if (!is_numeric($left) || !is_numeric($right)) {
                    if ($op === '+') return (string)$left . (string)$right;
                    return 0;
                }

                return match ($op) {
                    '+' => $left + $right,
                    '-' => $left - $right,
                    '*' => $left * $right,
                    '/' => (float)$right == 0.0 ? 0 : $left / $right,
                    '%' => (int)$right === 0 ? 0 : (int)$left % (int)$right,
                    default => 0
                };
            }
        }

        if (preg_match('/^!\s*(.+)$/s', $value, $m)) {
            return !((bool)self::evaluateExpression((string)$m[1], $vars, $params));
        }

        foreach (['==', '!=', '>=', '<=', '>', '<'] as $op) {
            $pos = self::findTopLevel($value, $op);
            if ($pos >= 0) {
                $left = self::evaluateExpression(substr($value, 0, $pos), $vars, $params);
                $right = self::evaluateExpression(substr($value, $pos + strlen($op)), $vars, $params);
                return match ($op) {
                    '==' => $left == $right,
                    '!=' => $left != $right,
                    '>=' => $left >= $right,
                    '<=' => $left <= $right,
                    '>' => $left > $right,
                    '<' => $left < $right,
                    default => false
                };
            }
        }

        if (preg_match('/^ENV\(("(?:\\.|[^"])*"|\'(?:\\.|[^\'])*\')\)$/i', $value, $m)) {
            $key = (string)self::unquote((string)$m[1]);
            return self::readEnvValue($key);
        }

        if (preg_match('/^hash_(sha256|sha512|md5|adler32|crc32)\((.*)\)$/is', $value, $m)) {
            return hash(strtolower((string)$m[1]), (string)self::evaluateValue((string)$m[2], $vars, $params));
        }

        if (preg_match('/^hash\(([^,]+),(.+)\)$/is', $value, $m)) {
            $algo = strtolower(trim((string)self::evaluateValue((string)$m[1], $vars, $params)));
            if (!in_array($algo, hash_algos(), true)) {
                return '';
            }
            return hash($algo, (string)self::evaluateValue((string)$m[2], $vars, $params));
        }

        if (preg_match('/^hash\((.*)\)$/is', $value, $m)) {
            return hash('sha256', (string)self::evaluateValue((string)$m[1], $vars, $params));
        }

        if (preg_match('/^len\((.*)\)$/is', $value, $m)) {
            $tmp = self::evaluateValue((string)$m[1], $vars, $params);
            return is_array($tmp) || $tmp instanceof Countable ? count($tmp) : strlen((string)$tmp);
        }

        return self::evaluateValue($value, $vars, $params);
    }


    /**
     * Findet einen mathematischen Operator auf oberster Ebene.
     * @param string $raw Ausdruck.
     * @param string $needle Operator.
     * @return int Position oder -1.
     */
    private static function findMathOperator(string $raw, string $needle): int {
        $quote = '';
        $round = 0;
        $square = 0;
        $curly = 0;
        $len = strlen($raw);

        for ($i = $len - 1; $i >= 0; $i--) {
            $ch = $raw[$i];

            if ($quote !== '') {
                if ($ch === $quote && ($i === 0 || $raw[$i - 1] !== '\\')) $quote = '';
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }

            if ($ch === ')') $round++;
            if ($ch === '(') $round = max(0, $round - 1);
            if ($ch === ']') $square++;
            if ($ch === '[') $square = max(0, $square - 1);
            if ($ch === '}') $curly++;
            if ($ch === '{') $curly = max(0, $curly - 1);

            if ($round !== 0 || $square !== 0 || $curly !== 0) continue;
            if ($ch !== $needle) continue;
            if (($needle === '+' || $needle === '-') && ($i === 0 || preg_match('/[+\-*\/%%(<>=!,]/', $raw[$i - 1]))) continue;

            return $i;
        }

        return -1;
    }


    /**
     * Parst Parameterblöcke für FILE.RUN.
     * @param string $raw Rohdaten.
     * @param array $vars Variablen.
     * @param array $params Parameter.
     * @return array Parameter.
     */
    private static function parseParamObject(string $raw, array $vars = [], array $params = []): array {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $data = self::parseLiteral($raw, $vars, $params);
        return is_array($data) ? $data : [];
    }


    /**
     * Parst eine kommagetrennte Liste.
     * @param string $raw Übergabewert.
     * @param array $vars Übergabewert.
     * @return array Rückgabewert.
     */
    public static function parseList(string $raw, array $vars = []): array {
        $parts = preg_split('/\s*,\s*/', trim($raw));
        $out = [];

        foreach ($parts as $part) {
            $part = self::resolveNameToken((string)$part, $vars);

            if ($part === "") {
                continue;
            }

            $out[] = $part;
        }

        return array_values(array_filter($out));
    }



    /**
     * Trennt Funktionsargumente quotesicher.
     * @param string $raw Übergabewert.
     * @return array Rückgabewert.
     */
    public static function splitArguments(string $raw): array {
        $raw = trim($raw);

        if ($raw === "") {
            return [];
        }

        $out = [];
        $buffer = "";
        $quote = "";
        $depth = 0;
        $squareDepth = 0;
        $curlyDepth = 0;
        $len = strlen($raw);

        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];

            if ($quote !== "") {
                if ($ch === "\\" && $i + 1 < $len) {
                    $buffer .= $ch . $raw[$i + 1];
                    $i++;
                    continue;
                }

                if ($ch === $quote) {
                    $quote = "";
                }

                $buffer .= $ch;
                continue;
            }

            if ($ch === "\"" || $ch === "'") {
                $quote = $ch;
                $buffer .= $ch;
                continue;
            }

            if ($ch === "(") {
                $depth++;
            } elseif ($ch === ")") {
                $depth = max(0, $depth - 1);
            } elseif ($ch === "[") {
                $squareDepth++;
            } elseif ($ch === "]") {
                $squareDepth = max(0, $squareDepth - 1);
            } elseif ($ch === "{") {
                $curlyDepth++;
            } elseif ($ch === "}") {
                $curlyDepth = max(0, $curlyDepth - 1);
            }

            if ($ch === "," && $depth === 0 && $squareDepth === 0 && $curlyDepth === 0) {
                $out[] = trim($buffer);
                $buffer = "";
                continue;
            }

            $buffer .= $ch;
        }

        $buffer = trim($buffer);

        if ($buffer !== "") {
            $out[] = $buffer;
        }

        return $out;
    }


    /**
     * Parst Zuweisungen.
     * @param string $raw Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function parseAssignments(string $raw, array $vars = [], array $params = []): array {
        $raw = trim($raw);

        if ($raw === '') return [];

        $out = [];

        foreach (self::splitNested($raw) as $part) {
            $pos = self::findTopLevel($part, '=');
            if ($pos < 0) continue;

            $key = self::cleanName((string)substr($part, 0, $pos));
            if ($key === '' || $key === 'id') continue;

            $out[$key] = self::evaluateExpression(substr($part, $pos + 1), $vars, $params);
        }

        return $out;
    }


    /**
     * Parst WHERE-Bedingungen.
     * @param string $raw Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return ?array Rückgabewert.
     */
    public static function parseWhere(string $raw, array $vars = [], array $params = []): ?array {
        $raw = trim($raw);

        if ($raw === "") {
            return null;
        }

        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(==|=|!=|>=|<=|>|<|~=)\s*(.+)$/', $raw, $m)) {
            return null;
        }

        return [
            "field" => self::cleanName((string)$m[1]),
            "op" => (string)$m[2],
            "value" => self::evaluateValue((string)$m[3], $vars, $params)
        ];
    }
}
