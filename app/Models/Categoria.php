<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Categoria extends Model
{
    protected string $table = 'categorias';
    protected string $primaryKey = 'id';

    protected array $fillable = ['nombre', 'descripcion'];

    /** El nombre es UNIQUE en la tabla, así que hay que comprobarlo antes. */
    public function nombreDuplicado(string $nombre, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categorias WHERE nombre = :nombre';
        $parametros = ['nombre' => $nombre];

        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Cuántos libros quedarían sin categoría si se borra esta. */
    public function totalLibros(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM libros WHERE id_categoria = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    /** Categorías con la cantidad de libros asociados. */
    public function conTotalLibros(): array
    {
        $sql = 'SELECT c.id, c.nombre, c.descripcion, COUNT(l.id) AS total_libros
                FROM categorias c
                LEFT JOIN libros l ON l.id_categoria = c.id
                GROUP BY c.id, c.nombre, c.descripcion
                ORDER BY c.nombre ASC';

        return $this->db->query($sql)->fetchAll();
    }
}
