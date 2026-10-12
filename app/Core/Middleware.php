<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Filtros que se ejecutan antes del controlador.
 *
 * Se declaran en routes/web.php como cadenas legibles:
 *   'auth'                      exige sesión iniciada
 *   'invitado'                  exige NO tener sesión (para el login)
 *   'rol:administrador'         exige uno de los roles indicados
 *   'permiso:usuarios,crear'    consulta config/permisos.php
 *
 * Tener los filtros en la ruta, y no dentro del controlador, permite ver de un
 * vistazo qué protección tiene cada endpoint.
 */
class Middleware
{
    /** @var list<string> */
    private static array $registrados = ['auth', 'invitado', 'rol', 'permiso'];

    /** @param list<string> $definiciones */
    public static function procesar(array $definiciones, Request $request): void
    {
        foreach ($definiciones as $definicion) {
            [$nombre, $argumentos] = array_pad(explode(':', $definicion, 2), 2, '');

            $nombre = trim($nombre);
            $argumentos = $argumentos === ''
                ? []
                : array_map('trim', explode(',', $argumentos));

            if (!in_array($nombre, self::$registrados, true)) {
                throw new HttpException(500, "El filtro '{$nombre}' no está registrado.");
            }

            match ($nombre) {
                'auth'     => self::auth($request),
                'invitado' => self::invitado(),
                'rol'      => self::rol($argumentos, $request),
                'permiso'  => self::permiso($argumentos, $request),
            };
        }
    }

    private static function auth(Request $request): void
    {
        if (Auth::verificado()) {
            return;
        }

        // Se recuerda a dónde quería llegar para llevarlo ahí tras iniciar sesión.
        Session::set('_intended', $request->path);
        Session::flash('_messages', ['error' => 'Inicia sesión para continuar.']);

        self::desviar('login');
    }

    private static function invitado(): void
    {
        if (Auth::verificado()) {
            self::desviar(Auth::destinoInicial());
        }
    }

    /** @param list<string> $roles */
    private static function rol(array $roles, Request $request): void
    {
        // Sin sesión el problema no es de roles: primero se pide iniciar sesión.
        // Si no, un visitante anónimo recibiría un 403 confuso en lugar del login.
        self::auth($request);

        if ($roles === []) {
            throw new HttpException(500, 'El filtro "rol" necesita al menos un rol.');
        }

        if (!Auth::es(...$roles)) {
            throw new HttpException(403, 'Tu rol no tiene acceso a esta sección.');
        }
    }

    /** @param list<string> $argumentos */
    private static function permiso(array $argumentos, Request $request): void
    {
        self::auth($request);

        [$modulo, $accion] = array_pad($argumentos, 2, '');

        if ($modulo === '' || $accion === '') {
            throw new HttpException(500, 'El filtro "permiso" espera módulo y acción.');
        }

        if (!Auth::puede($modulo, $accion)) {
            throw new HttpException(403, 'No tienes permiso para realizar esta acción.');
        }
    }

    private static function desviar(string $ruta): never
    {
        header('Location: ' . Controller::url($ruta), true, 302);
        exit;
    }
}
