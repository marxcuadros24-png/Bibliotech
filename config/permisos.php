<?php

declare(strict_types=1);

/**
 * Matriz de permisos por rol.
 *
 * Cada rol declara, por módulo, las acciones que puede ejecutar. Los módulos
 * que no aparecen quedan denegados por completo. Tener los permisos en un solo
 * archivo evita que se dispersen por los controladores y hace fácil auditar
 * quién puede hacer qué.
 *
 * Acciones de lectura:
 *   ver          -> listar y ver cualquiera
 *   ver_propios  -> listar y ver únicamente los registros ligados a su cuenta
 *   ver_propias  -> igual que ver_propios, para el módulo de multas
 */
return [
    'administrador' => [
        'libros'     => ['ver', 'crear', 'editar', 'eliminar'],
        'categorias' => ['ver', 'crear', 'editar', 'eliminar'],
        'usuarios'   => ['ver', 'crear', 'editar', 'eliminar'],
        'prestamos'  => ['ver', 'crear', 'devolver'],
        'multas'     => ['ver', 'pagar', 'condonar'],
    ],

    'bibliotecario' => [
        'libros'     => ['ver', 'crear', 'editar', 'eliminar'],
        'categorias' => ['ver', 'crear', 'editar', 'eliminar'],
        'prestamos'  => ['ver', 'crear', 'devolver'],
        'multas'     => ['ver', 'pagar', 'condonar'],
    ],

    // El estudiante solo consulta: el catálogo completo y sus propios registros.
    'estudiante' => [
        'libros'     => ['ver'],
        'categorias' => ['ver'],
        'prestamos'  => ['ver_propios'],
        'multas'     => ['ver_propias'],
    ],
];
