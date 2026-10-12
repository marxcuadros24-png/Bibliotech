<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encapsula la petición HTTP entrante.
 *
 * Los controladores nunca leen $_GET/$_POST directamente: piden los datos
 * a esta clase, lo que centraliza la validación de entrada.
 */
class Request
{
    public readonly string $method;
    public readonly string $path;

    /** @var array<string, mixed> */
    public readonly array $query;

    /** @var array<string, mixed> */
    public readonly array $body;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = self::resolvePath();
        $this->query = $_GET;
        $this->body = $_POST;
    }

    /**
     * Ruta limpia de la petición, sin el prefijo del directorio donde vive
     * el front controller (el proyecto puede estar en la raíz o en subcarpeta).
     */
    private static function resolvePath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if (!is_string($uri) || $uri === '') {
            $uri = '/';
        }

        $base = self::baseUrl();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim($uri, '/');

        return $uri === '//' ? '/' : $uri;
    }

    /**
     * Prefijo URL bajo el que se sirve la aplicación ('' si está en la raíz).
     */
    public static function baseUrl(): string
    {
        // El servidor embebido de PHP (php -S ... server.php) sirve todo desde la
        // raíz, pero coloca la URL solicitada dentro de SCRIPT_NAME, así que ahí
        // no se puede deducir ningún prefijo.
        if (PHP_SAPI === 'cli-server') {
            return '';
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        // Si SCRIPT_NAME no apunta a un archivo PHP real, no es fiable.
        if (!str_ends_with($script, '.php')) {
            return '';
        }

        // dirname() usa el separador del sistema, así que en Windows hay que
        // volver a normalizar a '/' antes de comparar rutas.
        $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($directory === '' || $directory === '.') {
            return '';
        }

        // El front controller vive en /public: ese segmento no forma parte de la URL pública.
        if (str_ends_with($directory, '/public')) {
            $directory = substr($directory, 0, -strlen('/public'));
        }

        return $directory === '/' ? '' : $directory;
    }

    /** Valor del cuerpo (POST) y, si no existe, de la query string. */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function boolean(string $key): bool
    {
        $value = $this->input($key);

        return in_array($value, ['1', 1, true, 'on', 'true', 'si', 'sí'], true);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /** Indica si la petición se hizo vía AJAX/fetch. */
    public function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json')
            || strtolower($requestedWith) === 'xmlhttprequest';
    }
}
