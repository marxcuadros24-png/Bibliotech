<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Categoria;

class CategoriaController extends Controller
{
    private Categoria $categorias;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->categorias = new Categoria();
    }

    // GET /categorias
    public function index(): void
    {
        $this->view('categorias.index', [
            'title'      => 'Categorías',
            'categorias' => $this->categorias->conTotalLibros(),
        ]);
    }

    // GET /categorias/crear
    public function create(): void
    {
        $this->view('categorias.create', [
            'title'  => 'Nueva categoría',
            'categoria' => ['id' => null, 'nombre' => '', 'descripcion' => ''],
            'accion' => url('categorias'),
            'metodo' => 'crear',
        ]);
    }

    // POST /categorias
    public function store(): void
    {
        $this->protegerCsrf();

        $resultado = $this->validar();

        if ($resultado['errores'] !== []) {
            $this->volverConErrores($resultado['errores'], 'categorias/crear');
        }

        $this->categorias->create($resultado['datos']);

        Session::flash('_messages', ['exito' => 'La categoría se registró correctamente.']);

        $this->redirect('categorias');
    }

    // GET /categorias/{id}/editar
    public function edit(int $id): void
    {
        $categoria = $this->categorias->find($id);

        if ($categoria === null) {
            $this->abort(404, "No existe la categoría con id {$id}.");
        }

        $this->view('categorias.edit', [
            'title'     => 'Editar: ' . $categoria['nombre'],
            'categoria' => $categoria,
            'libros'    => $this->categorias->totalLibros($id),
            'accion'    => url("categorias/{$id}"),
            'metodo'    => 'editar',
        ]);
    }

    // POST /categorias/{id}
    public function update(int $id): void
    {
        $this->protegerCsrf();

        if (!$this->categorias->exists($id)) {
            $this->abort(404, "No existe la categoría con id {$id}.");
        }

        $resultado = $this->validar($id);

        if ($resultado['errores'] !== []) {
            $this->volverConErrores($resultado['errores'], "categorias/{$id}/editar");
        }

        $this->categorias->update($id, $resultado['datos']);

        Session::flash('_messages', ['exito' => 'Los cambios se guardaron correctamente.']);

        $this->redirect('categorias');
    }

    // POST /categorias/{id}/eliminar
    public function destroy(int $id): void
    {
        $this->protegerCsrf();

        $categoria = $this->categorias->find($id);

        if ($categoria === null) {
            $this->abort(404, "No existe la categoría con id {$id}.");
        }

        // La clave foránea es ON DELETE SET NULL, así que los libros no se pierden:
        // simplemente quedan sin categoría. Se avisa cuántos son.
        $afectados = $this->categorias->totalLibros($id);

        $this->categorias->delete($id);

        $mensaje = $afectados > 0
            ? "Categoría eliminada. {$afectados} libro(s) quedaron sin categoría."
            : 'Categoría eliminada.';

        Session::flash('_messages', ['exito' => $mensaje]);

        $this->redirect('categorias');
    }

    // -----------------------------------------------------------------------

    /**
     * @return array{datos: array<string, mixed>, errores: array<string, list<string>>}
     */
    private function validar(?int $idActual = null): array
    {
        $errores = [];

        $nombre = $this->request->string('nombre');
        $descripcion = $this->request->string('descripcion');

        if ($nombre === '') {
            $errores['nombre'][] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errores['nombre'][] = 'El nombre no puede superar los 100 caracteres.';
        } elseif ($this->categorias->nombreDuplicado($nombre, $idActual)) {
            $errores['nombre'][] = 'Ya existe una categoría con ese nombre.';
        }

        return [
            'datos' => [
                'nombre'      => $nombre,
                'descripcion' => $descripcion === '' ? null : $descripcion,
            ],
            'errores' => $errores,
        ];
    }

    /** @param array<string, list<string>> $errores */
    private function volverConErrores(array $errores, string $destino): never
    {
        Session::flash('_errors', $errores);
        Session::flash('_old', $this->request->body);
        Session::flash('_messages', ['error' => 'Revisa los campos marcados en rojo.']);

        $this->redirect($destino);
    }
}
