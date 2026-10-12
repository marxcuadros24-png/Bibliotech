<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Migrator;

/**
 * Gestor de migraciones de Bibliotech.
 *
 *   php cli/migrate.php              Aplica las migraciones pendientes
 *   php cli/migrate.php --status     Muestra el estado de cada migración
 *   php cli/migrate.php --rollback   Revierte el último lote aplicado
 *   php cli/migrate.php --baseline   Marca todo como aplicado sin ejecutarlo
 *   php cli/migrate.php --help       Muestra esta ayuda
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde la línea de comandos.');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/app/bootstrap.php';

$opcion = $argv[1] ?? '--migrate';

if (in_array($opcion, ['--help', '-h', 'help'], true)) {
    echo <<<TEXTO
    Gestor de migraciones de Bibliotech

      php cli/migrate.php              Aplica las migraciones pendientes
      php cli/migrate.php --status     Muestra el estado de cada migración
      php cli/migrate.php --rollback   Revierte el último lote aplicado
      php cli/migrate.php --baseline   Marca todo como aplicado sin ejecutarlo
      php cli/migrate.php --help       Muestra esta ayuda

    TEXTO;

    exit(0);
}

$migrator = new Migrator(Database::connection());

echo 'Bibliotech - migraciones (' . Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME) . ')' . PHP_EOL;

try {
    switch ($opcion) {
        case '--status':
        case '-s':
            foreach ($migrator->status() as $fila) {
                printf(
                    "  [%s] %s%s" . PHP_EOL,
                    $fila['aplicada'] ? 'x' : ' ',
                    $fila['nombre'],
                    $fila['aplicada'] ? "  (lote {$fila['lote']})" : '',
                );
            }
            break;

        case '--rollback':
        case '-r':
            $revertidas = $migrator->rollback();

            if ($revertidas === []) {
                echo '  No hay lotes que revertir.' . PHP_EOL;
                break;
            }

            foreach ($revertidas as $nombre) {
                echo "  Revertida: {$nombre}" . PHP_EOL;
            }
            break;

        case '--baseline':
            $marcadas = $migrator->baseline();

            if ($marcadas === []) {
                echo '  No había migraciones pendientes de marcar.' . PHP_EOL;
                break;
            }

            foreach ($marcadas as $nombre) {
                echo "  Marcada como aplicada: {$nombre}" . PHP_EOL;
            }
            break;

        case '--migrate':
        default:
            $aplicadas = $migrator->migrate();

            if ($aplicadas === []) {
                echo '  Nada por hacer: todas las migraciones están aplicadas.' . PHP_EOL;
                break;
            }

            foreach ($aplicadas as $nombre) {
                echo "  Aplicada: {$nombre}" . PHP_EOL;
            }
            echo '  Total: ' . count($aplicadas) . ' migración(es) en el lote actual.' . PHP_EOL;
    }
} catch (Throwable $e) {
    echo PHP_EOL . '  ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
