<?php

declare(strict_types=1);

/**
 * Rutas web.
 *
 * Este archivo lo incluye App::run(), por lo que la variable $router ya está
 * disponible. Las rutas se evalúan en orden y aceptan parámetros {nombre}.
 *
 * @var App\Core\Router $router
 */

// ---------------------------------------------------------------------------
// Inicio
// ---------------------------------------------------------------------------
$router->get('/', 'HomeController@index');

// ---------------------------------------------------------------------------
// Catálogo de libros (CRUD de ejemplo)
// ---------------------------------------------------------------------------
$router->get('/libros', 'LibroController@index');
$router->get('/libros/crear', 'LibroController@create');
$router->post('/libros', 'LibroController@store');
$router->get('/libros/{id}', 'LibroController@show');
$router->get('/libros/{id}/editar', 'LibroController@edit');
$router->post('/libros/{id}', 'LibroController@update');
$router->post('/libros/{id}/eliminar', 'LibroController@destroy');

// ---------------------------------------------------------------------------
// Rutas de ejemplo para endpoints JSON (opcional, para prácticas de AJAX)
// ---------------------------------------------------------------------------
$router->get('/api/libros', 'LibroController@apiIndex');
