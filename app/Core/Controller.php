<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controlador base.
 *
 * Agrupa las respuestas típicas (renderizar, redirigir, responder JSON)
 * y la verificación del token CSRF en formularios POST.
 */
abstract class Controller
{
    public function __construct(protected readonly Request $request)
    {
    }

    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        echo View::render($view, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Redirige a una ruta interna. El prefijo del proyecto se añade de forma
     * automática, así funciona igual en la raíz del dominio o en subcarpeta.
     */
    protected function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . self::url($path), true, $status);
        exit;
    }

    /** Redirige a la petición anterior guardada en sesión. */
    protected function back(string $fallback = '/'): never
    {
        $previous = Session::pull('_previous_url', $fallback);

        $this->redirect(is_string($previous) ? $previous : $fallback);
    }

    /**
     * Aborta la petición con un código HTTP.
     *
     * @throws HttpException
     */
    protected function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }

    /**
     * Valida el token CSRF de un formulario.
     * Debe invocarse al inicio de toda acción que reciba POST.
     *
     * @throws HttpException
     */
    protected function protegerCsrf(): void
    {
        if (!Session::verifyCsrf($this->request->input('_token'))) {
            throw new HttpException(419, 'El token de seguridad no es válido. Vuelve a enviar el formulario.');
        }
    }

    /** Guarda la URL actual para poder volver a ella tras un error de validación. */
    protected function recordarUrl(): void
    {
        Session::set('_previous_url', $this->request->path);
    }

    /** URL absoluta (relativa al host) de una ruta de la aplicación. */
    public static function url(string $path = ''): string
    {
        return Request::baseUrl() . '/' . ltrim($path, '/');
    }
}
