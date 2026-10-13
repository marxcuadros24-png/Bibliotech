<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Libro;
use App\Models\Multa;
use App\Models\Prestamo;
use App\Models\Usuario;
use DateTimeImmutable;
use RuntimeException;

/**
 * Préstamos.
 *
 * El listado y el detalle están abiertos a cualquier sesión, pero el controlador
 * limita a los estudiantes a sus propios registros: si se les pasara el listado
 * completo verían los préstamos de los demás.
 */
class PrestamoController extends Controller
{
    private Prestamo $prestamos;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->prestamos = new Prestamo();
    }

    // GET /prestamos
    public function index(): void
    {
        $this->prestamos->sincronizarVencidos();

        // El estudiante solo ve lo suyo; el personal ve todo.
        $idUsuario = Auth::es('estudiante') ? Auth::id() : null;

        $this->view('prestamos.index', [
            'title'        => Auth::es('estudiante') ? 'Mis préstamos' : 'Préstamos',
            'prestamos'    => $this->prestamos->listado(
                $this->request->string('estado') ?: null,
                $idUsuario,
                $this->request->string('buscar') ?: null,
            ),
            'estado'       => $this->request->string('estado'),
            'busqueda'     => $this->request->string('buscar'),
            'conteo'       => $this->prestamos->contarPorEstado(),
            'soloPropios'  => Auth::es('estudiante'),
        ]);
    }

    // GET /prestamos/crear
    public function create(): void
    {
        $this->view('prestamos.create', [
            'title'           => 'Registrar préstamo',
            'libros'          => (new Libro())->disponibles(),
            'usuarios'        => (new Usuario())->activos(),
            'diasPorDefecto'  => (int) App::config('biblioteca.dias_prestamo', 7),
            'fechaSugerida'   => date('Y-m-d', strtotime('+' . (int) App::config('biblioteca.dias_prestamo', 7) . ' days')),
            // Permiten llegar con el usuario o el libro ya elegidos, por ejemplo
            // desde el botón "Prestar" del catálogo (?libro=5).
            'idUsuarioSugerido' => $this->request->integer('id_usuario'),
            'idLibroSugerido'   => $this->request->integer('libro'),
        ]);
    }

    // POST /prestamos
    public function store(): void
    {
        $this->protegerCsrf();

        $idUsuario = $this->request->integer('id_usuario');
        $idLibro = $this->request->integer('id_libro');
        $fecha = $this->request->string('fecha_devolucion_esperada');

        $errores = [];

        if ($idUsuario < 1) {
            $errores['id_usuario'][] = 'Selecciona el usuario que recibe el préstamo.';
        }

        if ($idLibro < 1) {
            $errores['id_libro'][] = 'Selecciona el libro que se presta.';
        }

        if ($fecha !== '' && strtotime($fecha) === false) {
            $errores['fecha_devolucion_esperada'][] = 'Escribe una fecha válida.';
        }

        if ($errores !== []) {
            $this->volverConErrores($errores, 'prestamos/crear');
        }

        try {
            $id = $this->prestamos->registrar($idUsuario, $idLibro, $fecha ?: null);
        } catch (RuntimeException $e) {
            // Reglas de negocio (usuario bloqueado, sin stock, fecha inválida):
            // el mensaje ya está redactado para mostrarlo tal cual.
            $this->volverConErrores(['id_libro' => [$e->getMessage()]], 'prestamos/crear');
        }

        Session::flash('_messages', ['exito' => "Préstamo registrado (folio #{$id})."]);

        $this->redirect("prestamos/{$id}");
    }

    // GET /prestamos/{id}
    public function show(int $id): void
    {
        // También aquí: si se entra directo a la ficha, el estado debe reflejar
        // el atraso sin obligar a pasar antes por el listado.
        $this->prestamos->sincronizarVencidos();

        $prestamo = $this->prestamos->detalle($id);

        if ($prestamo === null) {
            $this->abort(404, "No existe el préstamo con id {$id}.");
        }

        $this->autorizarLectura($prestamo);

        // Estimación de lo que costaría devolverlo hoy: deja ver el cálculo
        // automático de la mora antes de confirmar la devolución.
        $abierto = in_array($prestamo['estado'], ['activo', 'vencido'], true);
        $diasHoy = $abierto
            ? Multa::diasDeMora($prestamo['fecha_devolucion_esperada'], new DateTimeImmutable('today'))
            : 0;

        $this->view('prestamos.show', [
            'title'        => 'Préstamo #' . $id,
            'prestamo'     => $prestamo,
            'multas'       => $this->prestamos->multas($id),
            'esPropio'     => (int) $prestamo['id_usuario'] === Auth::id(),
            'abierto'      => $abierto,
            'diasHoy'      => $diasHoy,
            'multaHoy'     => $diasHoy > 0 ? Multa::calcularMonto($diasHoy) : 0.0,
            'tarifa'       => Multa::tarifaDiaria(),
        ]);
    }

    // POST /prestamos/{id}/devolver
    public function devolver(int $id): void
    {
        $this->protegerCsrf();

        if ($this->prestamos->detalle($id) === null) {
            $this->abort(404, "No existe el préstamo con id {$id}.");
        }

        try {
            $multa = $this->prestamos->devolver($id, $this->request->string('fecha_devolucion_real') ?: null);
        } catch (RuntimeException $e) {
            Session::flash('_messages', ['error' => $e->getMessage()]);

            $this->redirect("prestamos/{$id}");
        }

        // Se informa el resultado del cálculo automático de la mora.
        if ($multa === null) {
            Session::flash('_messages', ['exito' => 'Devolución registrada a tiempo. No corresponde multa.']);
        } else {
            Session::flash('_messages', [
                'error' => sprintf(
                    'Devolución registrada con %d día(s) de mora. Se generó una multa de %s.',
                    $multa['dias'],
                    moneda($multa['monto']),
                ),
            ]);
        }

        $this->redirect("prestamos/{$id}");
    }

    // -----------------------------------------------------------------------

    /** Un estudiante solo puede ver sus propios préstamos. */
    private function autorizarLectura(array $prestamo): void
    {
        if (Auth::es('estudiante') && (int) $prestamo['id_usuario'] !== (int) Auth::id()) {
            $this->abort(403, 'Este préstamo no es tuyo.');
        }
    }

    /** @param array<string, list<string>> $errores */
    private function volverConErrores(array $errores, string $destino): never
    {
        Session::flash('_errors', $errores);
        Session::flash('_old', $this->request->body);
        Session::flash('_messages', ['error' => 'No se pudo registrar el préstamo.']);

        $this->redirect($destino);
    }
}
