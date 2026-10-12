<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Usuario;

/**
 * Gestión de usuarios desde la consola.
 *
 *   php cli/usuario.php --listar
 *   php cli/usuario.php --password <correo> <nueva-clave>
 *   php cli/usuario.php --resetear-todos <clave-comun>
 *   php cli/usuario.php --crear <correo> <nombre> <rol> <clave>
 *
 * Sirve para no dejar contraseñas escritas en el código ni en un seeder, y
 * para poder recuperar el acceso si el administrador se queda fuera.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde la línea de comandos.');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/app/bootstrap.php';

const CLAVE_MINIMA = 8;

$opcion = $argv[1] ?? '--listar';

function ayuda(): void
{
    echo <<<TEXTO
    Gestión de usuarios de Bibliotech

      php cli/usuario.php --listar
      php cli/usuario.php --password <correo> <nueva-clave>
      php cli/usuario.php --resetear-todos <clave-comun>
      php cli/usuario.php --crear <correo> <nombre> <rol> <clave>

    Roles válidos: administrador, bibliotecario, estudiante
    La contraseña debe tener al menos 8 caracteres.

    TEXTO;
}

/** @return list<string> */
function roles(): array
{
    return Usuario::ROLES;
}

try {
    $usuarios = new Usuario();

    switch ($opcion) {
        case '--help':
        case '-h':
            ayuda();
            break;

        case '--listar':
        case '-l':
            $filas = Database::connection()->query(
                'SELECT id, nombre, email, rol, estado FROM usuarios ORDER BY id'
            )->fetchAll();

            if ($filas === []) {
                echo '  No hay usuarios registrados.' . PHP_EOL;
                break;
            }

            printf("  %-4s %-28s %-32s %-15s %s" . PHP_EOL, 'ID', 'NOMBRE', 'CORREO', 'ROL', 'ESTADO');
            echo '  ' . str_repeat('-', 95) . PHP_EOL;

            foreach ($filas as $fila) {
                printf(
                    "  %-4s %-28s %-32s %-15s %s" . PHP_EOL,
                    $fila['id'],
                    mb_strimwidth((string) $fila['nombre'], 0, 27, '…'),
                    mb_strimwidth((string) $fila['email'], 0, 31, '…'),
                    $fila['rol'],
                    $fila['estado'],
                );
            }

            echo PHP_EOL . '  Total: ' . count($filas) . ' usuario(s).' . PHP_EOL;
            break;

        case '--password':
        case '-p':
            $correo = mb_strtolower(trim((string) ($argv[2] ?? '')));
            $clave = (string) ($argv[3] ?? '');

            if ($correo === '' || $clave === '') {
                throw new InvalidArgumentException('Faltan argumentos. Usa: --password <correo> <nueva-clave>');
            }

            if (mb_strlen($clave) < CLAVE_MINIMA) {
                throw new InvalidArgumentException('La contraseña debe tener al menos ' . CLAVE_MINIMA . ' caracteres.');
            }

            $usuario = $usuarios->porEmail($correo);

            if ($usuario === null) {
                throw new InvalidArgumentException("No existe ningún usuario con el correo {$correo}.");
            }

            $usuarios->cambiarPassword((int) $usuario['id'], $clave);

            echo "  Contraseña actualizada para {$correo}." . PHP_EOL;
            break;

        case '--resetear-todos':
            $clave = (string) ($argv[2] ?? '');

            if ($clave === '') {
                throw new InvalidArgumentException('Falta la contraseña. Usa: --resetear-todos <clave-comun>');
            }

            if (mb_strlen($clave) < CLAVE_MINIMA) {
                throw new InvalidArgumentException('La contraseña debe tener al menos ' . CLAVE_MINIMA . ' caracteres.');
            }

            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $afectados = Database::connection()
                ->prepare('UPDATE usuarios SET password_hash = :hash')
                ->execute(['hash' => $hash]);

            if ($afectados) {
                $total = (int) Database::connection()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
                echo "  Contraseña restablecida en {$total} usuario(s)." . PHP_EOL;
                echo '  Recuerda cambiarlas desde la aplicación.' . PHP_EOL;
            }
            break;

        case '--crear':
        case '-c':
            $correo = mb_strtolower(trim((string) ($argv[2] ?? '')));
            $nombre = trim((string) ($argv[3] ?? ''));
            $rol = mb_strtolower(trim((string) ($argv[4] ?? '')));
            $clave = (string) ($argv[5] ?? '');

            if ($correo === '' || $nombre === '' || $rol === '' || $clave === '') {
                throw new InvalidArgumentException('Faltan argumentos. Usa: --crear <correo> <nombre> <rol> <clave>');
            }

            if (!in_array($rol, roles(), true)) {
                throw new InvalidArgumentException('Rol inválido. Válidos: ' . implode(', ', roles()) . '.');
            }

            if (mb_strlen($clave) < CLAVE_MINIMA) {
                throw new InvalidArgumentException('La contraseña debe tener al menos ' . CLAVE_MINIMA . ' caracteres.');
            }

            if ($usuarios->emailDuplicado($correo)) {
                throw new InvalidArgumentException("Ya existe un usuario con el correo {$correo}.");
            }

            $id = $usuarios->crear([
                'nombre' => $nombre,
                'email'  => $correo,
                'rol'    => $rol,
                'estado' => 'activo',
            ], $clave);

            echo "  Usuario creado con id {$id}: {$correo} ({$rol})." . PHP_EOL;
            break;

        default:
            echo "  Opción no reconocida: {$opcion}" . PHP_EOL . PHP_EOL;
            ayuda();
            exit(1);
    }
} catch (Throwable $e) {
    echo PHP_EOL . '  ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
