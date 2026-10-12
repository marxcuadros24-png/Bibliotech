<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use App\Core\Model;
use DateTimeImmutable;
use RuntimeException;

/**
 * Modelo de multas.
 *
 * El monto no se escribe a mano: se calcula como días de mora por la tarifa
 * diaria vigente, y ambos valores quedan guardados para poder auditar el cargo
 * aunque la tarifa cambie después.
 */
class Multa extends Model
{
    public const ESTADOS = ['pendiente', 'pagada', 'condonada'];

    protected string $table = 'multas';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_prestamo',
        'monto',
        'dias_mora',
        'tarifa_diaria',
        'estado',
        'fecha_generacion',
        'fecha_pago',
    ];

    // -----------------------------------------------------------------------
    // Cálculo
    // -----------------------------------------------------------------------

    /** Tarifa diaria configurada en el .env (MULTA_POR_DIA). */
    public static function tarifaDiaria(): float
    {
        return (float) App::config('biblioteca.multa_por_dia', 1.50);
    }

    /** Días transcurridos entre la fecha esperada y la real (0 si fue puntual). */
    public static function diasDeMora(string $esperada, DateTimeImmutable $real): int
    {
        $limite = new DateTimeImmutable($esperada);

        if ($real <= $limite) {
            return 0;
        }

        return (int) $limite->diff($real)->days;
    }

    /** Monto que corresponde a una cantidad de días de mora. */
    public static function calcularMonto(int $dias): float
    {
        return round(max(0, $dias) * self::tarifaDiaria(), 2);
    }

    /**
     * Crea la multa de un préstamo devuelto con atraso.
     *
     * No abre transacción propia: se invoca desde Prestamo::devolver(), que ya
     * está dentro de una.
     *
     * @return array{monto: float, dias: int}
     */
    public function generar(int $idPrestamo, string $fechaEsperada, DateTimeImmutable $fechaReal): array
    {
        $dias = self::diasDeMora($fechaEsperada, $fechaReal);

        if ($dias === 0) {
            throw new RuntimeException('El préstamo se devolvió a tiempo: no corresponde multa.');
        }

        $tarifa = self::tarifaDiaria();
        $monto = round($dias * $tarifa, 2);

        $sentencia = $this->db->prepare(
            "INSERT INTO multas (id_prestamo, monto, dias_mora, tarifa_diaria, estado, fecha_generacion)
             VALUES (:prestamo, :monto, :dias, :tarifa, 'pendiente', NOW())"
        );

        $sentencia->execute([
            'prestamo' => $idPrestamo,
            'monto'    => $monto,
            'dias'     => $dias,
            'tarifa'   => $tarifa,
        ]);

        return ['monto' => $monto, 'dias' => $dias];
    }

    // -----------------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------------

    /**
     * Multas con los datos del préstamo, el usuario y el libro.
     *
     * @return list<array<string, mixed>>
     */
    public function listado(?string $estado = null, ?int $idUsuario = null, ?string $busqueda = null): array
    {
        $sql = "SELECT m.*, p.id_usuario, p.id_libro, p.fecha_prestamo,
                       p.fecha_devolucion_esperada, p.fecha_devolucion_real,
                       u.nombre AS usuario, u.email AS usuario_email,
                       l.titulo AS libro, l.autor AS libro_autor
                FROM multas m
                JOIN prestamos p ON p.id = m.id_prestamo
                JOIN usuarios u  ON u.id = p.id_usuario
                JOIN libros l    ON l.id = p.id_libro";

        $condiciones = [];
        $parametros = [];

        if ($estado !== null && $estado !== '') {
            $condiciones[] = 'm.estado = :estado';
            $parametros['estado'] = $estado;
        }

        if ($idUsuario !== null) {
            $condiciones[] = 'p.id_usuario = :id_usuario';
            $parametros['id_usuario'] = $idUsuario;
        }

        if ($busqueda !== null && $busqueda !== '') {
            $condiciones[] = '(u.nombre LIKE :b_usuario OR u.email LIKE :b_email
                               OR l.titulo LIKE :b_titulo)';

            $patron = '%' . $busqueda . '%';
            $parametros += [
                'b_usuario' => $patron,
                'b_email'   => $patron,
                'b_titulo'  => $patron,
            ];
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY FIELD(m.estado, 'pendiente', 'pagada', 'condonada'),
                         m.fecha_generacion DESC";

        $sentencia = $this->db->prepare($sql);
        $sentencia->execute($parametros);

        return $sentencia->fetchAll();
    }

    /** Multas vigentes de un usuario (solo las que todavía debe pagar). */
    public function pendientesDe(int $idUsuario): array
    {
        return $this->listado('pendiente', $idUsuario);
    }

    /** @return array<string, mixed>|null */
    public function detalle(int $id): ?array
    {
        $sentencia = $this->db->prepare(
            'SELECT m.*, p.id_usuario, p.fecha_devolucion_esperada, p.fecha_devolucion_real,
                    u.nombre AS usuario, l.titulo AS libro
             FROM multas m
             JOIN prestamos p ON p.id = m.id_prestamo
             JOIN usuarios u  ON u.id = p.id_usuario
             JOIN libros l    ON l.id = p.id_libro
             WHERE m.id = :id
             LIMIT 1'
        );
        $sentencia->execute(['id' => $id]);
        $fila = $sentencia->fetch();

        return $fila === false ? null : $fila;
    }

    /** Importes por estado y cuántas quedan por cobrar. */
    public function resumen(): array
    {
        $filas = $this->db->query(
            'SELECT estado, COUNT(*) AS cantidad, COALESCE(SUM(monto), 0) AS total
             FROM multas GROUP BY estado'
        )->fetchAll();

        $resumen = [
            'pendiente' => ['cantidad' => 0, 'total' => 0.0],
            'pagada'    => ['cantidad' => 0, 'total' => 0.0],
            'condonada' => ['cantidad' => 0, 'total' => 0.0],
        ];

        foreach ($filas as $fila) {
            $resumen[(string) $fila['estado']] = [
                'cantidad' => (int) $fila['cantidad'],
                'total'    => (float) $fila['total'],
            ];
        }

        return $resumen;
    }

    /** Suma de todo lo que un usuario todavía debe. */
    public function totalPendienteDe(int $idUsuario): float
    {
        $sentencia = $this->db->prepare(
            "SELECT COALESCE(SUM(m.monto), 0)
             FROM multas m
             JOIN prestamos p ON p.id = m.id_prestamo
             WHERE p.id_usuario = :id AND m.estado = 'pendiente'"
        );
        $sentencia->execute(['id' => $idUsuario]);

        return (float) $sentencia->fetchColumn();
    }

    // -----------------------------------------------------------------------
    // Cambios de estado
    // -----------------------------------------------------------------------

    public function pagar(int $id): bool
    {
        return $this->cerrar($id, 'pagada');
    }

    public function condonar(int $id): bool
    {
        return $this->cerrar($id, 'condonada');
    }

    /**
     * @throws RuntimeException si la multa ya estaba cerrada.
     */
    private function cerrar(int $id, string $estadoDestino): bool
    {
        $sentencia = $this->db->prepare('SELECT * FROM multas WHERE id = :id FOR UPDATE');
        $sentencia->execute(['id' => $id]);
        $multa = $sentencia->fetch();

        if ($multa === false) {
            throw new RuntimeException("No existe la multa {$id}.");
        }

        if ($multa['estado'] !== 'pendiente') {
            throw new RuntimeException(
                'Esta multa ya fue ' . ($multa['estado'] === 'pagada' ? 'pagada' : 'condonada') . '.'
            );
        }

        $sentencia = $this->db->prepare(
            'UPDATE multas
             SET estado = :estado, fecha_pago = NOW()
             WHERE id = :id AND estado = :estado_origen'
        );

        return $sentencia->execute([
            'estado'        => $estadoDestino,
            'id'            => $id,
            'estado_origen' => 'pendiente',
        ]);
    }
}
