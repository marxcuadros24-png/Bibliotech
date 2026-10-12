<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Multa;
use RuntimeException;

/**
 * Multas.
 *
 * Los importes no se capturan a mano en ningún formulario: los genera el
 * sistema al registrar una devolución con mora. Aquí solo se consultan y se
 * cambia su estado (pagada o condonada).
 */
class MultaController extends Controller
{
    private Multa $multas;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->multas = new Multa();
    }

    // GET /multas
    public function index(): void
    {
        $soloPropios = Auth::es('estudiante');

        $this->view('multas.index', [
            'title'  => $soloPropios ? 'Mis multas' : 'Multas',
            'multas' => $this->multas->listado(
                $this->request->string('estado') ?: null,
                $soloPropios ? Auth::id() : null,
                $this->request->string('buscar') ?: null,
            ),
            'estado'      => $this->request->string('estado'),
            'busqueda'    => $this->request->string('buscar'),
            'resumen'     => $this->multas->resumen(),
            'tarifa'      => Multa::tarifaDiaria(),
            'soloPropios' => $soloPropios,
        ]);
    }

    // POST /multas/{id}/pagar
    public function pagar(int $id): void
    {
        $this->cambiarEstado($id, 'pagar');
    }

    // POST /multas/{id}/condonar
    public function condonar(int $id): void
    {
        $this->cambiarEstado($id, 'condonar');
    }

    // -----------------------------------------------------------------------

    private function cambiarEstado(int $id, string $accion): void
    {
        $this->protegerCsrf();

        $multa = $this->multas->detalle($id);

        if ($multa === null) {
            $this->abort(404, "No existe la multa con id {$id}.");
        }

        try {
            if ($accion === 'pagar') {
                $this->multas->pagar($id);

                Session::flash('_messages', [
                    'exito' => 'Multa marcada como pagada: ' . moneda($multa['monto']) . '.',
                ]);
            } else {
                $this->multas->condonar($id);

                Session::flash('_messages', [
                    'exito' => 'Multa condonada: ' . moneda($multa['monto']) . '.',
                ]);
            }
        } catch (RuntimeException $e) {
            // Ya estaba cerrada (condición de carrera entre dos pestañas).
            Session::flash('_messages', ['error' => $e->getMessage()]);
        }

        $this->redirect('multas');
    }
}
