<?php

declare(strict_types=1);

use App\Core\Controller;
use App\Core\Session;

if (!function_exists('env')) {
    /**
     * Lee una variable de entorno con conversión de los literales habituales.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (!function_exists('e')) {
    /** Escapa texto para insertarlo con seguridad en HTML. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** Construye una URL interna respetando el subdirectorio del proyecto. */
    function url(string $path = ''): string
    {
        return Controller::url($path);
    }
}

if (!function_exists('asset')) {
    /** URL de un recurso estático dentro de public/assets. */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('old')) {
    /**
     * Valor enviado previamente en un formulario (tras un error de validación).
     */
    function old(string $key, mixed $default = ''): mixed
    {
        static $values = null;

        if ($values === null) {
            $values = Session::pull('_old', []);
        }

        return is_array($values) && array_key_exists($key, $values) && $values[$key] !== ''
            ? $values[$key]
            : $default;
    }
}

if (!function_exists('errores')) {
    /**
     * Errores de validación de la petición anterior.
     *
     * @return array<string, string>|list<string>
     */
    function errores(?string $campo = null): array
    {
        static $errores = null;

        if ($errores === null) {
            $errores = Session::pull('_errors', []);
            $errores = is_array($errores) ? $errores : [];
        }

        if ($campo === null) {
            return $errores;
        }

        $value = $errores[$campo] ?? [];

        return is_array($value) ? $value : [$value];
    }
}

if (!function_exists('hayErrores')) {
    function hayErrores(): bool
    {
        return errores() !== [];
    }
}

if (!function_exists('mensaje_flash')) {
    /** Mensaje de éxito/aviso de la petición anterior. */
    function mensaje_flash(string $tipo = 'exito'): ?string
    {
        static $messages = null;

        if ($messages === null) {
            $messages = Session::pull('_messages', []);
            $messages = is_array($messages) ? $messages : [];
        }

        $value = $messages[$tipo] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}

if (!function_exists('csrf_field')) {
    /** Campo oculto con el token CSRF para formularios POST. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Session::csrfToken()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('activo')) {
    /** Devuelve "activo" cuando la sección actual coincide con la ruta dada. */
    function activo(string $prefijo): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return str_contains($path, $prefijo) ? 'activo' : '';
    }
}

if (!function_exists('puede')) {
    /** Atajo para consultar permisos desde las vistas. */
    function puede(string $modulo, string $accion): bool
    {
        return App\Core\Auth::puede($modulo, $accion);
    }
}

if (!function_exists('moneda')) {
    /** Formatea un importe con el símbolo configurado en config/biblioteca.php. */
    function moneda(float|int|string|null $monto): string
    {
        $simbolo = (string) App\Core\App::config('biblioteca.moneda', 'S/');

        return $simbolo . ' ' . number_format((float) $monto, 2);
    }
}

if (!function_exists('fecha')) {
    /** Fecha legible; devuelve un guion si viene vacía. */
    function fecha(?string $valor, string $formato = 'd/m/Y'): string
    {
        if ($valor === null || $valor === '' || str_starts_with($valor, '0000')) {
            return '—';
        }

        $marca = strtotime($valor);

        return $marca === false ? '—' : date($formato, $marca);
    }
}

if (!function_exists('nombre_estado')) {
    /** Traduce los estados de préstamos y multas a texto legible. */
    function nombre_estado(string $estado): string
    {
        return [
            'activo'     => 'Activo',
            'devuelto'   => 'Devuelto',
            'vencido'    => 'Vencido',
            'cancelado'  => 'Cancelado',
            'pendiente'  => 'Pendiente',
            'pagada'     => 'Pagada',
            'condonada'  => 'Condonada',
        ][$estado] ?? ucfirst($estado);
    }
}

if (!function_exists('clase_estado')) {
    /** Clase CSS que colorea la etiqueta según el estado. */
    function clase_estado(string $estado): string
    {
        return match ($estado) {
            'activo', 'pagada'    => 'etiqueta--disponible',
            'vencido', 'pendiente' => 'etiqueta--agotado',
            default               => '',
        };
    }
}
