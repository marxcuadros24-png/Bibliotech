<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Punto de arranque de la aplicación.
 *
 * Se encarga del entorno, la sesión, el manejo global de excepciones
 * y de entregar la petición al enrutador.
 */
class App
{
    /** @var array<string, mixed> */
    private static array $config = [];

    public static function boot(): void
    {
        Env::load(BASE_PATH . '/.env');

        /** @var array<string, mixed> $config */
        $config = require BASE_PATH . '/config/app.php';
        self::$config['app'] = $config;

        date_default_timezone_set((string) ($config['timezone'] ?? 'UTC'));

        if (($config['debug'] ?? false) === true) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }

        Session::start();
        self::registerExceptionHandler($config);
    }

    /**
     * Lee un valor de configuración con notación de punto: "app.debug" o
     * "biblioteca.dias_prestamo". El archivo se carga la primera vez que se
     * pide, así que agregar uno nuevo en config/ no requiere tocar el núcleo.
     */
    public static function config(string $clave, mixed $default = null): mixed
    {
        [$archivo, $subclave] = array_pad(explode('.', $clave, 2), 2, '');

        if (!array_key_exists($archivo, self::$config)) {
            $ruta = BASE_PATH . "/config/{$archivo}.php";

            if (!is_file($ruta)) {
                return $default;
            }

            self::$config[$archivo] = require $ruta;
        }

        $valor = self::$config[$archivo];

        if ($subclave === '') {
            return $valor;
        }

        return is_array($valor) ? ($valor[$subclave] ?? $default) : $default;
    }

    public static function run(): void
    {
        $router = new Router();

        require BASE_PATH . '/routes/web.php';

        $router->dispatch(new Request());
    }

    /** @param array<string, mixed> $config */
    private static function registerExceptionHandler(array $config): void
    {
        set_exception_handler(static function (Throwable $e) use ($config): void {
            $status = $e instanceof HttpException ? $e->statusCode() : 500;
            $debug = ($config['debug'] ?? false) === true;

            // En consola los errores van a stderr como texto, nunca como HTML.
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, sprintf(
                    "[%s] %s en %s:%d" . PHP_EOL,
                    date('Y-m-d H:i:s'),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                ));

                if ($debug) {
                    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
                }

                exit(1);
            }

            // Los errores 5xx siempre se registran, aunque no se muestren al usuario.
            if ($status >= 500) {
                error_log(sprintf(
                    '[%s] %s en %s:%d',
                    date('Y-m-d H:i:s'),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                ));
            }

            if (!headers_sent()) {
                http_response_code($status);
            }

            $mensaje = ($debug || $status < 500)
                ? $e->getMessage()
                : 'Ocurrió un error inesperado en el servidor.';

            $data = [
                'title'   => "Error {$status}",
                'status'  => $status,
                'mensaje' => $mensaje,
                'detalle' => $debug ? $e->getTraceAsString() : null,
            ];

            $vista = is_file(BASE_PATH . "/app/Views/errors/{$status}.php")
                ? "errors.{$status}"
                : 'errors.500';

            try {
                echo View::render($vista, $data);
            } catch (Throwable $inner) {
                // Si el layout también falla, se responde algo mínimo pero legible.
                error_log('Fallo al renderizar la vista de error: ' . $inner->getMessage());
                echo "<h1>Error {$status}</h1><p>" . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        });
    }
}
