<?php

declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP.
 *
 * Uso:
 *   php -S localhost:8000 server.php
 *
 * Sirve los archivos estáticos de public/ y envía el resto al front controller,
 * replicando el comportamiento del .htaccess de Apache en desarrollo.
 *
 * Nota: no se usa "return false" porque el servidor embebido resolvería el
 * archivo contra el directorio de trabajo y no contra public/.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';

$public = realpath(__DIR__ . '/public');

if ($public !== false && $path !== '/') {
    $candidate = realpath($public . '/' . ltrim($path, '/'));

    // realpath() ya resuelve "..", así que basta con confirmar que el archivo
    // siga estando dentro de public/.
    if ($candidate !== false && is_file($candidate) && str_starts_with($candidate, $public)) {
        $types = [
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'text/javascript; charset=utf-8',
            'json'  => 'application/json; charset=utf-8',
            'svg'   => 'image/svg+xml',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'webp'  => 'image/webp',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'txt'   => 'text/plain; charset=utf-8',
        ];

        $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));

        header('Content-Type: ' . ($types[$extension] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($candidate));

        readfile($candidate);

        return;
    }
}

require __DIR__ . '/public/index.php';
