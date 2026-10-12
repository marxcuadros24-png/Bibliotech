<?php

declare(strict_types=1);

/** Tabla `reservas`: apartados temporales de un libro por parte de un usuario. */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `reservas` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `id_usuario` bigint(20) unsigned NOT NULL,
            `id_libro` bigint(20) unsigned NOT NULL,
            `fecha_reserva` datetime NOT NULL DEFAULT current_timestamp(),
            `fecha_expiracion` datetime NOT NULL,
            `estado` enum('pendiente','confirmada','expirada','cancelada') NOT NULL DEFAULT 'pendiente',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_reservas_usuario` (`id_usuario`),
            KEY `idx_reservas_libro` (`id_libro`),
            KEY `idx_reservas_estado` (`estado`),
            CONSTRAINT `fk_reservas_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_reservas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],

    'down' => [
        'DROP TABLE IF EXISTS `reservas`',
    ],
];
