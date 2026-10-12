<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión PDO compartida.
 *
 * Se instancia una sola vez por petición; los modelos obtienen la conexión
 * a través de Database::connection().
 */
class Database
{
    private static ?PDO $connection = null;

    /** Evita instanciar la clase: solo se usan métodos estáticos. */
    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        /** @var array{driver:string,host:string,port:string,database:string,username:string,password:string,charset:string} $config */
        $config = require BASE_PATH . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'],
        );

        try {
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "No se pudo conectar a la base de datos '{$config['database']}': {$e->getMessage()}",
                0,
                $e,
            );
        }

        return self::$connection;
    }
}
