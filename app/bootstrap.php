<?php

declare(strict_types=1);

/**
 * Bootstrap de Bibliotech.
 *
 * Único punto de inicialización: cualquier script (front controller, tareas
 * de consola, pruebas) puede incluirlo para tener el framework disponible.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/app/Core/Autoloader.php';

App\Core\Autoloader::register(BASE_PATH . '/app', 'App\\');

require BASE_PATH . '/app/helpers.php';

App\Core\App::boot();
