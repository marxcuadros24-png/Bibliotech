<?php

declare(strict_types=1);

/** Tabla `multas`: cargos generados por devoluciones tardías o daños. */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `multas` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `id_prestamo` bigint(20) unsigned NOT NULL,
            `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
            `estado` enum('pendiente','pagada','condonada') NOT NULL DEFAULT 'pendiente',
            `fecha_generacion` datetime NOT NULL DEFAULT current_timestamp(),
            `fecha_pago` datetime DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_multas_prestamo` (`id_prestamo`),
            KEY `idx_multas_estado` (`estado`),
            CONSTRAINT `fk_multas_prestamo` FOREIGN KEY (`id_prestamo`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],

    'down' => [
        'DROP TABLE IF EXISTS `multas`',
    ],
];
