<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Manejo de sesión: datos persistentes, mensajes flash y token CSRF.
 */
class Session
{
    private const FLASH_KEY = '_flash';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string ...$keys): void
    {
        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** Guarda un valor que solo estará disponible en la siguiente petición. */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    /** Lee un valor flash y lo elimina en el mismo momento. */
    public static function pull(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION[self::FLASH_KEY][$key])) {
            return $default;
        }

        $value = $_SESSION[self::FLASH_KEY][$key];
        unset($_SESSION[self::FLASH_KEY][$key]);

        return $value;
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['_token']) || !is_string($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }

    public static function verifyCsrf(mixed $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $expected = $_SESSION['_token'] ?? null;

        return is_string($expected) && hash_equals($expected, $token);
    }
}
