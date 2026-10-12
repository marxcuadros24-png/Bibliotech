<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Categoria;
use App\Models\Libro;

class LibroController extends Controller
{
    private Libro $libros;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->libros = new Libro();
    }

    // -----------------------------------------------------------------------
    // GET /libros
    // -----------------------------------------------------------------------
    public function index(): void
    {
        $busqueda = $this->request->string('buscar');

        $this->view('libros.index', [
            'title'    => 'Catálogo de libros',
            'libros'   => $this->libros->conCategoria($busqueda),
            'busqueda' => $busqueda,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /libros/{id}
    // -----------------------------------------------------------------------
    public function show(int $id): void
    {
        $libro = $this->libros->encontrarConCategoria($id);

        if ($libro === null) {
            $this->abort(404, "No existe el libro con id {$id}.");
        }

        $this->view('libros.show', [
            'title' => $libro['titulo'],
            'libro' => $libro,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /libros/crear
    // -----------------------------------------------------------------------
    public function create(): void
    {
        $this->view('libros.create', [
            'title'      => 'Registrar libro',
            'libro'      => $this->libroVacio(),
            'categorias' => (new Categoria())->all('nombre'),
            'accion'     => url('libros'),
            'metodo'     => 'crear',
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /libros
    // -----------------------------------------------------------------------
    public function store(): void
    {
        $this->protegerCsrf();

        $resultado = $this->validar();

        if ($resultado['errores'] !== []) {
            $this->volverConErrores($resultado['errores'], 'libros/crear');
        }

        $id = $this->libros->create($resultado['libro']);

        Session::flash('_messages', ['exito' => 'El libro se registró correctamente.']);

        $this->redirect("libros/{$id}");
    }

    // -----------------------------------------------------------------------
    // GET /libros/{id}/editar
    // -----------------------------------------------------------------------
    public function edit(int $id): void
    {
        $libro = $this->libros->find($id);

        if ($libro === null) {
            $this->abort(404, "No existe el libro con id {$id}.");
        }

        $this->view('libros.edit', [
            'title'      => 'Editar: ' . $libro['titulo'],
            'libro'      => $libro,
            'categorias' => (new Categoria())->all('nombre'),
            'accion'     => url("libros/{$id}"),
            'metodo'     => 'editar',
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /libros/{id}
    // -----------------------------------------------------------------------
    public function update(int $id): void
    {
        $this->protegerCsrf();

        if (!$this->libros->exists($id)) {
            $this->abort(404, "No existe el libro con id {$id}.");
        }

        $resultado = $this->validar($id);

        if ($resultado['errores'] !== []) {
            $this->volverConErrores($resultado['errores'], "libros/{$id}/editar");
        }

        $this->libros->update($id, $resultado['libro']);

        Session::flash('_messages', ['exito' => 'Los cambios se guardaron correctamente.']);

        $this->redirect("libros/{$id}");
    }

    // -----------------------------------------------------------------------
    // POST /libros/{id}/eliminar
    // -----------------------------------------------------------------------
    public function destroy(int $id): void
    {
        $this->protegerCsrf();

        if (!$this->libros->exists($id)) {
            $this->abort(404, "No existe el libro con id {$id}.");
        }

        $this->libros->delete($id);

        Session::flash('_messages', ['exito' => 'El libro se eliminó del catálogo.']);

        $this->redirect('libros');
    }

    // -----------------------------------------------------------------------
    // GET /api/libros
    // -----------------------------------------------------------------------
    public function apiIndex(): void
    {
        $this->json([
            'data'  => $this->libros->conCategoria($this->request->string('buscar')),
            'total' => $this->libros->count(),
        ]);
    }

    // -----------------------------------------------------------------------
    // Apoyo interno
    // -----------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function libroVacio(): array
    {
        return [
            'id'               => null,
            'titulo'           => '',
            'autor'            => '',
            'isbn'             => '',
            'id_categoria'     => '',
            'editorial'        => '',
            'anio'             => '',
            'portada_url'      => '',
            'sinopsis'         => '',
            'stock_total'      => 1,
            'stock_disponible' => 1,
        ];
    }

    /**
     * Valida el formulario y prepara los datos para persistir.
     *
     * @return array{libro: array<string, mixed>, errores: array<string, list<string>>}
     */
    private function validar(?int $idActual = null): array
    {
        $errores = [];

        $titulo = $this->request->string('titulo');
        $autor = $this->request->string('autor');
        $isbn = $this->request->string('isbn');
        $editorial = $this->request->string('editorial');
        $sinopsis = $this->request->string('sinopsis');
        $portadaUrl = $this->request->string('portada_url');
        $anioRaw = $this->request->string('anio');
        $anio = $this->request->integer('anio');
        $categoriaId = $this->request->integer('id_categoria');
        $stockTotal = $this->request->integer('stock_total');
        $stockDisponible = $this->request->integer('stock_disponible');

        // titulo varchar(255) NOT NULL
        if ($titulo === '') {
            $errores['titulo'][] = 'El título es obligatorio.';
        } elseif (mb_strlen($titulo) > 255) {
            $errores['titulo'][] = 'El título no puede superar los 255 caracteres.';
        }

        // autor varchar(180) NOT NULL
        if ($autor === '') {
            $errores['autor'][] = 'El autor es obligatorio.';
        } elseif (mb_strlen($autor) > 180) {
            $errores['autor'][] = 'El autor no puede superar los 180 caracteres.';
        }

        // isbn varchar(20) UNIQUE, admite NULL
        if ($isbn !== '') {
            if (!preg_match('/^[0-9Xx-]{10,20}$/', $isbn)) {
                $errores['isbn'][] = 'El ISBN admite solo dígitos, guiones y la letra X (10 a 20 caracteres).';
            } elseif ($this->libros->isbnDuplicado($isbn, $idActual)) {
                $errores['isbn'][] = 'Ya existe otro libro registrado con ese ISBN.';
            }
        }

        // editorial varchar(150)
        if (mb_strlen($editorial) > 150) {
            $errores['editorial'][] = 'La editorial no puede superar los 150 caracteres.';
        }

        // portada_url varchar(500)
        if ($portadaUrl !== '') {
            if (mb_strlen($portadaUrl) > 500) {
                $errores['portada_url'][] = 'La URL de la portada no puede superar los 500 caracteres.';
            } elseif (filter_var($portadaUrl, FILTER_VALIDATE_URL) === false) {
                $errores['portada_url'][] = 'Escribe una URL válida (por ejemplo https://...).';
            }
        }

        // anio smallint unsigned
        $anioMaximo = (int) date('Y') + 1;
        if ($anioRaw !== '' && ($anio < 1000 || $anio > $anioMaximo)) {
            $errores['anio'][] = "El año debe estar entre 1000 y {$anioMaximo}.";
        }

        // id_categoria FK opcional
        if ($categoriaId > 0 && !(new Categoria())->exists($categoriaId)) {
            $errores['id_categoria'][] = 'La categoría seleccionada no existe.';
        }

        // stock_* int unsigned NOT NULL
        if ($stockTotal < 0) {
            $errores['stock_total'][] = 'El stock total no puede ser negativo.';
        }

        if ($stockDisponible < 0) {
            $errores['stock_disponible'][] = 'El stock disponible no puede ser negativo.';
        } elseif ($stockTotal >= 0 && $stockDisponible > $stockTotal) {
            $errores['stock_disponible'][] = 'El stock disponible no puede ser mayor que el stock total.';
        }

        $libro = [
            'titulo'           => $titulo,
            'autor'            => $autor,
            'isbn'             => $isbn === '' ? null : $isbn,
            'id_categoria'     => $categoriaId > 0 ? $categoriaId : null,
            'editorial'        => $editorial === '' ? null : $editorial,
            'anio'             => $anioRaw === '' ? null : $anio,
            'portada_url'      => $portadaUrl === '' ? null : $portadaUrl,
            'sinopsis'         => $sinopsis === '' ? null : $sinopsis,
            'stock_total'      => max(0, $stockTotal),
            'stock_disponible' => max(0, $stockDisponible),
        ];

        return ['libro' => $libro, 'errores' => $errores];
    }

    /**
     * Vuelve al formulario conservando lo escrito y los errores detectados.
     *
     * @param array<string, list<string>> $errores
     */
    private function volverConErrores(array $errores, string $destino): never
    {
        Session::flash('_errors', $errores);
        Session::flash('_old', $this->request->body);
        Session::flash('_messages', ['error' => 'Revisa los campos marcados en rojo.']);

        $this->redirect($destino);
    }
}
