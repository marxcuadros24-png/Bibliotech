<?php

declare(strict_types=1);

/**
 * Tabla `categorias`.
 *
 * Réplica de la estructura existente en db_libros. Se usa CREATE TABLE IF NOT
 * EXISTS para que la migración sea segura sobre una base de datos ya poblada.
 */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `categorias` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `descripcion` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_categorias_nombre` (`nombre`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],

    'down' => [
        'DROP TABLE IF EXISTS `categorias`',
    ],
];
