<?php

declare(strict_types=1);

/**
 * Tabla `libros`.
 *
 * Nota: en el esquema original el autor se guarda como texto (`autor`), no como
 * clave foránea, y la tabla tiene una collation distinta al resto
 * (utf8mb4_general_ci); se replican ambas cosas para no desviarse del original.
 */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `libros` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `titulo` varchar(255) NOT NULL,
            `autor` varchar(180) NOT NULL,
            `isbn` varchar(20) DEFAULT NULL,
            `id_categoria` bigint(20) unsigned DEFAULT NULL,
            `editorial` varchar(150) DEFAULT NULL,
            `anio` smallint(5) unsigned DEFAULT NULL,
            `portada_url` varchar(500) DEFAULT NULL,
            `sinopsis` text DEFAULT NULL,
            `stock_total` int(10) unsigned NOT NULL DEFAULT 0,
            `stock_disponible` int(10) unsigned NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_libros_isbn` (`isbn`),
            KEY `idx_libros_titulo` (`titulo`),
            KEY `idx_libros_autor` (`autor`),
            KEY `idx_libros_categoria` (`id_categoria`),
            CONSTRAINT `fk_libros_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        SQL,
    ],

    'down' => [
        'DROP TABLE IF EXISTS `libros`',
    ],
];
