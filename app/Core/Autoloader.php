<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader propio basado en PSR-4.
 *
 * Registra un prefijo de namespace (por defecto "App\") apuntando a un
 * directorio físico (por defecto la carpeta "app/"), de forma que la clase
 * App\Controllers\LibroController se resuelve a app/Controllers/LibroController.php.
 */
class Autoloader
{
    public static function register(string $baseDirectory, string $prefix = 'App\\'): void
    {
        $baseDirectory = rtrim(str_replace('\\', '/', $baseDirectory), '/');
        $prefix = rtrim($prefix, '\\') . '\\';

        spl_autoload_register(static function (string $class) use ($baseDirectory, $prefix): void {
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $file = $baseDirectory . '/' . str_replace('\\', '/', $relative) . '.php';

            if (is_file($file)) {
                require $file;
            }
        });
    }
}
