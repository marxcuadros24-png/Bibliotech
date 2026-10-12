<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Categoria extends Model
{
    protected string $table = 'categorias';
    protected string $primaryKey = 'id';

    protected array $fillable = ['nombre', 'descripcion'];

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
