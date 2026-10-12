<?php

declare(strict_types=1);

return [
    // Nombre que se muestra en el layout y en los títulos.
    'name'     => env('APP_NAME', 'Bibliotech'),

    // Entorno de ejecución: local, testing o production.
    'env'      => env('APP_ENV', 'local'),

    // En true muestra los errores en pantalla (útil en desarrollo).
    'debug'    => (bool) env('APP_DEBUG', true),

    // URL base del proyecto.
    'url'      => env('APP_URL', 'http://localhost:8000'),

    // Zona horaria usada por date() y por las marcas de tiempo.
    'timezone' => env('APP_TIMEZONE', 'America/Lima'),
];
