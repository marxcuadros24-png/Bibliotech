<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Motor de migraciones.
 *
 * Cada migración es un archivo PHP en database/migrations que devuelve un
 * arreglo con las sentencias SQL de ida y de vuelta:
 *
 *   return [
 *       'up'   => ['CREATE TABLE ...'],
 *       'down' => ['DROP TABLE IF EXISTS ...'],
 *   ];
 *
 * El nombre del archivo (sin .php) es el identificador de la migración y se
 * guarda en la tabla `migraciones`, junto con el lote en el que se ejecutó.
 */
class Migrator
{
    private const REPOSITORY = 'migraciones';

    private string $directory;

    public function __construct(
        private readonly PDO $db,
        ?string $directory = null,
    ) {
        $this->directory = $directory ?? BASE_PATH . '/database/migrations';
    }

    public function ensureRepository(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS `' . self::REPOSITORY . '` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `migracion` varchar(191) NOT NULL,
                `lote` int(10) unsigned NOT NULL,
                `ejecutada_en` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_migraciones_migracion` (`migracion`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * Archivos de migración ordenados alfabéticamente (el prefijo de fecha los
     * mantiene en orden cronológico).
     *
     * @return list<array{nombre:string, ruta:string}>
     */
    public function archivos(): array
    {
        $rutas = glob($this->directory . '/*.php') ?: [];
        sort($rutas, SORT_STRING);

        return array_map(
            static fn (string $ruta): array => [
                'nombre' => basename($ruta, '.php'),
                'ruta'   => $ruta,
            ],
            $rutas,
        );
    }

    /** @return list<string> */
    public function ejecutadas(): array
    {
        $this->ensureRepository();

        return $this->db
            ->query('SELECT `migracion` FROM `' . self::REPOSITORY . '` ORDER BY `migracion`')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<array{nombre:string, ruta:string}> */
    public function pendientes(): array
    {
        $ejecutadas = $this->ejecutadas();

        return array_values(array_filter(
            $this->archivos(),
            static fn (array $archivo): bool => !in_array($archivo['nombre'], $ejecutadas, true),
        ));
    }

    /**
     * Ejecuta todas las migraciones pendientes en un nuevo lote.
     *
     * @return list<string> Nombres de las migraciones aplicadas.
     */
    public function migrate(): array
    {
        $this->ensureRepository();
        $pendientes = $this->pendientes();

        if ($pendientes === []) {
            return [];
        }

        $lote = $this->ultimoLote() + 1;
        $aplicadas = [];

        foreach ($pendientes as $migracion) {
            $this->ejecutar($migracion, 'up');
            $this->registrar($migracion['nombre'], $lote);
            $aplicadas[] = $migracion['nombre'];
        }

        return $aplicadas;
    }

    /**
     * Revierte el último lote aplicado, en orden inverso.
     *
     * @return list<string> Nombres de las migraciones revertidas.
     */
    public function rollback(): array
    {
        $this->ensureRepository();

        $lote = (int) $this->db
            ->query('SELECT COALESCE(MAX(`lote`), 0) FROM `' . self::REPOSITORY . '` WHERE `lote` >= 1')
            ->fetchColumn();

        if ($lote === 0) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT `migracion` FROM `' . self::REPOSITORY . '` WHERE `lote` = :lote ORDER BY `migracion` DESC'
        );
        $stmt->execute(['lote' => $lote]);
        $nombres = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $indice = [];
        foreach ($this->archivos() as $archivo) {
            $indice[$archivo['nombre']] = $archivo;
        }

        $revertidas = [];

        foreach ($nombres as $nombre) {
            if (!isset($indice[$nombre])) {
                throw new RuntimeException("Falta el archivo de la migración {$nombre}; no se puede revertir.");
            }

            $this->ejecutar($indice[$nombre], 'down');
            $this->db
                ->prepare('DELETE FROM `' . self::REPOSITORY . '` WHERE `migracion` = :migracion')
                ->execute(['migracion' => $nombre]);

            $revertidas[] = $nombre;
        }

        return $revertidas;
    }

    /**
     * Marca todas las migraciones como aplicadas SIN ejecutarlas.
     *
     * Se usa cuando la base de datos ya existe (por ejemplo, un dump previo) y
     * solo hay que poner al día el registro de migraciones. Se guardan en el
     * lote 0 para que rollback() nunca las deshaga.
     *
     * @return list<string> Nombres marcados como aplicados.
     */
    public function baseline(): array
    {
        $this->ensureRepository();

        $marcadas = [];

        foreach ($this->pendientes() as $migracion) {
            $this->registrar($migracion['nombre'], 0);
            $marcadas[] = $migracion['nombre'];
        }

        return $marcadas;
    }

    /**
     * Estado de cada migración.
     *
     * @return list<array{nombre:string, aplicada:bool, lote:int|null}>
     */
    public function status(): array
    {
        $this->ensureRepository();

        $stmt = $this->db->query('SELECT `migracion`, `lote` FROM `' . self::REPOSITORY . '`');
        $registro = [];

        foreach ($stmt->fetchAll() as $fila) {
            $registro[$fila['migracion']] = (int) $fila['lote'];
        }

        return array_map(
            static fn (array $archivo): array => [
                'nombre'  => $archivo['nombre'],
                'aplicada' => array_key_exists($archivo['nombre'], $registro),
                'lote'    => $registro[$archivo['nombre']] ?? null,
            ],
            $this->archivos(),
        );
    }

    private function ultimoLote(): int
    {
        return (int) $this->db
            ->query('SELECT COALESCE(MAX(`lote`), 0) FROM `' . self::REPOSITORY . '`')
            ->fetchColumn();
    }

    private function registrar(string $nombre, int $lote): void
    {
        $this->db
            ->prepare('INSERT INTO `' . self::REPOSITORY . '` (`migracion`, `lote`) VALUES (:migracion, :lote)')
            ->execute(['migracion' => $nombre, 'lote' => $lote]);
    }

    /** Ejecuta las sentencias de una migración en la dirección indicada. */
    private function ejecutar(array $migracion, string $direccion): void
    {
        $definicion = require $migracion['ruta'];

        if (!is_array($definicion) || !array_key_exists($direccion, $definicion)) {
            throw new RuntimeException(
                "La migración {$migracion['nombre']} no define la sección '{$direccion}'."
            );
        }

        $sentencias = is_array($definicion[$direccion])
            ? $definicion[$direccion]
            : [$definicion[$direccion]];

        foreach ($sentencias as $sentencia) {
            if (!is_string($sentencia) || trim($sentencia) === '') {
                continue;
            }

            $this->db->exec($sentencia);
        }
    }
}
