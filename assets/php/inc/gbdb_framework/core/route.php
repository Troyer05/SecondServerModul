<?php

class Route {
    private static array $routes = [];

    /** Registriert eine GET-Route */
    public static function get(string $path, callable $callback): void {
        self::$routes['GET'][$path] = $callback;
    }

    /** Registriert eine POST-Route */
    public static function post(string $path, callable $callback): void {
        self::$routes['POST'][$path] = $callback;
    }

    /** Führt die passende Route aus */
    public static function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach (self::$routes[$method] ?? [] as $path => $callback) {
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $path);
            $pattern = "@^" . str_replace('/', '\/', $pattern) . "$@";

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Erstes Element ist der komplette Pfad
                echo call_user_func_array($callback, $matches);
                
                return;
            }
        }

        http_response_code(404);
        echo "404 - Route nicht gefunden";
    }
}
