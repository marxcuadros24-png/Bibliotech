<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Usuario;

/**
 * CRUD de usuarios. Solo accesible para el administrador.
 *
 * Además de validar el formulario, protege tres invariantes: no quedarse sin
 * administradores activos, no borrar la propia cuenta en uso y no borrar
 * usuarios con historial de préstamos (la clave foránea lo impediría).
 */
class UsuarioController extends Controller
{
    private const CLAVE_MINIMA = 8;

    private Usuario $usuarios;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->usuarios = new Usuario();
    }

    // GET /usuarios
    public function index(): void
    {
        $busqueda = $this->request->string('buscar');
        $rol = $this->request->string('rol');
        $estado = $this->request->string('estado');

        $this->view('usuarios.index', [
            'title'    => 'Usuarios',
            // El modelo descarta los valores de rol o estado que no existan.
            'usuarios' => $this->usuarios->conTotalPrestamos($busqueda, $rol, $estado),
            'roles'    => $this->usuarios->contarPorRol(),
            'busqueda' => $busqueda,
            'rol'      => $rol,
            'estado'   => $estado,
        ]);
    }

    // GET /usuarios/crear
    public function create(): void
    {
        $this->view('usuarios.create', [
            'title'   => 'Nuevo usuario',
            'usuario' => [
                'id' => null, 'nombre' => '', 'email' => '',
                'rol' => 'estudiante', 'estado' => 'activo',
            ],
            'accion'      => url('usuarios'),
            'metodo'      => 'crear',
            'rolesValidos' => Usuario::ROLES,
            'estados'     => Usuario::ESTADOS,
        ]);
    }

    // POST /usuarios
    public function store(): void
    {
        $this->protegerCsrf();

        $resultado = $this->validar();
        $password = (string) $this->request->post('password', '');

        if ($password === '') {
            $resultado['errores']['password'][] = 'La contraseña es obligatoria.';
        } elseif (mb_strlen($password) < self::CLAVE_MINIMA) {
            $resultado['errores']['password'][] = 'Mínimo ' . self::CLAVE_MINIMA . ' caracteres.';
        } elseif ($password !== (string) $this->request->post('password_confirmacion', '')) {
            $resultado['errores']['password_confirmacion'][] = 'Las contraseñas no coinciden.';
        }

        if ($resultado['errores'] !== []) {
            $this->volverConErrores($resultado['errores'], 'usuarios/crear');
        }

        $id = $this->usuarios->crear($resultado['datos'], $password);

        Session::flash('_messages', ['exito' => "El usuario se creó correctamente (id {$id})."]);

        $this->redirect('usuarios');
    }

    // GET /usuarios/{id}/editar
    public function edit(int $id): void
    {
        $usuario = $this->usuarios->find($id);

        if ($usuario === null) {
            $this->abort(404, "No existe el usuario con id {$id}.");
        }

        $this->view('usuarios.edit', [
            'title'        => 'Editar: ' . $usuario['nombre'],
            'usuario'      => $usuario,
            'prestamos'    => $this->usuarios->totalPrestamos($id),
            'esMiCuenta'   => Auth::id() === $id,
            'accion'       => url("usuarios/{$id}"),
            'metodo'       => 'editar',
            'rolesValidos' => Usuario::ROLES,
            'estados'      => Usuario::ESTADOS,
        ]);
    }

    // POST /usuarios/{id}
    public function update(int $id): void
    {
        $this->protegerCsrf();

        $usuario = $this->usuarios->find($id);

        if ($usuario === null) {
            $this->abort(404, "No existe el usuario con id {$id}.");
        }

        $resultado = $this->validar($id);
        $datos = $resultado['datos'];

        // No se puede degradar ni bloquear al último administrador activo.
        $sigueSiendoAdmin = $datos['rol'] === 'administrador' && $datos['estado'] === 'activo';

        if (!$sigueSiendoAdmin && $this->esUltimoAdministrador($id)) {
            $resultado['errores']['rol'][] = 'No puedes quitarle el rol de administrador al único que queda activo.';
        }

        // Tampoco bloquear la cuenta con la que se está navegando.
        if (Auth::id() === $id && $datos['estado'] === 'bloqueado') {
            $resultado['errores']['estado'][] = 'No puedes bloquear tu propia cuenta mientras la usas.';
        }

        $password = (string) $this->request->post('password', '');

        if ($password !== '') {
            if (mb_strlen($password) < self::CLAVE_MINIMA) {
                $resultado['errores']['password'][] = 'Mínimo ' . self::CLAVE_MINIMA . ' caracteres.';
            } elseif ($password !== (string) $this->request->post('password_confirmacion', '')) {
                $resultado['errores']['password_confirmacion'][] = 'Las contraseñas no coinciden.';
            }
        }

        if ($resultado['errores'] !== []) {
            $this->volverConErrores($resultado['errores'], "usuarios/{$id}/editar");
        }

        $this->usuarios->actualizar($id, $datos, $password === '' ? null : $password);

        Session::flash('_messages', ['exito' => 'Los cambios se guardaron correctamente.']);

        $this->redirect('usuarios');
    }

    // POST /usuarios/{id}/eliminar
    public function destroy(int $id): void
    {
        $this->protegerCsrf();

        $usuario = $this->usuarios->find($id);

        if ($usuario === null) {
            $this->abort(404, "No existe el usuario con id {$id}.");
        }

        if (Auth::id() === $id) {
            Session::flash('_messages', ['error' => 'No puedes eliminar tu propia cuenta.']);

            $this->redirect('usuarios');
        }

        // La clave foránea de prestamos.id_usuario no tiene ON DELETE, así que
        // el borrado fallaría: mejor explicarlo que dejar que reviente el SQL.
        $prestamos = $this->usuarios->totalPrestamos($id);

        if ($prestamos > 0) {
            Session::flash('_messages', [
                'error' => "No se puede eliminar: el usuario tiene {$prestamos} préstamo(s) registrado(s). Bloquéalo en lugar de borrarlo.",
            ]);

            $this->redirect('usuarios');
        }

        if ($this->esUltimoAdministrador($id)) {
            Session::flash('_messages', [
                'error' => 'No se puede eliminar al último administrador activo.',
            ]);

            $this->redirect('usuarios');
        }

        $this->usuarios->delete($id);

        Session::flash('_messages', ['exito' => 'El usuario se eliminó correctamente.']);

        $this->redirect('usuarios');
    }

    // -----------------------------------------------------------------------

    /** ¿Es el único administrador activo que queda? */
    private function esUltimoAdministrador(int $id): bool
    {
        $usuario = $this->usuarios->find($id);

        if ($usuario === null || $usuario['rol'] !== 'administrador') {
            return false;
        }

        return $this->usuarios->contarAdministradoresActivos($id) === 0;
    }

    /**
     * @return array{datos: array<string, mixed>, errores: array<string, list<string>>}
     */
    private function validar(?int $idActual = null): array
    {
        $errores = [];

        $nombre = $this->request->string('nombre');
        $email = mb_strtolower($this->request->string('email'));
        $rol = $this->request->string('rol');
        $estado = $this->request->string('estado');

        if ($nombre === '') {
            $errores['nombre'][] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 120) {
            $errores['nombre'][] = 'El nombre no puede superar los 120 caracteres.';
        }

        if ($email === '') {
            $errores['email'][] = 'El correo es obligatorio.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errores['email'][] = 'Escribe un correo válido.';
        } elseif (mb_strlen($email) > 180) {
            $errores['email'][] = 'El correo no puede superar los 180 caracteres.';
        } elseif ($this->usuarios->emailDuplicado($email, $idActual)) {
            $errores['email'][] = 'Ya existe una cuenta con ese correo.';
        }

        if (!in_array($rol, Usuario::ROLES, true)) {
            $errores['rol'][] = 'Selecciona un rol válido.';
        }

        if (!in_array($estado, Usuario::ESTADOS, true)) {
            $errores['estado'][] = 'Selecciona un estado válido.';
        }

        return [
            'datos' => [
                'nombre' => $nombre,
                'email'  => $email,
                'rol'    => $rol,
                'estado' => $estado,
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
