<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Libro;
use App\Models\Multa;
use App\Models\Prestamo;
use App\Models\Usuario;

/**
 * Panel de inicio.
 *
 * El contenido cambia según el rol: el personal ve el estado general de la
 * biblioteca y el estudiante ve sus propios préstamos y multas. Así el
 * estudiante no recibe datos que no le corresponden.
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        $prestamos = new Prestamo();
        $multas = new Multa();

        // Mantiene al día el estado de los préstamos antes de contarlos.
        $prestamos->sincronizarVencidos();

        if (Auth::es('estudiante')) {
            $this->panelEstudiante($prestamos, $multas);

            return;
        }

        $this->panelPersonal($prestamos, $multas);
    }

    // -----------------------------------------------------------------------
    // GET /reporte (ruta con el filtro 'rol:administrador')
    // -----------------------------------------------------------------------

    /**
     * Reporte general de la biblioteca: conteos totales para el administrador.
     *
     * Se separa del panel de inicio (que es lo que ve cada rol al entrar) para
     * que este resumen quede restringido a los administradores sin quitarles a
     * los estudiantes su propio panel.
     */
    public function reporte(): void
    {
        // Defensa en profundidad: la ruta ya exige el rol, pero si algún día se
        // registrara sin el filtro, el reporte seguiría siendo solo del admin.
        if (!Auth::es('administrador')) {
            $this->abort(403, 'Solo un administrador puede ver el reporte general.');
        }

        $prestamos = new Prestamo();
        $multas = new Multa();

        // Mantiene al día los préstamos vencidos antes de contarlos.
        $prestamos->sincronizarVencidos();

        $resumenMultas = $multas->resumen();

        $this->view('dashboard', [
            'title'            => 'Reporte general',
            'totalLibros'      => (new Libro())->count(),
            'totalUsuarios'    => (new Usuario())->count(),
            'prestamosActivos' => $prestamos->contarPorEstado()['activo'],
            'multasPendientes' => $resumenMultas['pendiente']['cantidad'],
            'montoPendiente'   => $resumenMultas['pendiente']['total'],
        ]);
    }

    private function panelPersonal(Prestamo $prestamos, Multa $multas): void
    {
        $libros = new Libro();

        $this->view('dashboard.index', [
            'title'       => 'Panel',
            'esPersonal'  => true,
            'totalLibros' => $libros->count(),
            'disponibles' => $libros->totalDisponibles(),
            'ejemplares'  => $libros->totalEjemplares(),
            'prestamos'   => $prestamos->contarPorEstado(),
            'multas'      => $multas->resumen(),
            'categorias'  => (new Categoria())->count(),
            'usuarios'    => (new Usuario())->count(),
            'roles'       => (new Usuario())->contarPorRol(),
            'porVencer'   => array_slice($prestamos->listado('activo'), 0, 5),
            'atrasados'   => array_slice($prestamos->listado('vencido'), 0, 5),
        ]);
    }

    private function panelEstudiante(Prestamo $prestamos, Multa $multas): void
    {
        $idUsuario = (int) Auth::id();

        $this->view('dashboard.index', [
            'title'          => 'Mi panel',
            'esPersonal'     => false,
            'misPrestamos'   => $prestamos->vigentesDe($idUsuario),
            'miHistorial'    => array_slice($prestamos->listado(null, $idUsuario), 0, 5),
            'misMultas'      => $multas->pendientesDe($idUsuario),
            'totalPendiente' => $multas->totalPendienteDe($idUsuario),
        ]);
    }
}
