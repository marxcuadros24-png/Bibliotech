<?php

declare(strict_types=1);

/** Tabla `usuarios`: estudiantes, bibliotecarios y administradores. */
return [
    'up' => [
        <<<'SQL'
        CREATE TABLE IF NOT EXISTS `usuarios` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `nombre` varchar(120) NOT NULL,
            `email` varchar(180) NOT NULL,
            `password_hash` varchar(255) NOT NULL,
            `rol` enum('estudiante','bibliotecario','administrador') NOT NULL DEFAULT 'estudiante',
            `estado` enum('activo','bloqueado') NOT NULL DEFAULT 'activo',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_usuarios_email` (`email`),
            KEY `idx_usuarios_rol` (`rol`),
            KEY `idx_usuarios_estado` (`estado`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ],

    'down' => [
        'DROP TABLE IF EXISTS `usuarios`',
    ],
];
