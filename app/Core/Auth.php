<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Usuario;

/**
 * Autenticación y autorización.
 *
 * Mantiene al usuario de la sesión y responde las preguntas de permisos
 * consultando config/permisos.php, para que los controladores no tengan
 * condiciones de rol repartidas por todo el código.
 */
class Auth
{
    private const CLAVE_SESION = '_usuario';

    /** @var array<string, mixed>|null */
    private static ?array $usuario = null;

    private static bool $cargado = false;

    /**
     * Verifica las credenciales y, si son correctas, abre la sesión.
     *
     * Siempre ejecuta password_verify() aunque el correo no exista, para que el
     * tiempo de respuesta no revele qué cuentas están registradas.
     */
    public static function intentar(string $email, string $password): bool
    {
        $modelo = new Usuario();
        $usuario = $modelo->porEmail($email);

        $hash = $usuario['password_hash'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidinva';

        if (!password_verify($password, $hash)) {
            return false;
        }

        // Cuenta inexistente o bloqueada: mismo resultado para el usuario.
        if ($usuario === null || $usuario['estado'] !== 'activo') {
            return false;
        }

        // Si el algoritmo de hash quedó desactualizado, se renueva en el momento.
        if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
            $modelo->cambiarPassword((int) $usuario['id'], $password);
        }

        self::abrirSesion($usuario);

        return true;
    }

    /** @param array<string, mixed> $usuario */
    public static function abrirSesion(array $usuario): void
    {
        Session::regenerate();

        Session::set(self::CLAVE_SESION, [
            'id'     => (int) $usuario['id'],
            'nombre' => (string) $usuario['nombre'],
            'email'  => (string) $usuario['email'],
            'rol'    => (string) $usuario['rol'],
        ]);

        self::$usuario = null;
        self::$cargado = false;
    }

    public static function salir(): void
    {
        self::$usuario = null;
        self::$cargado = false;

        Session::remove(self::CLAVE_SESION);
        Session::regenerate();
    }

    public static function verificado(): bool
    {
        return self::usuario() !== null;
    }

    public static function invitado(): bool
    {
        return !self::verificado();
    }

    /** @return array<string, mixed>|null */
    public static function usuario(): ?array
    {
        if (self::$cargado) {
            return self::$usuario;
        }

        self::$cargado = true;

        $guardado = Session::get(self::CLAVE_SESION);

        if (!is_array($guardado) || !isset($guardado['id'])) {
            self::$usuario = null;

            return null;
        }

        // Se relee de la base para reflejar de inmediato cambios de rol o estado.
        $actual = (new Usuario())->find((int) $guardado['id']);

        if ($actual === null || $actual['estado'] !== 'activo') {
            self::salir();
            self::$usuario = null;

            return null;
        }

        self::$usuario = [
            'id'     => (int) $actual['id'],
            'nombre' => (string) $actual['nombre'],
            'email'  => (string) $actual['email'],
            'rol'    => (string) $actual['rol'],
        ];

        return self::$usuario;
    }

    public static function id(): ?int
    {
        return self::usuario()['id'] ?? null;
    }

    public static function rol(): ?string
    {
        return self::usuario()['rol'] ?? null;
    }

    public static function nombre(): string
    {
        return (string) (self::usuario()['nombre'] ?? '');
    }

    public static function es(string ...$roles): bool
    {
        $rol = self::rol();

        return $rol !== null && in_array($rol, $roles, true);
    }

    /**
     * ¿El rol actual puede ejecutar $accion sobre $modulo?
     * La respuesta sale siempre de config/permisos.php.
     */
    public static function puede(string $modulo, string $accion): bool
    {
        $rol = self::rol();

        if ($rol === null) {
            return false;
        }

        $permisos = self::permisos()[$rol] ?? [];

        return in_array($accion, $permisos[$modulo] ?? [], true);
    }

    /**
     * ¿Este rol administra el módulo? Atajo para "ver, crear, editar y eliminar".
     */
    public static function administra(string $modulo): bool
    {
        return self::puede($modulo, 'crear') && self::puede($modulo, 'editar');
    }

    /**
     * Página de destino tras iniciar sesión. El panel ya adapta su contenido
     * al rol, así que todos entran por el mismo sitio.
     */
    public static function destinoInicial(): string
    {
        return '';
    }

    /** @return array<string, array<string, list<string>>> */
    public static function permisos(): array
    {
        /** @var array<string, array<string, list<string>>> $permisos */
        $permisos = require BASE_PATH . '/config/permisos.php';

        return $permisos;
    }
}
