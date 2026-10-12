<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Libro;
use App\Models\Usuario;

class HomeController extends Controller
{
    public function index(): void
    {
        $libros = new Libro();

        $this->view('home.index', [
            'title'            => 'Inicio',
            'totalLibros'      => $libros->count(),
            'totalEjemplares'  => $libros->totalEjemplares(),
            'totalDisponibles' => $libros->totalDisponibles(),
            'totalCategorias'  => (new Categoria())->count(),
            'totalUsuarios'    => (new Usuario())->count(),
            'primerosLibros'   => array_slice($libros->conCategoria(), 0, 5),
        ]);
    }
}
