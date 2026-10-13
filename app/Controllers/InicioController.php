<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Portada pública del sistema (página de inicio).
 *
 * No lleva filtro de sesión: cualquier visitante (anónimo o autenticado) puede
 * verla. El layout público arma el menú según haya sesión o no, y el botón
 * "Comenzar" envía a cada uno donde corresponde (login o panel).
 */
class InicioController extends Controller
{
    // GET /
    public function index(): void
    {
        $this->view('inicio', ['title' => 'Inicio'], 'publico');
    }
}
