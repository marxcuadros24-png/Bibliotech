<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class Usuario extends Model
{
    public const ROLES = ['administrador', 'bibliotecario', 'estudiante'];

    /**
     * Rol que reciben las cuentas creadas desde el formulario público.
     *
     * Es una constante y no un valor de configuración a propósito: si el rol
     * saliera del formulario, cualquiera podría registrarse como administrador.
     */
    public const ROL_PUBLICO = 'estudiante';

    /**
     * Hash de descarte para cuando el correo no existe.
     *
     * password_verify() se ejecuta igual, así el tiempo de respuesta no delata
     * qué correos están registrados.
     */
    private const HASH_SENUELO = '$2y$10$usuarioinexistentesoloParagastartiempodeVerificacion00';

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
     * Registra una cuenta desde el formulario público.
     *
     * A diferencia de crear(), aquí el rol NO se toma de $datos: se fuerza al
     * rol público. Nombre y correo son los únicos campos que el visitante elige.
     *
     * @param array{nombre?: string, email?: string} $datos
     *
     * @throws RuntimeException si el correo ya está tomado.
     */
    public function registrar(array $datos, string $password): int
    {
        $email = mb_strtolower(trim((string) ($datos['email'] ?? '')));

        if ($this->emailDuplicado($email)) {
            throw new RuntimeException('Ya existe una cuenta con ese correo.');
        }

        return $this->crear([
            'nombre' => trim((string) ($datos['nombre'] ?? '')),
            'email'  => $email,
            'rol'    => self::ROL_PUBLICO,
            'estado' => 'activo',
        ], $password);
    }

    /**
     * Comprueba unas credenciales y devuelve el usuario si son correctas.
     *
     * Devuelve null tanto si la contraseña no coincide como si la cuenta no
     * existe o está bloqueada: quien llama no debe poder distinguir esos casos.
     *
     * @return array<string, mixed>|null
     */
    public function validarLogin(string $email, string $password): ?array
    {
        $usuario = $this->porEmail(mb_strtolower(trim($email)));

        $hash = is_array($usuario) ? (string) $usuario['password_hash'] : self::HASH_SENUELO;

        if (!password_verify($password, $hash)) {
            return null;
        }

        if ($usuario === null || $usuario['estado'] !== 'activo') {
            return null;
        }

        return $usuario;
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
