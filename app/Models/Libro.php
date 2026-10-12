<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Modelo del catálogo de libros.
 *
 * Refleja la tabla `libros` de db_libros: el autor es una columna de texto
 * (no una tabla aparte) y el inventario se controla con stock_total y
 * stock_disponible.
 */
class Libro extends Model
{
    protected string $table = 'libros';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'titulo',
        'autor',
        'isbn',
        'id_categoria',
        'editorial',
        'anio',
        'portada_url',
        'sinopsis',
        'stock_total',
        'stock_disponible',
    ];

    /**
     * Catálogo con el nombre de la categoría resuelto.
     *
     * @return list<array<string, mixed>>
     */
    public function conCategoria(?string $busqueda = null): array
    {
        $sql = 'SELECT l.id, l.titulo, l.autor, l.isbn, l.editorial, l.anio,
                       l.stock_total, l.stock_disponible, l.id_categoria,
                       c.nombre AS categoria
                FROM libros l
                LEFT JOIN categorias c ON c.id = l.id_categoria';

        $parametros = [];

        if ($busqueda !== null && $busqueda !== '') {
            // MySQL no admite reutilizar un mismo placeholder con prepares
            // nativos, por eso cada columna usa su propio parámetro.
            $sql .= ' WHERE l.titulo LIKE :busqueda_titulo
                         OR l.autor LIKE :busqueda_autor
                         OR l.isbn LIKE :busqueda_isbn
                         OR l.editorial LIKE :busqueda_editorial
                         OR c.nombre LIKE :busqueda_categoria';

            $patron = '%' . $busqueda . '%';

            $parametros = [
                'busqueda_titulo'    => $patron,
                'busqueda_autor'     => $patron,
                'busqueda_isbn'      => $patron,
                'busqueda_editorial' => $patron,
                'busqueda_categoria' => $patron,
            ];
        }

        $sql .= ' ORDER BY l.titulo ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function encontrarConCategoria(int $id): ?array
    {
        $sql = 'SELECT l.*, c.nombre AS categoria, c.descripcion AS categoria_descripcion
                FROM libros l
                LEFT JOIN categorias c ON c.id = l.id_categoria
                WHERE l.id = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /** Suma de ejemplares registrados en todo el catálogo. */
    public function totalEjemplares(): int
    {
        return (int) $this->db->query('SELECT COALESCE(SUM(stock_total), 0) FROM libros')->fetchColumn();
    }

    /** Suma de ejemplares actualmente en estantería. */
    public function totalDisponibles(): int
    {
        return (int) $this->db->query('SELECT COALESCE(SUM(stock_disponible), 0) FROM libros')->fetchColumn();
    }

    /**
     * ¿Otro libro ya usa este ISBN? (la columna es UNIQUE en la tabla)
     */
    public function isbnDuplicado(string $isbn, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM libros WHERE isbn = :isbn';
        $parametros = ['isbn' => $isbn];

        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $exceptoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }
}
