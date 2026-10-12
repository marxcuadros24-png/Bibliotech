<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Modelo base.
 *
 * Cada modelo concreto solo declara su tabla y hereda el CRUD básico.
 * Todas las consultas usan sentencias preparadas para evitar inyección SQL.
 */
abstract class Model
{
    protected PDO $db;

    /** Nombre de la tabla en la base de datos. */
    protected string $table = '';

    /** Columna usada como clave primaria. */
    protected string $primaryKey = 'id';

    /** Campos que se pueden asignar masivamente desde un formulario. */
    protected array $fillable = [];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return list<array<string, mixed>> */
    public function all(?string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($orderBy !== null && $orderBy !== '') {
            $sql .= ' ORDER BY ' . $this->sanitizeColumnList($orderBy);
        }

        return $this->db->query($sql)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $data = $this->filterFillable($data);

        if ($data === []) {
            throw new \InvalidArgumentException('No hay datos válidos para insertar.');
        }

        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            ':' . implode(', :', $columns),
        );

        $this->db->prepare($sql)->execute($data);

        return (int) $this->db->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);

        if ($data === []) {
            return false;
        }

        $assignments = implode(', ', array_map(
            static fn (string $column): string => "{$column} = :{$column}",
            array_keys($data),
        ));

        $data['__id'] = $id;
        $sql = "UPDATE {$this->table} SET {$assignments} WHERE {$this->primaryKey} = :__id";

        return $this->db->prepare($sql)->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id"
        );

        return $stmt->execute(['id' => $id]);
    }

    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    /**
     * Deja únicamente las claves declaradas en $fillable.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Permite "ORDER BY titulo, anio" sin abrir la puerta a inyección SQL:
     * solo se aceptan nombres de columna y la palabra ASC/DESC.
     */
    protected function sanitizeColumnList(string $columns): string
    {
        $parts = array_map('trim', explode(',', $columns));
        $safe = [];

        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\s+(?:ASC|DESC))?$/i', $part)) {
                continue;
            }

            $safe[] = $part;
        }

        return $safe === [] ? $this->primaryKey : implode(', ', $safe);
    }
}
