<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    public const ROLES = ['administrador', 'bibliotecario', 'estudiante'];

    protected string $table = 'usuarios';
    protected string $primaryKey = 'id';

    // password_hash queda fuera: nunca se asigna en masa desde un formulario.
    protected array $fillable = ['nombre', 'email', 'rol', 'estado'];

    /** @return array<string, mixed>|null */
    public function porEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    public function emailDuplicado(string $email, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $parametros = ['email' => $email];

        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @param array<string, mixed> $datos */
    public function crear(array $datos, string $password): int
    {
        $datos['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        $columnas = array_keys($datos);
        $sql = sprintf(
            'INSERT INTO usuarios (%s) VALUES (%s)',
            implode(', ', $columnas),
            ':' . implode(', :', $columnas),
        );

        $this->db->prepare($sql)->execute($datos);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza el usuario. Si $password viene con valor, también la contraseña.
     *
     * @param array<string, mixed> $datos
     */
    public function actualizar(int $id, array $datos, ?string $password = null): bool
    {
        if ($password !== null && $password !== '') {
            $datos['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($datos === []) {
            return false;
        }

        $asignaciones = implode(', ', array_map(
            static fn (string $columna): string => "{$columna} = :{$columna}",
            array_keys($datos),
        ));

        $datos['__id'] = $id;

        return $this->db->prepare("UPDATE usuarios SET {$asignaciones} WHERE id = :__id")->execute($datos);
    }

    public function cambiarPassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET password_hash = :hash WHERE id = :id'
        );

        return $stmt->execute([
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'id'   => $id,
        ]);
    }

    /** Cantidad de usuarios por rol, para el panel. */
    public function contarPorRol(): array
    {
        $filas = $this->db->query(
            'SELECT rol, COUNT(*) AS total FROM usuarios GROUP BY rol'
        )->fetchAll();

        $conteo = array_fill_keys(self::ROLES, 0);

        foreach ($filas as $fila) {
            $conteo[(string) $fila['rol']] = (int) $fila['total'];
        }

        return $conteo;
    }

    /** Estudiantes, bibliotecarios o administradores con la cuenta habilitada. */
    public function activos(): array
    {
        return $this->db->query(
            "SELECT id, nombre, email, rol, created_at
             FROM usuarios
             WHERE estado = 'activo'
             ORDER BY nombre ASC"
        )->fetchAll();
    }

    /** Usuarios con su cantidad de préstamos vigentes. */
    public function conTotalPrestamos(): array
    {
        return $this->db->query(
            "SELECT u.id, u.nombre, u.email, u.rol, u.estado, u.created_at,
                    COUNT(p.id) AS prestamos_activos
             FROM usuarios u
             LEFT JOIN prestamos p ON p.id_usuario = u.id AND p.estado IN ('activo', 'vencido')
             GROUP BY u.id, u.nombre, u.email, u.rol, u.estado, u.created_at
             ORDER BY u.nombre ASC"
        )->fetchAll();
    }

    /** Cuántos préstamos tiene el usuario (para saber si se puede eliminar). */
    public function totalPrestamos(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM prestamos WHERE id_usuario = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Administradores activos, opcionalmente ignorando a uno.
     *
     * Sirve para impedir que el sistema se quede sin nadie que pueda gestionar
     * cuentas: ni borrando, ni cambiando de rol, ni bloqueando al último admin.
     */
    public function contarAdministradoresActivos(?int $exceptoId = null): int
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'";
        $parametros = [];

        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn();
    }

    /** Bloquea el acceso de un usuario sin borrar su historial. */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET estado = :estado WHERE id = :id');

        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }
}
