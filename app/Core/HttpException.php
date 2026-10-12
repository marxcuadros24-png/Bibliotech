<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Excepción que transporta un código de estado HTTP.
 * El manejador global de App la usa para decidir qué vista de error mostrar.
 */
class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
    ) {
        parent::__construct($message === '' ? self::defaultMessage($statusCode) : $message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    private static function defaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'La petición no es válida.',
            403 => 'No tienes permiso para acceder a este recurso.',
            404 => 'La página que buscas no existe.',
            405 => 'El método HTTP no está permitido para esta ruta.',
            419 => 'La sesión ha expirado, vuelve a intentarlo.',
            500 => 'Ocurrió un error interno en el servidor.',
            default => 'Se produjo un error al procesar la petición.',
        };
    }
}
