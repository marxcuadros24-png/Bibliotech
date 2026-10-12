<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    protected string $table = 'usuarios';
    protected string $primaryKey = 'id';

    // password_hash queda fuera a propósito: no se asigna desde un formulario.
    protected array $fillable = ['nombre', 'email', 'rol', 'estado'];

    /** Estudiantes, bibliotecarios o administradores con sesión habilitada. */
    public function activos(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre, email, rol, created_at
             FROM usuarios
             WHERE estado = 'activo'
             ORDER BY nombre ASC"
        );

        return $stmt->fetchAll();
    }

    /** Préstamos vigentes de cada usuario (usuarios sin préstamos aparecen en 0). */
    public function conTotalPrestamos(): array
    {
        $sql = "SELECT u.id, u.nombre, u.email, u.rol, u.estado,
                       COUNT(p.id) AS prestamos_activos
                FROM usuarios u
                LEFT JOIN prestamos p ON p.id_usuario = u.id AND p.estado = 'activo'
                GROUP BY u.id, u.nombre, u.email, u.rol, u.estado
                ORDER BY u.nombre ASC";

        return $this->db->query($sql)->fetchAll();
    }
}
