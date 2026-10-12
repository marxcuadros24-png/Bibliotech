<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador mínimo con parámetros dinámicos.
 *
 * Las rutas se declaran en routes/web.php con sintaxis {parametro}:
 *   $router->get('/libros/{id}', 'LibroController@show');
 */
class Router
{
    /** @var array<string, list<array{pattern:string, action:string, uri:string}>> */
    private array $routes = [];

    public function get(string $uri, string $action): self
    {
        return $this->add('GET', $uri, $action);
    }

    public function post(string $uri, string $action): self
    {
        return $this->add('POST', $uri, $action);
    }

    public function add(string $method, string $uri, string $action): self
    {
        $this->routes[strtoupper($method)][] = [
            'pattern' => $this->toPattern($uri),
            'action'  => $action,
            'uri'     => '/' . trim($uri, '/'),
        ];

        return $this;
    }

    /**
     * Busca la ruta que corresponde a la petición y ejecuta su controlador.
     *
     * @throws HttpException 404 si no hay coincidencia, 405 si la URI existe con otro método.
     */
    public function dispatch(Request $request): void
    {
        foreach ($this->routes[$request->method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            $this->invoke($route['action'], $params, $request);

            return;
        }

        foreach ($this->routes as $method => $routes) {
            if ($method === $request->method) {
                continue;
            }

            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $request->path)) {
                    throw new HttpException(
                        405,
                        "La ruta {$request->path} no acepta peticiones {$request->method}.",
                    );
                }
            }
        }

        throw new HttpException(404, "No existe la ruta {$request->path}.");
    }

    /** Convierte /libros/{id} en una expresión regular con grupos nombrados. */
    private function toPattern(string $uri): string
    {
        $uri = '/' . trim($uri, '/');

        $pattern = preg_replace(
            '#\{([A-Za-z_][A-Za-z0-9_]*)\}#',
            '(?P<$1>[^/]+)',
            $uri,
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Instancia el controlador y ejecuta la acción indicada ("Clase@metodo").
     *
     * @param array<string, string> $params
     */
    private function invoke(string $action, array $params, Request $request): void
    {
        [$controller, $method] = array_pad(explode('@', $action, 2), 2, 'index');

        $class = str_contains($controller, '\\')
            ? $controller
            : 'App\\Controllers\\' . $controller;

        if (!class_exists($class)) {
            throw new HttpException(500, "El controlador {$class} no existe.");
        }

        $instance = new $class($request);

        if (!method_exists($instance, $method)) {
            throw new HttpException(500, "La acción {$method}() no existe en {$class}.");
        }

        // Los segmentos de la URL llegan como string; los numéricos se castean a int
        // para que coincidan con las firmas tipadas de los controladores.
        $arguments = array_map(
            static fn (string $value): int|string => ctype_digit($value) ? (int) $value : $value,
            array_values($params),
        );

        $instance->{$method}(...$arguments);
    }
}
