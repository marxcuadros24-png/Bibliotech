<?php

declare(strict_types=1);

/** Tabla `prestamos`: entregas de un ejemplar a un usuario con fecha de devolución. */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `prestamos` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `id_usuario` bigint(20) unsigned NOT NULL,
            `id_libro` bigint(20) unsigned NOT NULL,
            `id_reserva` bigint(20) unsigned DEFAULT NULL,
            `fecha_prestamo` date NOT NULL,
            `fecha_devolucion_esperada` date NOT NULL,
            `fecha_devolucion_real` date DEFAULT NULL,
            `estado` enum('activo','devuelto','vencido','cancelado') NOT NULL DEFAULT 'activo',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_prestamos_usuario` (`id_usuario`),
            KEY `idx_prestamos_libro` (`id_libro`),
            KEY `idx_prestamos_estado` (`estado`),
            KEY `idx_prestamos_reserva` (`id_reserva`),
            CONSTRAINT `fk_prestamos_libro` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`) ON UPDATE CASCADE,
            CONSTRAINT `fk_prestamos_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT `fk_prestamos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],

    'down' => [
        'DROP TABLE IF EXISTS `prestamos`',
    ],
];
