<?php

declare(strict_types=1);

/**
 * Front controller de Bibliotech.
 *
 * Todas las peticiones (salvo los recursos estáticos) pasan por aquí:
 * se inicializa el framework y se despacha la ruta solicitada.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

App\Core\App::run();
