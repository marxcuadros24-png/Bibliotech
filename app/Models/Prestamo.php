<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use App\Core\Model;
use DateTimeImmutable;
use RuntimeException;

/**
 * Modelo de préstamos.
 *
 * Además del CRUD heredado, aquí viven las dos operaciones que tocan varias
 * tablas a la vez: registrar un préstamo (descuenta stock) y devolverlo
 * (devuelve stock y genera la multa si hubo mora). Ambas van en una
 * transacción para que el inventario no quede descuadrado.
 */
class Prestamo extends Model
{
    public const ESTADOS = ['activo', 'devuelto', 'vencido', 'cancelado'];

    protected string $table = 'prestamos';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_usuario',
        'id_libro',
        'id_reserva',
        'fecha_prestamo',
        'fecha_devolucion_esperada',
        'fecha_devolucion_real',
        'estado',
    ];

    // -----------------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------------

    /**
     * Préstamos con el usuario y el libro resueltos.
     *
     * @return list<array<string, mixed>>
     */
    public function listado(?string $estado = null, ?int $idUsuario = null, ?string $busqueda = null): array
    {
        $sql = "SELECT p.*, u.nombre AS usuario, u.email AS usuario_email,
                       l.titulo AS libro, l.autor AS libro_autor, l.isbn AS libro_isbn,
                       (SELECT COUNT(*) FROM multas m WHERE m.id_prestamo = p.id) AS total_multas,
                       DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) AS dias_atraso
                FROM prestamos p
                JOIN usuarios u ON u.id = p.id_usuario
                JOIN libros l   ON l.id = p.id_libro";

        $condiciones = [];
        $parametros = [];

        if ($estado !== null && $estado !== '') {
            $condiciones[] = 'p.estado = :estado';
            $parametros['estado'] = $estado;
        }

        if ($idUsuario !== null) {
            $condiciones[] = 'p.id_usuario = :id_usuario';
            $parametros['id_usuario'] = $idUsuario;
        }

        if ($busqueda !== null && $busqueda !== '') {
            $condiciones[] = '(u.nombre LIKE :b_usuario OR u.email LIKE :b_email
                               OR l.titulo LIKE :b_titulo OR l.autor LIKE :b_autor)';

            $patron = '%' . $busqueda . '%';
            $parametros += [
                'b_usuario' => $patron,
                'b_email'   => $patron,
                'b_titulo'  => $patron,
                'b_autor'   => $patron,
            ];
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        // Primero los vigentes, y dentro de ellos los más atrasados.
        $sql .= " ORDER BY FIELD(p.estado, 'vencido', 'activo', 'devuelto', 'cancelado'),
                          p.fecha_devolucion_esperada ASC, p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function detalle(int $id): ?array
    {
        $sql = 'SELECT p.*, u.nombre AS usuario, u.email AS usuario_email,
                       l.titulo AS libro, l.autor AS libro_autor, l.isbn AS libro_isbn,
                       DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) AS dias_atraso
                FROM prestamos p
                JOIN usuarios u ON u.id = p.id_usuario
                JOIN libros l   ON l.id = p.id_libro
                WHERE p.id = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /** Multas asociadas a un préstamo. */
    public function multas(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM multas WHERE id_prestamo = :id ORDER BY id DESC'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetchAll();
    }

    /**
     * Marca como vencidos los préstamos activos cuya fecha esperada ya pasó.
     *
     * Se ejecuta al listar en lugar de depender de un proceso programado, así
     * el estado refleja la realidad sin necesidad de cron.
     *
     * @return int Filas actualizadas.
     */
    public function sincronizarVencidos(): int
    {
        return (int) $this->db
            ->query("UPDATE prestamos
                     SET estado = 'vencido'
                     WHERE estado = 'activo' AND fecha_devolucion_esperada < CURDATE()")
            ->rowCount();
    }

    /** Totales por estado para el panel. */
    public function contarPorEstado(): array
    {
        $filas = $this->db->query(
            'SELECT estado, COUNT(*) AS total FROM prestamos GROUP BY estado'
        )->fetchAll();

        $conteo = array_fill_keys(self::ESTADOS, 0);

        foreach ($filas as $fila) {
            $conteo[(string) $fila['estado']] = (int) $fila['total'];
        }

        return $conteo;
    }

    /** Préstamos vigentes (activos o vencidos) de un usuario concreto. */
    public function vigentesDe(int $idUsuario): array
    {
        return $this->listadoFiltrado($idUsuario, ['activo', 'vencido']);
    }

    /** @param list<string> $estados */
    private function listadoFiltrado(int $idUsuario, array $estados): array
    {
        $marcadores = [];
        $parametros = ['id_usuario' => $idUsuario];

        foreach ($estados as $indice => $estado) {
            $marcadores[] = ":estado{$indice}";
            $parametros["estado{$indice}"] = $estado;
        }

        $sql = "SELECT p.*, l.titulo AS libro, l.autor AS libro_autor,
                       DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) AS dias_atraso
                FROM prestamos p
                JOIN libros l ON l.id = p.id_libro
                WHERE p.id_usuario = :id_usuario
                  AND p.estado IN (" . implode(', ', $marcadores) . ')
                ORDER BY p.fecha_devolucion_esperada ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------------
    // Operaciones de negocio
    // -----------------------------------------------------------------------

    /**
     * Registra un préstamo y descuenta un ejemplar del inventario.
     *
     * El stock se lee con FOR UPDATE dentro de la transacción: si dos
     * bibliotecarios prestan el último ejemplar a la vez, el segundo falla en
     * lugar de dejar el inventario en negativo.
     *
     * @throws RuntimeException si el usuario o el libro no sirven, o no hay stock.
     */
    public function registrar(int $idUsuario, int $idLibro, ?string $fechaDevolucion = null): int
    {
        $diasPorDefecto = (int) App::config('biblioteca.dias_prestamo', 7);

        $hoy = new DateTimeImmutable('today');
        $esperada = $fechaDevolucion !== null && $fechaDevolucion !== ''
            ? new DateTimeImmutable($fechaDevolucion)
            : $hoy->modify("+{$diasPorDefecto} days");

        if ($esperada <= $hoy) {
            throw new RuntimeException('La fecha de devolución debe ser posterior a hoy.');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $usuario = $this->bloquearUsuario($idUsuario);
            $libro = $this->bloquearLibro($idLibro);

            if ((int) $libro['stock_disponible'] < 1) {
                throw new RuntimeException(
                    "No quedan ejemplares disponibles de «{$libro['titulo']}»."
                );
            }

            $sentencia = $db->prepare(
                "INSERT INTO prestamos
                    (id_usuario, id_libro, fecha_prestamo, fecha_devolucion_esperada, estado)
                 VALUES (:usuario, :libro, :prestamo, :esperada, 'activo')"
            );

            $sentencia->execute([
                'usuario'  => $idUsuario,
                'libro'    => $idLibro,
                'prestamo' => $hoy->format('Y-m-d'),
                'esperada' => $esperada->format('Y-m-d'),
            ]);

            $idPrestamo = (int) $db->lastInsertId();

            $db->prepare(
                'UPDATE libros SET stock_disponible = stock_disponible - 1 WHERE id = :id'
            )->execute(['id' => $idLibro]);

            $db->commit();

            return $idPrestamo;
        } catch (\Throwable $e) {
            $db->rollBack();

            throw $e;
        }
    }

    /**
     * Registra la devolución, repone el ejemplar y genera la multa si hubo mora.
     *
     * @return array{monto: float, dias: int}|null Datos de la multa, o null si no hubo mora.
     *
     * @throws RuntimeException si el préstamo ya estaba cerrado.
     */
    public function devolver(int $id, ?string $fechaDevolucion = null): ?array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $sentencia = $db->prepare('SELECT * FROM prestamos WHERE id = :id FOR UPDATE');
            $sentencia->execute(['id' => $id]);
            $prestamo = $sentencia->fetch();

            if ($prestamo === false) {
                throw new RuntimeException("No existe el préstamo {$id}.");
            }

            if (!in_array($prestamo['estado'], ['activo', 'vencido'], true)) {
                throw new RuntimeException('Este préstamo ya fue cerrado anteriormente.');
            }

            $real = new DateTimeImmutable(
                $fechaDevolucion !== null && $fechaDevolucion !== '' ? $fechaDevolucion : 'today'
            );

            $db->prepare(
                "UPDATE prestamos
                 SET fecha_devolucion_real = :real, estado = 'devuelto'
                 WHERE id = :id"
            )->execute(['real' => $real->format('Y-m-d'), 'id' => $id]);

            // Se repone el ejemplar sin superar nunca el total registrado.
            $db->prepare(
                'UPDATE libros
                 SET stock_disponible = LEAST(stock_disponible + 1, stock_total)
                 WHERE id = :id'
            )->execute(['id' => $prestamo['id_libro']]);

            $multa = null;

            if (Multa::diasDeMora($prestamo['fecha_devolucion_esperada'], $real) > 0) {
                $multa = (new Multa())->generar($id, $prestamo['fecha_devolucion_esperada'], $real);
            }

            $db->commit();

            return $multa;
        } catch (\Throwable $e) {
            $db->rollBack();

            throw $e;
        }
    }

    /** Siguiente préstamo sin devolver de un usuario, si lo tiene. */
    public function pendienteDe(int $idUsuario): ?array
    {
        $filas = $this->vigentesDe($idUsuario);

        return $filas[0] ?? null;
    }

    // -----------------------------------------------------------------------
    // Apoyo interno
    // -----------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function bloquearUsuario(int $id): array
    {
        $sentencia = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id FOR UPDATE');
        $sentencia->execute(['id' => $id]);
        $usuario = $sentencia->fetch();

        if ($usuario === false) {
            throw new RuntimeException('El usuario seleccionado no existe.');
        }

        if ($usuario['estado'] !== 'activo') {
            throw new RuntimeException('El usuario está bloqueado y no puede recibir préstamos.');
        }

        return $usuario;
    }

    /** @return array<string, mixed> */
    private function bloquearLibro(int $id): array
    {
        $sentencia = $this->db->prepare('SELECT * FROM libros WHERE id = :id FOR UPDATE');
        $sentencia->execute(['id' => $id]);
        $libro = $sentencia->fetch();

        if ($libro === false) {
            throw new RuntimeException('El libro seleccionado no existe.');
        }

        return $libro;
    }
}
