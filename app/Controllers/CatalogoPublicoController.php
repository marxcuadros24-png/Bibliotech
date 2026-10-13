<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Libro;

/**
 * Catálogo público de libros.
 *
 * No lleva filtro de sesión: cualquier visitante (anónimo o autenticado) puede
 * consultar el inventario. Solo se exponen datos del libro; no hay enlaces a la
 * ficha interna ni información de usuarios o préstamos, que sí requieren sesión.
 */
class CatalogoPublicoController extends Controller
{
    // GET /catalogo
    public function index(): void
    {
        $busqueda = $this->request->string('buscar');

        $this->view('catalogo_publico', [
            'title'    => 'Catálogo de libros',
            'libros'   => (new Libro())->conCategoria($busqueda),
            'busqueda' => $busqueda,
        ], 'publico');
    }
}
