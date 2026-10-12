<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Renderizador de vistas en PHP plano.
 *
 * Cada vista es un archivo .php que se ejecuta en su propio ámbito y su
 * resultado se inyecta en el layout como variable $content.
 */
class View
{
    /**
     * @param array<string, mixed> $data
     * @param string|null $layout Nombre del layout en app/Views/layouts, o null para no usar ninguno.
     */
    public static function render(string $view, array $data = [], ?string $layout = 'main'): string
    {
        $content = self::renderFile(self::viewPath($view), $data);

        if ($layout === null || $layout === '') {
            return $content;
        }

        $data['content'] = $content;

        return self::renderFile(BASE_PATH . "/app/Views/layouts/{$layout}.php", $data);
    }

    private static function viewPath(string $view): string
    {
        // Acepta tanto "libros.index" como "libros/index".
        return BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
    }

    /** @param array<string, mixed> $data */
    private static function renderFile(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new RuntimeException("No se encontró la vista: {$file}");
        }

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return (string) ob_get_clean();
    }
}
