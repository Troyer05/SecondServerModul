<?php

trait GreenQLv2_ExecutionTrait {


    /**
     * Extrahiert den Ausdruck aus einem OUTPUT-Befehl.
     * Unterstützt OUTPUT wert; und OUTPUT(wert); ohne schließende Klammern von Funktionsaufrufen zu verschlucken.
     * @param string $command GreenQL-Befehl.
     * @return string|null Ausdruck oder null, wenn es kein OUTPUT-Befehl ist.
     */
    private static function outputExpression(string $command): ?string {
        if (!preg_match('/^OUTPUT(?:\s+|\s*\()(.+)$/is', $command, $m)) {
            return null;
        }

        $expr = trim((string)$m[1]);

        if ($expr === '') {
            return '';
        }

        $startedWithParen = preg_match('/^OUTPUT\s*\(/i', $command) === 1;

        if ($startedWithParen && strlen($expr) >= 1 && substr($expr, -1) === ')') {
            $expr = trim(substr($expr, 0, -1));
        }

        return $expr;
    }


    /**
     * Parst IF-Blöcke mit verschachtelten Klammern und optionalem ELSE.
     * @param string $command GreenQL-Befehl.
     * @return array|null [condition, ifBody, elseBody] oder null.
     */
    private static function parseIfCommand(string $command): ?array {
        $command = trim($command);
        if (!preg_match('/^IF\s*\(/i', $command)) return null;

        $len = strlen($command);
        $pos = stripos($command, '(');
        if ($pos === false) return null;

        $quote = '';
        $depth = 0;
        $condEnd = -1;

        for ($i = $pos; $i < $len; $i++) {
            $ch = $command[$i];

            if ($quote !== '') {
                if ($ch === '\\') {
                    $i++;
                    continue;
                }
                if ($ch === $quote) $quote = '';
                continue;
            }

            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }

            if ($ch === '(') $depth++;
            if ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $condEnd = $i;
                    break;
                }
            }
        }

        if ($condEnd < 0) return null;

        $condition = trim(substr($command, $pos + 1, $condEnd - $pos - 1));
        $rest = ltrim(substr($command, $condEnd + 1));
        if ($rest === '' || $rest[0] !== '{') return null;

        $readBlock = function (string $raw): array {
            $len = strlen($raw);
            $quote = '';
            $depth = 0;

            for ($i = 0; $i < $len; $i++) {
                $ch = $raw[$i];

                if ($quote !== '') {
                    if ($ch === '\\') {
                        $i++;
                        continue;
                    }
                    if ($ch === $quote) $quote = '';
                    continue;
                }

                if ($ch === '"' || $ch === "'") {
                    $quote = $ch;
                    continue;
                }

                if ($ch === '{') $depth++;
                if ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        return [substr($raw, 1, $i - 1), ltrim(substr($raw, $i + 1))];
                    }
                }
            }

            return ['', $raw];
        };

        [$ifBody, $afterIf] = $readBlock($rest);
        $elseBody = '';

        if (preg_match('/^ELSE\b/is', $afterIf)) {
            $afterElse = ltrim(preg_replace('/^ELSE\b/is', '', $afterIf, 1));
            if ($afterElse !== '' && $afterElse[0] === '{') {
                [$elseBody] = $readBlock($afterElse);
            } elseif (preg_match('/^IF\s*\(/i', $afterElse)) {
                $elseBody = $afterElse;
            }
        }

        return [$condition, $ifBody, $elseBody];
    }

    /**
     * Führt einen Script-Block aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    private static function runBlock(string $script, array &$ctx, array &$vars, array $params = []): array {
        $results = [];

        foreach (self::splitCommands($script) as $blockCommand) {
            $res = self::command($blockCommand, $ctx, $vars, $params);
            $results[] = $res;

            if (!($res['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => (string)($res['message'] ?? 'Block fehlgeschlagen.'),
                    'results' => $results,
                    'ctx' => $ctx
                ];
            }

            if (array_key_exists('back', $res)) {
                return [
                    'ok' => true,
                    'message' => 'BACK',
                    'back' => $res['back'],
                    'results' => $results,
                    'ctx' => $ctx
                ];
            }

            if (!empty($res['restart'])) {
                return [
                    'ok' => true,
                    'message' => 'RESTART',
                    'restart' => true,
                    'results' => $results,
                    'ctx' => $ctx
                ];
            }

            if (!empty($res['end_proc'])) {
                return [
                    'ok' => true,
                    'message' => 'END_PROC',
                    'end_proc' => true,
                    'results' => $results,
                    'ctx' => $ctx
                ];
            }
        }

        return [
            'ok' => true,
            'message' => 'Block ausgeführt.',
            'results' => $results,
            'ctx' => $ctx
        ];
    }


    /**
     * Führt einen einzelnen GreenQL-Befehl aus.
     * @param string $command Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $vars Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    public static function command(string $command, array &$ctx = [], array &$vars = [], array $params = []): array {
        $command = trim($command);

        self::syncInstance($ctx);
        $driver = self::db();

        if ($command === "") {
            return [
                "ok" => true,
                "message" => ""
            ];
        }

        if (preg_match('/^CALL\s+([a-zA-Z_][a-zA-Z0-9_]*)\/([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $command, $m)) {
            $command = 'CLASS ' . $m[1] . '/' . $m[2] . '(' . $m[3] . ')';
        }

        if (preg_match('/^META\s*=\s*(.+)$/is', $command, $m)) {
            $ctx['meta'] = self::parseParamObject((string)$m[1], $vars, $params);
            return ['ok' => true, 'message' => 'META gelesen.', 'ctx' => $ctx, 'result' => $ctx['meta']];
        }

        if (preg_match('/^(?:DECLARE|DECALRE|DELACE)\s+([$]?[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(CALL\s+.+)$/is', $command, $m)) {
            $name = self::cleanVarName((string)$m[1]);
            $call = self::command((string)$m[2], $ctx, $vars, $params);

            if (!($call['ok'] ?? false)) {
                return $call;
            }

            return self::setVar($name, $call['back'] ?? ($call['result'] ?? null), $ctx, $vars);
        }

        if (preg_match('/^BACK\s+(.+)$/is', $command, $m)) {
            return [
                'ok' => true,
                'message' => 'BACK',
                'back' => self::evalRuntimeExpression((string)$m[1], $ctx, $vars, $params),
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^FILE\.BACK\s+(.+)$/is', $command, $m)) {
            return [
                'ok' => true,
                'message' => 'FILE.BACK',
                'back' => self::evalRuntimeExpression((string)$m[1], $ctx, $vars, $params),
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^END_PROC$/i', $command)) {
            return ['ok' => true, 'message' => 'END_PROC', 'end_proc' => true, 'ctx' => $ctx];
        }

        if (preg_match('/^this_f\.restart\s*\(\s*\)$/i', $command)) {
            return ['ok' => true, 'message' => 'Funktion wird neu gestartet.', 'restart' => true, 'ctx' => $ctx];
        }

        $outputExpression = self::outputExpression($command);
        if ($outputExpression !== null) {
            $value = self::evalRuntimeExpression($outputExpression, $ctx, $vars, $params);
            $display = is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string)$value;

            return [
                'ok' => true,
                'message' => 'OUTPUT',
                'keys' => ['output'],
                'rows' => [[
                    'output' => $display
                ]],
                'result' => $value,
                'ctx' => $ctx
            ];
        }


        if (preg_match('/^SET_LOGFILE\s*\((.*)\)$/is', $command, $m)) {
            $args = self::evalArgs((string)$m[1], $vars, $params);
            $file = self::resolveLogPath((string)($args[0] ?? ''));

            if ($file === '') {
                return ['ok' => false, 'message' => 'Log-Dateipfad ungültig oder nicht erlaubt.', 'ctx' => $ctx];
            }

            $ctx['logfile'] = $file;
            self::$defaultLogFile = $file;

            return ['ok' => true, 'message' => 'Logfile gesetzt: ' . str_replace(self::scriptRoot() . '/', '', $file), 'ctx' => $ctx, 'result' => $file];
        }

        if (preg_match('/^LOG\s*\((.*)\)$/is', $command, $m)) {
            $file = self::activeLogFile($ctx);

            if ($file === '') {
                return ['ok' => false, 'message' => 'Kein Logfile gesetzt. Nutze SET_LOGFILE("path/to/log.txt").', 'ctx' => $ctx];
            }

            $args = self::splitArguments((string)$m[1]);
            $values = [];

            foreach ($args as $arg) {
                $values[] = self::evalRuntimeExpression($arg, $ctx, $vars, $params);
            }

            $value = count($values) === 1 ? $values[0] : $values;
            $ok = self::writeLogLine($file, $value);

            return ['ok' => $ok, 'message' => $ok ? 'Log geschrieben.' : 'Log konnte nicht geschrieben werden.', 'ctx' => $ctx, 'result' => $value];
        }

        if (preg_match('/^CLEAR_LOG\s*\(\s*\)$/is', $command)) {
            $file = self::activeLogFile($ctx);

            if ($file === '') {
                return ['ok' => false, 'message' => 'Kein Logfile gesetzt. Nutze SET_LOGFILE("path/to/log.txt").', 'ctx' => $ctx];
            }

            $ok = @file_put_contents($file, '', LOCK_EX) !== false;
            return ['ok' => $ok, 'message' => $ok ? 'Log geleert.' : 'Log konnte nicht geleert werden.', 'ctx' => $ctx];
        }

        if (preg_match('/^DELETE_LOG_FILE\s*\(\s*\)$/is', $command)) {
            $file = self::activeLogFile($ctx);

            if ($file === '') {
                return ['ok' => false, 'message' => 'Kein Logfile gesetzt. Nutze SET_LOGFILE("path/to/log.txt").', 'ctx' => $ctx];
            }

            $ok = !is_file($file) || @unlink($file);
            if ($ok) {
                unset($ctx['logfile']);
                self::$defaultLogFile = '';
            }

            return ['ok' => $ok, 'message' => $ok ? 'Logdatei gelöscht.' : 'Logdatei konnte nicht gelöscht werden.', 'ctx' => $ctx];
        }

        if (preg_match('/^FILE\.INCLUDE\s+(.+)$/is', $command, $m)) {
            $file = self::resolveScriptPath((string)$m[1]);

            if ($file === '') {
                return ['ok' => false, 'message' => 'Include-Datei nicht gefunden oder nicht erlaubt.', 'ctx' => $ctx];
            }

            $res = self::runBlock((string)file_get_contents($file), $ctx, $vars, $params);
            if (!empty($res['ok'])) $res['message'] = 'Datei inkludiert: ' . basename($file);
            return $res;
        }

        if (preg_match('/^FILE\.RUN\s+(.+?)(?:\s+(\{.*\}|\[.*\]))?$/is', $command, $m)) {
            $file = self::resolveScriptPath((string)$m[1]);

            if ($file === '') {
                return ['ok' => false, 'message' => 'Run-Datei nicht gefunden oder nicht erlaubt.', 'ctx' => $ctx];
            }

            $runParams = isset($m[2]) ? self::parseParamObject((string)$m[2], $vars, $params) : [];
            $runCtx = $ctx;
            $res = self::run((string)file_get_contents($file), $runCtx, $runParams);
            $ctx = $runCtx;
            $res['message'] = !empty($res['ok']) ? 'Datei separat ausgeführt: ' . basename($file) : 'FILE.RUN fehlgeschlagen: ' . basename($file);
            return $res;
        }

        if (preg_match('/^(?:C|CLASS)\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\{(.*)\}$/is', $command, $m)) {
            $name = self::cleanName((string)$m[1]);
            $body = trim((string)$m[2]);
            $classVars = [];
            $methods = [];

            foreach (self::splitCommands($body) as $part) {
                $part = trim((string)$part);

                if (preg_match('/^(?:PRIV|PUB)?\s*(?:DECLARE|DECALRE|DELACE)\s+([$]?[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/is', $part, $pm)) {
                    $classVars[self::cleanVarName((string)$pm[1])] = self::evalRuntimeExpression((string)$pm[2], $ctx, $vars, $params);
                    continue;
                }

                if (preg_match('/^(?:PUB|PRIV)?\s*F\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)\s*\{(.*)\}$/is', $part, $fm)) {
                    $args = array_values(array_filter(array_map(fn($v) => self::cleanVarName(trim((string)$v)), explode(',', (string)$fm[2]))));
                    $methods[self::cleanName((string)$fm[1])] = ['args' => $args, 'body' => trim((string)$fm[3])];
                }
            }

            if ($name === '') {
                return ['ok' => false, 'message' => 'Klassenname ungültig.', 'ctx' => $ctx];
            }

            if (!isset($ctx['classes']) || !is_array($ctx['classes'])) $ctx['classes'] = [];
            $ctx['classes'][$name] = ['vars' => $classVars, 'methods' => $methods];

            return ['ok' => true, 'message' => 'Klasse gespeichert: ' . $name, 'ctx' => $ctx];
        }

        if (preg_match('/^CLASS\s+([a-zA-Z_][a-zA-Z0-9_]*)\/([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $command, $m)) {
            $className = self::cleanName((string)$m[1]);
            $methodName = self::cleanName((string)$m[2]);
            $class = $ctx['classes'][$className] ?? null;

            if (is_array($class) && !is_array($class['methods'][$methodName] ?? null)) {
                foreach (array_keys(is_array($class['methods'] ?? null) ? $class['methods'] : []) as $candidate) {
                    if (levenshtein(strtolower($methodName), strtolower((string)$candidate)) <= 2) {
                        $methodName = (string)$candidate;
                        break;
                    }
                }
            }

            if (!is_array($class) || !is_array($class['methods'][$methodName] ?? null)) {
                return ['ok' => false, 'message' => 'Klasse oder Methode nicht gefunden: ' . $className . '/' . $methodName, 'ctx' => $ctx];
            }

            $method = $class['methods'][$methodName];
            $classData = is_array($class['vars'] ?? null) ? $class['vars'] : [];
            $localVars = array_merge($vars, $classData);
            foreach ($classData as $prop => $value) {
                $localVars['this__' . $prop] = $value;
            }
            $argTokens = self::splitArguments((string)$m[3]);

            foreach (($method['args'] ?? []) as $i => $argName) {
                $localVars[$argName] = array_key_exists($i, $argTokens) ? self::evalRuntimeExpression((string)$argTokens[$i], $ctx, $vars, $params) : null;
            }

            $body = (string)($method['body'] ?? '');
            $body = preg_replace('/\bthis\.([a-zA-Z][a-zA-Z0-9_]*)\s*\(/', 'CALL ' . $className . '/$1(', $body);
            $body = preg_replace('/\bthis\.([a-zA-Z_][a-zA-Z0-9_]*)\b/', 'this__$1', $body);
            $guard = 0;
            do {
                $res = self::runBlock($body, $ctx, $localVars, $params);
                $guard++;
                if ($guard > 1000) {
                    return ['ok' => false, 'message' => 'this_f.restart() Sicherheitslimit erreicht.', 'ctx' => $ctx];
                }
            } while (!empty($res['restart']));

            foreach (array_keys(is_array($class['vars'] ?? null) ? $class['vars'] : []) as $prop) {
                if (array_key_exists('this__' . $prop, $localVars)) {
                    $ctx['classes'][$className]['vars'][$prop] = $localVars['this__' . $prop];
                } elseif (array_key_exists($prop, $localVars)) {
                    $ctx['classes'][$className]['vars'][$prop] = $localVars[$prop];
                }
            }

            if (!empty($res['ok'])) $res['message'] = 'Methode ausgeführt: ' . $className . '/' . $methodName;
            return $res;
        }

        if (preg_match('/^(?:this\.)?([$]?[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/is', $command, $m)) {
            $name = self::cleanVarName((string)$m[1]);

            if (isset($ctx['consts'][$name])) {
                return ['ok' => false, 'message' => 'Konstante kann nicht überschrieben werden: ' . $name, 'ctx' => $ctx];
            }

            return self::setVar($name, self::evalRuntimeExpression((string)$m[2], $ctx, $vars, $params), $ctx, $vars);
        }

        if (preg_match('/^ERROR(?:\s+MSG)?\s+(.+)$/is', $command, $m)) {
            return [
                'ok' => false,
                'message' => (string)self::evalRuntimeExpression((string)$m[1], $ctx, $vars, $params),
                'ctx' => $ctx
            ];
        }

        $ifParts = self::parseIfCommand($command);
        if ($ifParts !== null) {
            [$condition, $ifBody, $elseBody] = $ifParts;
            $ok = (bool)self::evalRuntimeExpression($condition, $ctx, $vars, $params);
            $body = $ok ? $ifBody : $elseBody;

            if (trim($body) === '') {
                return [
                    'ok' => true,
                    'message' => $ok ? 'IF leer.' : 'IF übersprungen.',
                    'ctx' => $ctx
                ];
            }

            $res = self::runBlock($body, $ctx, $vars, $params);
            if (!empty($res['ok']) && !array_key_exists('back', $res)) {
                $res['message'] = $ok ? 'IF ausgeführt.' : 'ELSE ausgeführt.';
            }
            return $res;
        }


        if (preg_match('/^FOR\s*\(\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*;\s*([a-zA-Z_][a-zA-Z0-9_]*)\s+FROM\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\)\s*\{(.*)\}$/is', $command, $m)) {
            $itemVar = self::cleanName((string)$m[1]);
            $indexVar = self::cleanName((string)$m[2]);
            $sourceName = self::cleanName((string)$m[3]);
            $items = $vars[$sourceName] ?? ($vars['_' . ltrim($sourceName, '_')] ?? []);
            $body = (string)$m[4];
            $results = [];
            $count = 0;

            if (!is_array($items)) {
                return ['ok' => false, 'message' => 'FOR erwartet ein Array: ' . $sourceName, 'ctx' => $ctx];
            }

            foreach (array_values($items) as $i => $item) {
                $vars[$itemVar] = $item;
                $vars[$indexVar] = $i;
                $vars[ltrim($itemVar, '_')] = $item;
                $vars[ltrim($indexVar, '_')] = $i;
                $res = self::runBlock($body, $ctx, $vars, $params);
                $results[] = $res;

                if (!($res['ok'] ?? false)) {
                    return ['ok' => false, 'message' => (string)($res['message'] ?? 'FOR fehlgeschlagen.'), 'results' => $results, 'ctx' => $ctx];
                }

                if (array_key_exists('back', $res)) {
                    return $res;
                }

                $count++;
            }

            return ['ok' => true, 'message' => 'FOR ausgeführt: ' . $count . ' Durchläufe.', 'results' => $results, 'ctx' => $ctx, 'vars' => $vars];
        }

        if (preg_match('/^FOR\s*\(([^,;]+)[,;]([^;]+);([^\)]+)\)\s*\{(.*)\}$/is', $command, $m)) {
            $init = trim((string)$m[1]);
            $condition = trim((string)$m[2]);
            $step = trim((string)$m[3]);
            $body = (string)$m[4];
            $guard = 0;
            $results = [];

            if (preg_match('/^([$]?[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/s', $init, $im)) {
                $vars[self::cleanName((string)$im[1])] = self::evaluateExpression((string)$im[2], $vars, $params);
            }

            while ((bool)self::evaluateExpression($condition, $vars, $params)) {
                $res = self::runBlock($body, $ctx, $vars, $params);
                $results[] = $res;

                if (!($res['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => (string)($res['message'] ?? 'FOR fehlgeschlagen.'),
                        'results' => $results,
                        'ctx' => $ctx
                    ];
                }

                if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\+\+$/', $step, $sm)) {
                    $n = self::cleanName((string)$sm[1]);
                    $vars[$n] = (int)($vars[$n] ?? 0) + 1;
                } elseif (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*--$/', $step, $sm)) {
                    $n = self::cleanName((string)$sm[1]);
                    $vars[$n] = (int)($vars[$n] ?? 0) - 1;
                } elseif (preg_match('/^([$]?[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/s', $step, $sm)) {
                    $vars[self::cleanName((string)$sm[1])] = self::evaluateExpression((string)$sm[2], $vars, $params);
                }

                $guard++;
                if ($guard > 10000) {
                    return [
                        'ok' => false,
                        'message' => 'FOR abgebrochen: Sicherheitslimit erreicht.',
                        'ctx' => $ctx
                    ];
                }
            }

            return [
                'ok' => true,
                'message' => 'FOR ausgeführt: ' . $guard . ' Durchläufe.',
                'results' => $results,
                'ctx' => $ctx,
                'vars' => $vars
            ];
        }

        if (preg_match('/^MAP_OBJECT\s*\(\s*([a-zA-Z_][a-zA-Z0-9_]*)\s+AS\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*,\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\)\s*\{(.*)\}$/is', $command, $m)) {
            $sourceName = self::cleanName((string)$m[1]);
            $keyVar = self::cleanName((string)$m[2]);
            $valueVar = self::cleanName((string)$m[3]);
            $items = $vars[$sourceName] ?? ($vars['_' . ltrim($sourceName, '_')] ?? []);
            $body = (string)$m[4];
            $results = [];
            $count = 0;

            if (!is_array($items)) {
                return ['ok' => false, 'message' => 'MAP_OBJECT erwartet ein Array/Objekt: ' . $sourceName, 'ctx' => $ctx];
            }

            foreach ($items as $key => $item) {
                $vars[$keyVar] = $key;
                $vars[$valueVar] = $item;
                $vars[ltrim($keyVar, '_')] = $key;
                $vars[ltrim($valueVar, '_')] = $item;
                $res = self::runBlock($body, $ctx, $vars, $params);
                $results[] = $res;

                if (!($res['ok'] ?? false)) {
                    return ['ok' => false, 'message' => (string)($res['message'] ?? 'MAP_OBJECT fehlgeschlagen.'), 'results' => $results, 'ctx' => $ctx];
                }

                if (array_key_exists('back', $res)) {
                    return $res;
                }

                $count++;
            }

            return ['ok' => true, 'message' => 'MAP_OBJECT ausgeführt: ' . $count . ' Einträge.', 'results' => $results, 'ctx' => $ctx, 'vars' => $vars];
        }

        if (preg_match('/^MAP_OBJECT\s*\(([^\)]+)\)\s*\{(.*)\}$/is', $command, $m)) {
            $name = self::cleanName((string)$m[1]);
            $items = $vars[$name] ?? [];
            $body = (string)$m[2];
            $results = [];
            $count = 0;

            if (!is_array($items)) {
                return [
                    'ok' => false,
                    'message' => 'MAP_OBJECT erwartet ein Array/Objekt.',
                    'ctx' => $ctx
                ];
            }

            foreach ($items as $key => $item) {
                $localVars = $vars;
                $localVars[$name] = $item;
                $localVars['_key'] = $key;
                $res = self::runBlock($body, $ctx, $localVars, $params);
                $results[] = $res;

                if (!($res['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => (string)($res['message'] ?? 'MAP_OBJECT fehlgeschlagen.'),
                        'results' => $results,
                        'ctx' => $ctx
                    ];
                }

                $count++;
            }

            return [
                'ok' => true,
                'message' => 'MAP_OBJECT ausgeführt: ' . $count . ' Einträge.',
                'results' => $results,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^F\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)\s*\{(.*)\}$/is', $command, $m)) {
            $name = self::cleanName((string)$m[1]);
            $args = array_values(array_filter(array_map(function ($v) {
                return self::cleanName(trim((string)$v));
            }, explode(',', (string)$m[2]))));
            $body = trim((string)$m[3]);

            if ($name === '') {
                return [
                    'ok' => false,
                    'message' => 'Funktionsname ungültig.',
                    'ctx' => $ctx
                ];
            }

            if (!isset($ctx['functions']) || !is_array($ctx['functions'])) {
                $ctx['functions'] = [];
            }

            $ctx['functions'][$name] = [
                'args' => $args,
                'body' => $body
            ];

            return [
                'ok' => true,
                'message' => 'Funktion gespeichert: ' . $name,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^CALL\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/is', $command, $m)) {
            $name = self::cleanName((string)$m[1]);
            $fn = $ctx['functions'][$name] ?? null;

            if (!is_array($fn)) {
                return [
                    'ok' => false,
                    'message' => 'Funktion nicht gefunden: ' . $name,
                    'ctx' => $ctx
                ];
            }

            $argTokens = self::splitArguments((string)$m[2]);
            $fnArgs = is_array($fn['args'] ?? null) ? $fn['args'] : [];
            $localVars = $vars;

            foreach ($fnArgs as $i => $argName) {
                $localVars[$argName] = array_key_exists($i, $argTokens)
                    ? self::evalRuntimeExpression((string)$argTokens[$i], $ctx, $vars, $params)
                    : null;
            }

            $results = [];
            $commands = self::splitCommands((string)($fn['body'] ?? ''));

            foreach ($commands as $fnCommand) {
                $res = self::command($fnCommand, $ctx, $localVars, $params);
                $results[] = $res;

                if (!($res['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => 'Funktionsaufruf fehlgeschlagen: ' . $name,
                        'results' => $results,
                        'ctx' => $ctx
                    ];
                }

                if (array_key_exists('back', $res)) {
                    return [
                        'ok' => true,
                        'message' => 'Funktion ausgeführt: ' . $name,
                        'back' => $res['back'],
                        'result' => $res['back'],
                        'results' => $results,
                        'ctx' => $ctx,
                        'refresh' => true
                    ];
                }
            }

            return [
                'ok' => true,
                'message' => 'Funktion ausgeführt: ' . $name,
                'result' => null,
                'results' => $results,
                'ctx' => $ctx,
                'refresh' => true
            ];
        }

        if (preg_match('/^BEGIN$/i', $command)) {
            return [
                "ok" => $driver::begin(),
                "message" => "Transaktion gestartet.",
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^COMMIT$/i', $command)) {
            $ok = $driver::commit();

            return [
                "ok" => $ok,
                "message" => $ok ? "Transaktion gespeichert." : "Transaktion konnte nicht gespeichert werden.",
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^ROLLBACK$/i', $command)) {
            $ok = $driver::rollback();

            return [
                "ok" => $ok,
                "message" => $ok ? "Transaktion verworfen." : "Keine aktive Transaktion.",
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^SHOW\s+TRANSACTION$/i', $command)) {
            $status = $driver::transactionStatus();

            return [
                "ok" => true,
                "message" => "Transaktionsstatus.",
                "keys" => ["active", "id", "ops", "instance"],
                "rows" => [[
                    "active" => $status["active"] ? "true" : "false",
                    "id" => $status["id"],
                    "ops" => $status["ops"],
                    "instance" => $status["instance"] ?? ""
                ]],
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^(?:PRIV|PUB)?\s*(?:DECLARE|DECALRE|DELACE)\s+([$]?[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/is', $command, $m)) {
            return self::setVar((string)$m[1], self::evalRuntimeExpression((string)$m[2], $ctx, $vars, $params), $ctx, $vars);
        }

        if (preg_match('/^EXISTS\s+(INSTANCE|BASE|TABLE|DATA)\s+(.+)$/is', $command, $m)) {
            $ok = self::existsRuntime(strtoupper((string)$m[1]), trim((string)$m[2]), $ctx, $vars, $params);
            return [
                'ok' => true,
                'message' => $ok ? 'EXISTS true.' : 'EXISTS false.',
                'result' => $ok,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^USE\s+INSTANCE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $instance = self::resolveNameToken((string)$m[1], $vars);

            if (!self::useInstance($instance, $ctx)) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht aktiviert werden.",
                    "ctx" => $ctx
                ];
            }

            return [
                "ok" => true,
                "message" => "Instanz aktiv: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^ROOT\s+INSTANCE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $instance = self::resolveNameToken((string)$m[1], $vars);

            if (!self::useInstance($instance, $ctx)) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht aktiviert werden.",
                    "ctx" => $ctx
                ];
            }

            return [
                "ok" => true,
                "message" => "Instanz fokussiert: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^SHOW\s+INSTANCES$/i', $command)) {
            if (!class_exists("GBDBv2")) {
                return [
                    "ok" => false,
                    "message" => "GBDBv2 ist nicht verfügbar.",
                    "ctx" => $ctx
                ];
            }

            $rows = [];
            $oldInstance = GBDBv2::getInstance();
            $oldCtxInstance = $ctx["instance"] ?? "";

            foreach (GBDBv2::listInstances() as $instance) {
                GBDBv2::setInstance($instance);
                self::$driver = "GBDBv2";
                self::$instance = $instance;

                $dbs = GBDBv2::listDBs();
                $tables = 0;
                $records = 0;

                foreach ($dbs as $db) {
                    $stats = self::stats($db);
                    $tables += $stats["tables"];
                    $records += $stats["rows"];
                }

                $rows[] = [
                    "instance" => $instance,
                    "bases" => count($dbs),
                    "tables" => $tables,
                    "rows" => $records
                ];
            }

            GBDBv2::setInstance($oldInstance);

            if ($oldCtxInstance !== "") {
                self::useInstance((string)$oldCtxInstance, $ctx);
            } else {
                self::$driver = "GBDB";
                self::$instance = "";
            }

            return [
                "ok" => true,
                "message" => count($rows) . " Instanzen gefunden.",
                "keys" => ["instance", "bases", "tables", "rows"],
                "rows" => $rows,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^GROW\s+INSTANCE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            if (!class_exists("GBDBv2")) {
                return [
                    "ok" => false,
                    "message" => "GBDBv2 ist nicht verfügbar.",
                    "ctx" => $ctx
                ];
            }

            $instance = self::resolveNameToken((string)$m[1], $vars);

            if ($instance === "") {
                return [
                    "ok" => false,
                    "message" => "Ungültiger Instanz-Name.",
                    "ctx" => $ctx
                ];
            }

            $exists = in_array($instance, GBDBv2::listInstances(), true);
            $ok = $exists || GBDBv2::createInstance($instance);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht erstellt werden.",
                    "ctx" => $ctx
                ];
            }

            self::useInstance($instance, $ctx);

            return [
                "ok" => true,
                "message" => $exists ? "Instanz bereits vorhanden: " . $instance : "Instanz erstellt: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DROP\s+INSTANCE\s+([a-zA-Z0-9_\-]+)(?:\s+(FORCE))?$/i', $command, $m)) {
            if (!class_exists("GBDBv2")) {
                return [
                    "ok" => false,
                    "message" => "GBDBv2 ist nicht verfügbar.",
                    "ctx" => $ctx
                ];
            }

            $instance = self::resolveNameToken((string)$m[1], $vars);
            $force = strtoupper((string)($m[2] ?? "")) === "FORCE";

            if ($instance === "") {
                return [
                    "ok" => false,
                    "message" => "Ungültiger Instanz-Name.",
                    "ctx" => $ctx
                ];
            }

            $ok = GBDBv2::deleteInstance($instance, $force);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Instanz konnte nicht gelöscht werden. Sie muss leer sein oder FORCE genutzt werden.",
                    "ctx" => $ctx
                ];
            }

            if (($ctx["instance"] ?? "") === $instance) {
                unset($ctx["instance"]);
                self::$instance = "";
                self::$driver = "GBDB";
            }

            return [
                "ok" => true,
                "message" => "Instanz gelöscht: " . $instance,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^ROOT\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $ctx["db"] = self::resolveNameToken((string)$m[1], $vars);
            $ctx["table"] = "";

            return [
                "ok" => true,
                "message" => "Base fokussiert: " . $ctx["db"],
                "refresh" => true,
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^BRANCH\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $ctx["table"] = self::resolveNameToken((string)$m[1], $vars);

            return [
                "ok" => true,
                "message" => "Tabelle fokussiert: " . $ctx["table"],
                "refresh" => true,
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^SHOW\s+BASES$/i', $command)) {
            $rows = [];

            foreach ($driver::listDBs() as $db) {
                $stats = self::stats($db);

                $rows[] = [
                    "base" => $db,
                    "tables" => $stats["tables"],
                    "rows" => $stats["rows"]
                ];
            }

            return [
                "ok" => true,
                "message" => count($rows) . " Basen gefunden.",
                "keys" => ["base", "tables", "rows"],
                "rows" => $rows,
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^SHOW\s+TABLES(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $db = self::resolveNameToken(self::optionalDbMatch($m, 1, $ctx), $vars);

            if ($db === "") {
                return [
                    "ok" => false,
                    "message" => "Keine Base aktiv.",
                    "ctx" => $ctx
                ];
            }

            $rows = [];

            foreach ($driver::listTables($db) as $table) {
                $rows[] = [
                    "table" => $table,
                    "fields" => count(self::getTableKeys($db, $table)),
                    "rows" => count(self::getRows($db, $table))
                ];
            }

            $ctx["db"] = $db;

            return [
                "ok" => true,
                "message" => count($rows) . " Tabellen in " . $db . ".",
                "keys" => ["table", "fields", "rows"],
                "rows" => $rows,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^GROW\s+BASE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $db = self::resolveNameToken((string)$m[1], $vars);

            if ($db === "") {
                return [
                    "ok" => false,
                    "message" => "Ungültiger Base-Name.",
                    "ctx" => $ctx
                ];
            }

            $exists = in_array($db, $driver::listDBs(), true);
            $ok = $exists || $driver::createDatabase($db);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Base konnte nicht erstellt werden.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = "";

            return [
                "ok" => true,
                "message" => $exists ? "Base bereits vorhanden: " . $db : "Base erstellt: " . $db,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DROP\s+BASE\s+([a-zA-Z0-9_\-]+)$/i', $command, $m)) {
            $db = self::resolveNameToken((string)$m[1], $vars);
            $ok = $driver::deleteDatabase($db);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Base konnte nicht gelöscht werden. Sie muss leer sein.",
                    "ctx" => $ctx
                ];
            }

            if (($ctx["db"] ?? "") === $db) {
                $ctx["db"] = "";
                $ctx["table"] = "";
            }

            return [
                "ok" => true,
                "message" => "Base gelöscht: " . $db,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^GROW\s+TABLE\s+([a-zA-Z0-9_\-]+)\s+WITH\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $command = 'GROW TABLE ' . $m[1] . ' (' . $m[2] . ')' . (isset($m[3]) && $m[3] !== '' ? ' IN ' . $m[3] : '');
        }

        if (preg_match('/^GROW\s+TABLE\s+([a-zA-Z0-9_\-]+)\s*\(([^\)]+)\)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $cols = self::parseList((string)$m[2], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 3, $ctx), $vars);

            if ($db === "") {
                return [
                    "ok" => false,
                    "message" => "Keine Base aktiv.",
                    "ctx" => $ctx
                ];
            }

            if ($table === "" || empty($cols)) {
                return [
                    "ok" => false,
                    "message" => "Tabelle oder Felder ungültig.",
                    "ctx" => $ctx
                ];
            }

            $exists = in_array($table, $driver::listTables($db), true);
            $ok = $exists || $driver::createTable($db, $table, $cols);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Tabelle konnte nicht erstellt werden.",
                    "ctx" => $ctx
                ];
            }

            if ($exists) {
                foreach ($cols as $col) {
                    if ($col !== "id" && !in_array($col, self::getTableKeys($db, $table), true)) {
                        $driver::addColumn($db, $table, $col, "");
                    }
                }
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => $exists ? "Tabelle bereits vorhanden: " . $table : "Tabelle erstellt: " . $table,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^EDIT\s+TABLE\s+([a-zA-Z0-9_\-]+)\s+ADD(?!\s+CONSTRAINT)(?:\s+COLUMN)?\s+([a-zA-Z0-9_\-]+)(?:\s+(?:DEFAULT\s+)?(.+?))?(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $command = 'ALTER TABLE ' . $m[1] . ' ADD ' . $m[2] . (isset($m[3]) && trim((string)$m[3]) !== '' ? ' DEFAULT ' . $m[3] : '') . (isset($m[4]) && $m[4] !== '' ? ' IN ' . $m[4] : '');
        }

        if (preg_match('/^ALTER\s+TABLE\s+([a-zA-Z0-9_\-]+)\s+ADD(?!\s+CONSTRAINT)(?:\s+COLUMN)?\s+([a-zA-Z0-9_\-]+)(?:\s+(?:DEFAULT\s+)?(.+?))?(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $column = self::resolveNameToken((string)$m[2], $vars);
            $default = isset($m[3]) ? self::evaluateValue((string)$m[3], $vars, $params) : "";
            $db = self::resolveNameToken(self::optionalDbMatch($m, 4, $ctx), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if ($column === "" || $column === "id") {
                return [
                    "ok" => false,
                    "message" => "Spaltenname ungültig.",
                    "ctx" => $ctx
                ];
            }

            $keysBefore = self::getTableKeys($db, $table);
            $existsBefore = in_array($column, $keysBefore, true);
            $ok = $driver::addColumn($db, $table, $column, $default);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Spalte konnte nicht hinzugefügt werden.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => $existsBefore
                    ? "Spalte bereits vorhanden: " . $column
                    : "Spalte hinzugefügt: " . $column,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DROP\s+TABLE\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::deleteTable($db, $table);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Tabelle konnte nicht gelöscht werden.",
                    "ctx" => $ctx
                ];
            }

            if (($ctx["db"] ?? "") === $db && ($ctx["table"] ?? "") === $table) {
                $tables = $driver::listTables($db);
                $ctx["table"] = $tables[0] ?? "";
            }

            return [
                "ok" => true,
                "message" => "Tabelle gelöscht: " . $table,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }



        if (preg_match('/^ALTER\s+TABLE\s+([a-zA-Z0-9_\-]+)\s+ADD\s+CONSTRAINT\s+(UNIQUE|REQUIRED)\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $type = strtolower((string)$m[2]);
            $column = self::resolveNameToken((string)$m[3], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 4, $ctx), $vars);

            if ($db === '' || $table === '' || $column === '') {
                return ['ok' => false, 'message' => 'Base, Tabelle oder Spalte fehlt.', 'ctx' => $ctx];
            }

            $ok = $driver::addConstraint($db, $table, $column, $type);

            return [
                'ok' => $ok,
                'message' => $ok ? 'Constraint gesetzt: ' . $type . ' ' . $column : 'Constraint konnte nicht gesetzt werden.',
                'ctx' => $ctx,
                'refresh' => $ok
            ];
        }

        if (preg_match('/^ALTER\s+TABLE\s+([a-zA-Z0-9_\-]+)\s+DROP\s+CONSTRAINT\s+(UNIQUE|REQUIRED)\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $type = strtolower((string)$m[2]);
            $column = self::resolveNameToken((string)$m[3], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 4, $ctx), $vars);

            if ($db === '' || $table === '' || $column === '') {
                return ['ok' => false, 'message' => 'Base, Tabelle oder Spalte fehlt.', 'ctx' => $ctx];
            }

            $ok = $driver::dropConstraint($db, $table, $column, $type);

            return [
                'ok' => $ok,
                'message' => $ok ? 'Constraint entfernt: ' . $type . ' ' . $column : 'Constraint konnte nicht entfernt werden.',
                'ctx' => $ctx,
                'refresh' => $ok
            ];
        }

        if (preg_match('/^SHOW\s+CONSTRAINTS\s+(?:FROM\s+)?([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $rows = [];

            foreach ($driver::listConstraints($db, $table) as $column => $rules) {
                foreach ((array)$rules as $type => $active) {
                    if ($active) $rows[] = ['field' => (string)$column, 'constraint' => (string)$type];
                }
            }

            return [
                'ok' => true,
                'message' => count($rows) . ' Constraints gefunden.',
                'keys' => ['field', 'constraint'],
                'rows' => $rows,
                'ctx' => $ctx
            ];
        }
        if (preg_match('/^SHOW\s+INDEXES\s+(?:FROM\s+)?([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $rows = [];

            foreach ($driver::listIndexes($db, $table) as $idx) {
                $rows[] = ['index' => $idx];
            }

            return [
                'ok' => true,
                'message' => count($rows) . ' Indexe gefunden.',
                'keys' => ['index'],
                'rows' => $rows,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^(?:INDEX|CREATE\s+INDEX\s+ON)\s+([a-zA-Z0-9_\-]+)\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $column = self::resolveNameToken((string)$m[2], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 3, $ctx), $vars);

            if ($db === '' || $table === '' || $column === '') {
                return ['ok' => false, 'message' => 'Base, Tabelle oder Spalte fehlt.', 'ctx' => $ctx];
            }

            $ok = $driver::createIndex($db, $table, $column);

            return [
                'ok' => $ok,
                'message' => $ok ? 'Index erstellt: ' . $column : 'Index konnte nicht erstellt werden.',
                'ctx' => $ctx,
                'refresh' => $ok
            ];
        }

        if (preg_match('/^(?:UNINDEX|DROP\s+INDEX\s+ON)\s+([a-zA-Z0-9_\-]+)\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $column = self::resolveNameToken((string)$m[2], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 3, $ctx), $vars);

            if ($db === '' || $table === '' || $column === '') {
                return ['ok' => false, 'message' => 'Base, Tabelle oder Spalte fehlt.', 'ctx' => $ctx];
            }

            $ok = $driver::dropIndex($db, $table, $column);

            return [
                'ok' => $ok,
                'message' => $ok ? 'Index gelöscht: ' . $column : 'Index konnte nicht gelöscht werden.',
                'ctx' => $ctx,
                'refresh' => $ok
            ];
        }

        if (preg_match('/^REINDEX\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $ok = $driver::rebuildIndexes($db, $table);

            return [
                'ok' => $ok,
                'message' => $ok ? 'Indexe neu aufgebaut.' : 'Indexe konnten nicht neu aufgebaut werden.',
                'ctx' => $ctx,
                'refresh' => $ok
            ];
        }

        if (preg_match('/^(?:CHECK|HEALTH)\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $health = $driver::health($db, $table);
            $rows = [];

            foreach (($health['errors'] ?? []) as $err) $rows[] = ['type' => 'error', 'value' => $err];
            foreach (($health['warnings'] ?? []) as $warn) $rows[] = ['type' => 'warning', 'value' => $warn];

            return [
                'ok' => (bool)($health['ok'] ?? false),
                'message' => ((bool)($health['ok'] ?? false) ? 'Tabelle ok.' : 'Tabelle hat Fehler.') . ' Rows: ' . (string)($health['rows_real'] ?? 0),
                'keys' => ['type', 'value'],
                'rows' => $rows,
                'result' => $health,
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^REPAIR\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $ok = $driver::repairTable($db, $table);

            return [
                'ok' => $ok,
                'message' => $ok ? 'Tabelle repariert.' : 'Tabelle konnte nicht repariert werden.',
                'ctx' => $ctx,
                'refresh' => $ok
            ];
        }

        if (preg_match('/^SNAPSHOT\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $id = $driver::snapshot($db, $table, 'greenql');

            return [
                'ok' => $id !== '',
                'message' => $id !== '' ? 'Snapshot erstellt: ' . $id : 'Snapshot konnte nicht erstellt werden.',
                'ctx' => $ctx
            ];
        }

        if (preg_match('/^SHOW\s+META\s+(?:FROM\s+)?([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $rows = [];

            foreach ($driver::meta($db, $table) as $key => $value) {
                $rows[] = ['key' => (string)$key, 'value' => is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE)];
            }

            return [
                'ok' => true,
                'message' => 'Meta-Daten gelesen.',
                'keys' => ['key', 'value'],
                'rows' => $rows,
                'ctx' => $ctx
            ];
        }



        if (preg_match('/^MONITOR(?:\s+([a-zA-Z0-9_\-.]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?)?$/i', $command, $m)) {
            $ref = isset($m[1]) ? trim((string)$m[1]) : '';
            $db = isset($m[2]) && trim((string)$m[2]) !== '' ? self::resolveNameToken((string)$m[2], $vars) : (string)($ctx['db'] ?? '');
            $table = '';

            if ($ref !== '') {
                if (str_contains($ref, '.')) {
                    [$dbRef, $tableRef] = array_pad(explode('.', $ref, 2), 2, '');
                    $db = self::resolveNameToken($dbRef, $vars);
                    $table = self::resolveNameToken($tableRef, $vars);
                } else {
                    $table = self::resolveNameToken($ref, $vars);
                }
            }

            if ($ref === '') {
                $rows = [];
                foreach ($driver::listDBs() as $dbName) {
                    foreach ($driver::listTables($dbName) as $tableName) {
                        $monitor = method_exists($driver, 'monitor') ? $driver::monitor($dbName, $tableName) : [];
                        $rows[] = [
                            'base' => $dbName,
                            'table' => $tableName,
                            'rows' => (int)($monitor['rows'] ?? 0),
                            'append_ops' => (int)($monitor['append_ops'] ?? 0),
                            'data_size' => (int)($monitor['data_size'] ?? 0)
                        ];
                    }
                }
                return ['ok' => true, 'message' => 'Monitoring-Übersicht gelesen.', 'keys' => ['base', 'table', 'rows', 'append_ops', 'data_size'], 'rows' => $rows, 'ctx' => $ctx];
            }

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $monitor = method_exists($driver, 'monitor') ? $driver::monitor($db, $table) : [];
            $rows = [];

            foreach ($monitor as $key => $value) {
                $rows[] = ['key' => (string)$key, 'value' => is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE)];
            }

            return ['ok' => !empty($monitor), 'message' => 'Monitoring gelesen.', 'keys' => ['key', 'value'], 'rows' => $rows, 'result' => $monitor, 'ctx' => $ctx];
        }

        if (preg_match('/^RECOVER\s+([a-zA-Z0-9_\-.]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $ref = trim((string)$m[1]);
            $db = isset($m[2]) && trim((string)$m[2]) !== '' ? self::resolveNameToken((string)$m[2], $vars) : (string)($ctx['db'] ?? '');
            $table = '';

            if (str_contains($ref, '.')) {
                [$dbRef, $tableRef] = array_pad(explode('.', $ref, 2), 2, '');
                $db = self::resolveNameToken($dbRef, $vars);
                $table = self::resolveNameToken($tableRef, $vars);
            } else {
                $table = self::resolveNameToken($ref, $vars);
            }

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $result = method_exists($driver, 'recoverTable') ? $driver::recoverTable($db, $table) : ['ok' => false];
            return ['ok' => (bool)($result['ok'] ?? false), 'message' => 'WAL-Recovery ausgeführt.', 'result' => $result, 'ctx' => $ctx, 'refresh' => true];
        }

        if (preg_match('/^PAGE\s+([a-zA-Z0-9_\-.]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+PAGE\s+(\d+))?\s+(?:LIMIT|SIZE)\s+(\d+)$/i', $command, $m)) {
            $ref = trim((string)$m[1]);
            $db = isset($m[2]) && trim((string)$m[2]) !== '' ? self::resolveNameToken((string)$m[2], $vars) : (string)($ctx['db'] ?? '');
            $page = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : 1;
            $size = (int)$m[4];
            $table = '';

            if (str_contains($ref, '.')) {
                [$dbRef, $tableRef] = array_pad(explode('.', $ref, 2), 2, '');
                $db = self::resolveNameToken($dbRef, $vars);
                $table = self::resolveNameToken($tableRef, $vars);
            } else {
                $table = self::resolveNameToken($ref, $vars);
            }

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $result = method_exists($driver, 'page') ? $driver::page($db, $table, $page, $size) : ['ok' => false, 'rows' => []];
            $rows = $result['rows'] ?? [];
            $keys = isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : [];
            return ['ok' => (bool)($result['ok'] ?? false), 'message' => 'Page ' . $page . ' geladen.', 'keys' => $keys, 'rows' => $rows, 'result' => $result, 'ctx' => $ctx];
        }

        if (preg_match('/^CURSOR\s+([a-zA-Z0-9_\-.]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?\s+(?:LIMIT|SIZE)\s+(\d+)(?:\s+AFTER\s+(.+))?$/i', $command, $m)) {
            $ref = trim((string)$m[1]);
            $db = isset($m[2]) && trim((string)$m[2]) !== '' ? self::resolveNameToken((string)$m[2], $vars) : (string)($ctx['db'] ?? '');
            $limit = (int)$m[3];
            $cursor = isset($m[4]) ? trim((string)$m[4]) : null;
            $table = '';

            if (str_contains($ref, '.')) {
                [$dbRef, $tableRef] = array_pad(explode('.', $ref, 2), 2, '');
                $db = self::resolveNameToken($dbRef, $vars);
                $table = self::resolveNameToken($tableRef, $vars);
            } else {
                $table = self::resolveNameToken($ref, $vars);
            }

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $result = method_exists($driver, 'cursor') ? $driver::cursor($db, $table, $limit, $cursor) : ['ok' => false, 'rows' => []];
            $rows = $result['rows'] ?? [];
            $keys = isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : [];
            return ['ok' => (bool)($result['ok'] ?? false), 'message' => 'Cursor geladen. Next: ' . (string)($result['cursor'] ?? ''), 'keys' => $keys, 'rows' => $rows, 'result' => $result, 'ctx' => $ctx];
        }

        if (preg_match('/^FULLTEXT\s+([a-zA-Z0-9_\-.]+)\s+SEARCH\s+(.+?)(?:\s+COLUMNS\s+(.+?))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $m)
            || preg_match('/^FULLTEXT\s+(.+?)\s+FROM\s+([a-zA-Z0-9_\-.]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+COLUMNS\s+(.+?))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $mOld)) {
            if (isset($mOld)) {
                $query = self::evaluateValue(trim((string)$mOld[1]), $vars, $params);
                $ref = trim((string)$mOld[2]);
                $db = isset($mOld[3]) && trim((string)$mOld[3]) !== '' ? self::resolveNameToken((string)$mOld[3], $vars) : (string)($ctx['db'] ?? '');
                $columns = isset($mOld[4]) && trim((string)$mOld[4]) !== '' ? self::parseList((string)$mOld[4], $vars) : [];
                $limit = isset($mOld[5]) && $mOld[5] !== '' ? (int)$mOld[5] : 50;
                unset($mOld);
            } else {
                $ref = trim((string)$m[1]);
                $query = self::evaluateValue(trim((string)$m[2]), $vars, $params);
                $db = (string)($ctx['db'] ?? '');
                $columns = isset($m[3]) && trim((string)$m[3]) !== '' ? self::parseList((string)$m[3], $vars) : [];
                $limit = isset($m[4]) && $m[4] !== '' ? (int)$m[4] : 50;
            }

            $table = '';
            if (str_contains($ref, '.')) {
                [$dbRef, $tableRef] = array_pad(explode('.', $ref, 2), 2, '');
                $db = self::resolveNameToken($dbRef, $vars);
                $table = self::resolveNameToken($tableRef, $vars);
            } else {
                $table = self::resolveNameToken($ref, $vars);
            }

            if ($db === '' || $table === '') {
                return ['ok' => false, 'message' => 'Base oder Tabelle fehlt.', 'ctx' => $ctx];
            }

            $hits = method_exists($driver, 'fulltext_search') ? $driver::fulltext_search($db, $table, (string)$query, $columns, $limit) : [];
            $rows = [];
            foreach ($hits as $hit) {
                $row = is_array($hit['row'] ?? null) ? $hit['row'] : [];
                $row['_score'] = $hit['score'] ?? 0;
                $rows[] = $row;
            }
            $keys = isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : [];
            return ['ok' => true, 'message' => count($rows) . ' Volltext-Treffer.', 'keys' => $keys, 'rows' => $rows, 'ctx' => $ctx];
        }
        if (preg_match('/^GRANT\s+([a-zA-Z0-9_\-*]+)\s+([a-zA-Z0-9_\-*]+)\s+ON\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $role = (string)$m[1];
            $perm = (string)$m[2];
            $table = self::resolveNameToken((string)$m[3], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 4, $ctx), $vars);
            $ok = $db !== '' && $table !== '' && method_exists($driver, 'grantAcl') && $driver::grantAcl($db, $table, $role, $perm);
            return ['ok' => $ok, 'message' => $ok ? 'ACL gesetzt.' : 'ACL konnte nicht gesetzt werden.', 'ctx' => $ctx];
        }

        if (preg_match('/^REVOKE\s+([a-zA-Z0-9_\-*]+)\s+([a-zA-Z0-9_\-*]+)\s+ON\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $role = (string)$m[1];
            $perm = (string)$m[2];
            $table = self::resolveNameToken((string)$m[3], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 4, $ctx), $vars);
            $ok = $db !== '' && $table !== '' && method_exists($driver, 'revokeAcl') && $driver::revokeAcl($db, $table, $role, $perm);
            return ['ok' => $ok, 'message' => $ok ? 'ACL entfernt.' : 'ACL konnte nicht entfernt werden.', 'ctx' => $ctx];
        }

        if (preg_match('/^DESCRIBE\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $keys = self::getTableKeys($db, $table);
            $rows = [];

            foreach ($keys as $key) {
                $rows[] = [
                    "field" => $key,
                    "kind" => $key === "id" ? "auto" : "mixed"
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Schema geladen: " . $table,
                "keys" => ["field", "kind"],
                "rows" => $rows,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^PACK\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ok = $driver::compactTable($db, $table);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Compact fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Tabelle gepackt: " . $table,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^PEEK\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 2, $ctx), $vars);
            $limit = isset($m[3]) ? (int)$m[3] : 50;

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            $result = self::selectRows($db, $table, ["*"], null, "id", "ASC", $limit);

            return [
                "ok" => true,
                "message" => "Vorschau: " . $table,
                "keys" => $result["keys"],
                "rows" => $result["rows"],
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^EXPLAIN\s+PICK\s+(.+?)\s+FROM\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+WHERE\s+(.+?))?(?:\s+SORT\s+([a-zA-Z0-9_\-]+)\s+(ASC|DESC))?(?:\s+LIMIT\s+(\d+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[2], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 3, $ctx), $vars);
            $where = isset($m[4]) ? self::parseWhere((string)$m[4], $vars, $params) : null;
            $indexes = ($db !== "" && $table !== "") ? $driver::listIndexes($db, $table) : [];
            $indexUsed = "";
            $scan = "table";

            if ($where !== null && in_array((string)$where["op"], ["=", "=="], true) && in_array((string)$where["field"], $indexes, true)) {
                $indexUsed = (string)$where["field"];
                $scan = "index";
            }

            return [
                "ok" => true,
                "message" => "Query Plan für " . $table . ".",
                "keys" => ["db", "table", "scan", "index", "where", "sort", "limit"],
                "rows" => [[
                    "db" => $db,
                    "table" => $table,
                    "scan" => $scan,
                    "index" => $indexUsed,
                    "where" => $where === null ? "" : (($where["field"] ?? "") . " " . ($where["op"] ?? "") . " " . (string)($where["value"] ?? "")),
                    "sort" => (string)($m[5] ?? ""),
                    "limit" => (string)($m[7] ?? "")
                ]],
                "ctx" => $ctx
            ];
        }

        if (preg_match('/^PICK\s+(.+?)\s+FROM\s+([a-zA-Z0-9_\-]+)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?(?:\s+WHERE\s+(.+?))?(?:\s+SORT\s+([a-zA-Z0-9_\-]+)\s+(ASC|DESC))?(?:\s+(?:LIMIT\s+(\d+)|MAX\((\d+)\)))?$/i', $command, $m)) {
            $colsRaw = trim((string)$m[1]);
            $table = self::resolveNameToken((string)$m[2], $vars);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 3, $ctx), $vars);
            $where = isset($m[4]) ? self::parseWhere((string)$m[4], $vars, $params) : null;
            $sortField = isset($m[5]) ? self::resolveNameToken((string)$m[5], $vars) : null;
            $sortDir = strtoupper((string)($m[6] ?? "ASC"));
            $limit = isset($m[7]) && $m[7] !== '' ? (int)$m[7] : (isset($m[8]) && $m[8] !== '' ? (int)$m[8] : 50);
            $columns = $colsRaw === "*" ? ["*"] : self::parseList($colsRaw, $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            $result = self::selectRows(
                $db,
                $table,
                empty($columns) ? ["*"] : $columns,
                $where,
                $sortField,
                $sortDir,
                $limit
            );

            return [
                "ok" => true,
                "message" => count($result["rows"]) . " Treffer aus " . $table . ".",
                "keys" => $result["keys"],
                "rows" => $result["rows"],
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^SEED\s+([a-zA-Z0-9_\-]+)\s+WITH\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $assignments = self::parseAssignments((string)$m[2], $vars, $params);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 3, $ctx), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if (empty($assignments)) {
                return [
                    "ok" => false,
                    "message" => "Keine Daten gefunden.",
                    "ctx" => $ctx
                ];
            }

            $id = $driver::insertData($db, $table, $assignments);

            if ($id <= 0) {
                return [
                    "ok" => false,
                    "message" => "Insert fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Datensatz angelegt. Neue ID: " . $id,
                "insert_id" => $id,
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^RESHAPE\s+([a-zA-Z0-9_\-]+)\s+WITH\s+(.+?)\s+WHERE\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $assignments = self::parseAssignments((string)$m[2], $vars, $params);
            $where = self::parseWhere((string)$m[3], $vars, $params);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 4, $ctx), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if (empty($assignments) || $where === null) {
                return [
                    "ok" => false,
                    "message" => "WITH oder WHERE ungültig.",
                    "ctx" => $ctx
                ];
            }

            if (!in_array($where["op"], ["=", "=="], true)) {
                return [
                    "ok" => false,
                    "message" => "RESHAPE unterstützt aktuell nur WHERE feld = wert.",
                    "ctx" => $ctx
                ];
            }

            if (self::rowIsReadonly($db, $table, [$where["field"] => $where["value"]])) {
                return ["ok" => false, "message" => "Datensatz ist readonly.", "ctx" => $ctx];
            }

            $ok = $driver::editData($db, $table, $where["field"], $where["value"], $assignments);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Update fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Datensatz aktualisiert.",
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        if (preg_match('/^DELETE\s+FROM\s+([a-zA-Z0-9_\-]+)\s+WHERE\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $command = 'ERASE FROM ' . $m[1] . ' WHERE ' . $m[2] . (isset($m[3]) && $m[3] !== '' ? ' IN ' . $m[3] : '');
        }

        if (preg_match('/^ERASE\s+FROM\s+([a-zA-Z0-9_\-]+)\s+WHERE\s+(.+?)(?:\s+IN\s+([a-zA-Z0-9_\-]+))?$/i', $command, $m)) {
            $table = self::resolveNameToken((string)$m[1], $vars);
            $where = self::parseWhere((string)$m[2], $vars, $params);
            $db = self::resolveNameToken(self::optionalDbMatch($m, 3, $ctx), $vars);

            if ($db === "" || $table === "") {
                return [
                    "ok" => false,
                    "message" => "Base oder Tabelle fehlt.",
                    "ctx" => $ctx
                ];
            }

            if ($where === null) {
                return [
                    "ok" => false,
                    "message" => "WHERE ungültig.",
                    "ctx" => $ctx
                ];
            }

            if (!in_array($where["op"], ["=", "=="], true)) {
                return [
                    "ok" => false,
                    "message" => "ERASE unterstützt aktuell nur WHERE feld = wert.",
                    "ctx" => $ctx
                ];
            }

            if (self::rowIsReadonly($db, $table, [$where["field"] => $where["value"]])) {
                return ["ok" => false, "message" => "Datensatz ist readonly.", "ctx" => $ctx];
            }

            $ok = $driver::deleteData($db, $table, $where["field"], $where["value"]);

            if (!$ok) {
                return [
                    "ok" => false,
                    "message" => "Löschen fehlgeschlagen.",
                    "ctx" => $ctx
                ];
            }

            $ctx["db"] = $db;
            $ctx["table"] = $table;

            return [
                "ok" => true,
                "message" => "Datensatz entfernt.",
                "ctx" => $ctx,
                "refresh" => true
            ];
        }

        return [
            "ok" => false,
            "message" => "Befehl nicht erkannt: " . $command,
            "ctx" => $ctx
        ];
    }


    /**
     * Sammelt Ausgabe- und Tabellenresultate auch aus verschachtelten Blöcken.
     * @param array $result Ergebnis eines Befehls.
     * @param string $command Ursprünglicher Befehl.
     * @param array $outputs Ausgabe-Stream.
     * @param array $results Ergebnisliste.
     * @param array $lastKeys Letzte Tabellenschlüssel.
     * @param array $lastRows Letzte Tabellenzeilen.
     * @return void
     */
    private static function collectResultArtifacts(array $result, string $command, array &$outputs, array &$results, array &$lastKeys, array &$lastRows): void {
        if (isset($result["keys"], $result["rows"])) {
            $isOutput = (string)($result["message"] ?? "") === "OUTPUT" || (count((array)$result["keys"]) === 1 && ((array)$result["keys"])[0] === "output");

            if ($isOutput) {
                foreach ((array)$result["rows"] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $outputs[] = [
                        "command" => $command,
                        "value" => $row["output"] ?? ""
                    ];
                }
            } else {
                $lastKeys = $result["keys"];
                $lastRows = $result["rows"];
            }

            $results[] = [
                "command" => $command,
                "keys" => $result["keys"],
                "rows" => $result["rows"],
                "type" => $isOutput ? "output" : "table"
            ];
        }

        if (isset($result["results"]) && is_array($result["results"])) {
            foreach ($result["results"] as $nested) {
                if (!is_array($nested)) {
                    continue;
                }

                $nestedCommand = (string)($nested["command"] ?? $command);
                self::collectResultArtifacts($nested, $nestedCommand, $outputs, $results, $lastKeys, $lastRows);
            }
        }
    }

    /**
     * Führt ein komplettes GreenQL-Script aus.
     * @param string $script Übergabewert.
     * @param array $ctx Übergabewert.
     * @param array $params Übergabewert.
     * @return array Rückgabewert.
     */
    /**
     * Sammelt Tabellen- und OUTPUT-Resultate rekursiv aus verschachtelten Blöcken.
     * @param array $result Ausführungsergebnis.
     * @param string $command Ursprungskommando.
     * @param array $results Sammelarray für Resultate.
     * @param array $outputs Sammelarray für Outputs.
     * @param array $lastKeys Letzte Tabellenkeys.
     * @param array $lastRows Letzte Tabellenrows.
     * @return void
     */
    private static function collectResultStreams(array $result, string $command, array &$results, array &$outputs, array &$lastKeys, array &$lastRows): void {
        if (isset($result["keys"], $result["rows"])) {
            $isOutput = (string)($result["message"] ?? "") === "OUTPUT" || (count((array)$result["keys"]) === 1 && ((array)$result["keys"])[0] === "output");

            if ($isOutput) {
                foreach ((array)$result["rows"] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $outputs[] = [
                        "command" => (string)($result["command"] ?? $command),
                        "value" => $row["output"] ?? ""
                    ];
                }
            } else {
                $lastKeys = (array)$result["keys"];
                $lastRows = (array)$result["rows"];
            }

            $results[] = [
                "command" => (string)($result["command"] ?? $command),
                "keys" => $result["keys"],
                "rows" => $result["rows"],
                "type" => $isOutput ? "output" : "table"
            ];
        }

        if (!empty($result["results"]) && is_array($result["results"])) {
            foreach ($result["results"] as $child) {
                if (is_array($child)) {
                    self::collectResultStreams($child, (string)($child["command"] ?? $command), $results, $outputs, $lastKeys, $lastRows);
                }
            }
        }
    }

    public static function run(string $script, array $ctx = [], array $params = []): array {
        self::syncInstance($ctx);

        $commands = self::splitCommands(trim($script));
        $messages = [];
        $results = [];
        $lastKeys = [];
        $lastRows = [];
        $outputs = [];
        $refresh = false;
        $okAll = true;
        $back = null;
        $hasBack = false;
        $vars = [];

        foreach ($commands as $command) {
            $command = trim((string)$command);

            if ($command === "") {
                continue;
            }

            $result = self::command($command, $ctx, $vars, $params);

            if (($result["message"] ?? "") !== "" && (string)($result["message"] ?? "") !== "OUTPUT") {
                $messages[] = [
                    "ok" => (bool)($result["ok"] ?? false),
                    "text" => (string)$result["message"]
                ];
            }

            self::collectResultStreams($result, $command, $results, $outputs, $lastKeys, $lastRows);

            if (!empty($result["refresh"])) {
                $refresh = true;
            }

            if (array_key_exists('back', $result)) {
                $back = $result['back'];
                $hasBack = true;
                break;
            }

            if (!empty($result['end_proc'])) {
                break;
            }

            if (empty($result["ok"])) {
                $okAll = false;
                break;
            }
        }

        if (!$okAll) {
            $driver = !empty($ctx["instance"]) && class_exists("GBDBv2") ? "GBDBv2" : "GBDB";

            if (method_exists($driver, "transactionStatus") && !empty($driver::transactionStatus()["active"])) {
                $driver::rollback();
                $messages[] = [
                    "ok" => true,
                    "text" => "Aktive Transaktion wegen Fehler verworfen."
                ];
            }
        }

        return [
            "ok" => $okAll,
            "messages" => $messages,
            "results" => $results,
            "outputs" => $outputs,
            "keys" => $lastKeys,
            "rows" => $lastRows,
            "ctx" => [
                "instance" => self::cleanName((string)($ctx["instance"] ?? self::$instance)),
                "db" => self::cleanName((string)($ctx["db"] ?? "")),
                "table" => self::cleanName((string)($ctx["table"] ?? ""))
            ],
            "vars" => $vars,
            "back" => $hasBack ? $back : null,
            "refresh" => $refresh
        ];
    }
}
