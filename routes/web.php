<?php

declare(strict_types=1);

/**
 * Rutas web.
 *
 * Este archivo lo incluye App::run(), por lo que la variable $router ya está
 * disponible. Las rutas se evalúan en orden y aceptan parámetros {nombre}.
 *
 * El tercer argumento son los filtros que corren antes del controlador
 * (ver App\Core\Middleware):
 *   'auth'                   exige sesión iniciada
 *   'invitado'               exige no tener sesión
 *   'rol:administrador'      exige uno de los roles indicados
 *   'permiso:modulo,accion'  consulta config/permisos.php
 *
 * Ojo con el orden: las rutas literales (/libros/crear) deben ir antes que las
 * que capturan un parámetro (/libros/{id}), o "crear" se interpretaría como id.
 *
 * @var App\Core\Router $router
 */

// ---------------------------------------------------------------------------
// Autenticación
// ---------------------------------------------------------------------------
$router->get('/login', 'AuthController@mostrarLogin', ['invitado']);
$router->post('/login', 'AuthController@login', ['invitado']);
$router->post('/logout', 'AuthController@logout', ['auth']);

// ---------------------------------------------------------------------------
// Panel
// ---------------------------------------------------------------------------
$router->get('/', 'DashboardController@index', ['auth']);

// ---------------------------------------------------------------------------
// Libros: el catálogo lo ve cualquier rol autenticado, el CRUD solo el personal
// ---------------------------------------------------------------------------
$router->get('/libros', 'LibroController@index', ['permiso:libros,ver']);
$router->get('/libros/crear', 'LibroController@create', ['permiso:libros,crear']);
$router->post('/libros', 'LibroController@store', ['permiso:libros,crear']);
$router->get('/libros/{id}', 'LibroController@show', ['permiso:libros,ver']);
$router->get('/libros/{id}/editar', 'LibroController@edit', ['permiso:libros,editar']);
$router->post('/libros/{id}', 'LibroController@update', ['permiso:libros,editar']);
$router->post('/libros/{id}/eliminar', 'LibroController@destroy', ['permiso:libros,eliminar']);

// ---------------------------------------------------------------------------
// Categorías
// ---------------------------------------------------------------------------
$router->get('/categorias', 'CategoriaController@index', ['permiso:categorias,ver']);
$router->get('/categorias/crear', 'CategoriaController@create', ['permiso:categorias,crear']);
$router->post('/categorias', 'CategoriaController@store', ['permiso:categorias,crear']);
$router->get('/categorias/{id}/editar', 'CategoriaController@edit', ['permiso:categorias,editar']);
$router->post('/categorias/{id}', 'CategoriaController@update', ['permiso:categorias,editar']);
$router->post('/categorias/{id}/eliminar', 'CategoriaController@destroy', ['permiso:categorias,eliminar']);

// ---------------------------------------------------------------------------
// Usuarios: solo el administrador administra cuentas
// ---------------------------------------------------------------------------
$router->get('/usuarios', 'UsuarioController@index', ['permiso:usuarios,ver']);
$router->get('/usuarios/crear', 'UsuarioController@create', ['permiso:usuarios,crear']);
$router->post('/usuarios', 'UsuarioController@store', ['permiso:usuarios,crear']);
$router->get('/usuarios/{id}/editar', 'UsuarioController@edit', ['permiso:usuarios,editar']);
$router->post('/usuarios/{id}', 'UsuarioController@update', ['permiso:usuarios,editar']);
$router->post('/usuarios/{id}/eliminar', 'UsuarioController@destroy', ['permiso:usuarios,eliminar']);

// ---------------------------------------------------------------------------
// Préstamos
//
// El listado y el detalle solo piden sesión: el controlador decide si muestra
// todo (personal) o únicamente los del usuario (estudiante). Registrar y
// devolver sí están restringidos al personal.
// ---------------------------------------------------------------------------
$router->get('/prestamos', 'PrestamoController@index', ['auth']);
$router->get('/prestamos/crear', 'PrestamoController@create', ['permiso:prestamos,crear']);
$router->post('/prestamos', 'PrestamoController@store', ['permiso:prestamos,crear']);
$router->get('/prestamos/{id}', 'PrestamoController@show', ['auth']);
$router->post('/prestamos/{id}/devolver', 'PrestamoController@devolver', ['permiso:prestamos,devolver']);

// ---------------------------------------------------------------------------
// Multas
// ---------------------------------------------------------------------------
$router->get('/multas', 'MultaController@index', ['auth']);
$router->post('/multas/{id}/pagar', 'MultaController@pagar', ['permiso:multas,pagar']);
$router->post('/multas/{id}/condonar', 'MultaController@condonar', ['permiso:multas,condonar']);

// ---------------------------------------------------------------------------
// Endpoint JSON de ejemplo (requiere sesión para no exponer el catálogo)
// ---------------------------------------------------------------------------
$router->get('/api/libros', 'LibroController@apiIndex', ['permiso:libros,ver']);
