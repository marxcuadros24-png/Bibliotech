<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Usuario;
use RuntimeException;

class AuthController extends Controller
{
    private const CLAVE_MINIMA = 8;

    // -----------------------------------------------------------------------
    // GET /login
    // -----------------------------------------------------------------------
    public function mostrarLogin(): void
    {
        $this->view('auth.login', ['title' => 'Iniciar sesión'], 'auth');
    }

    // -----------------------------------------------------------------------
    // POST /login
    // -----------------------------------------------------------------------
    public function login(): void
    {
        $this->protegerCsrf();

        $email = mb_strtolower($this->request->string('email'));
        $password = (string) $this->request->post('password', '');

        $errores = [];

        if ($email === '') {
            $errores['email'][] = 'El correo es obligatorio.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errores['email'][] = 'Escribe un correo válido.';
        }

        if ($password === '') {
            $errores['password'][] = 'La contraseña es obligatoria.';
        }

        if ($errores === [] && Auth::intentar($email, $password)) {
            // Si el filtro 'auth' lo desvió, se lo devuelve a donde quería ir.
            $destino = Session::pull('_intended', Auth::destinoInicial());

            Session::flash('_messages', ['exito' => 'Bienvenido, ' . Auth::nombre() . '.']);

            $this->redirect(is_string($destino) ? ltrim($destino, '/') : '');
        }

        // Un solo mensaje para credenciales incorrectas y cuentas bloqueadas:
        // no hay que revelar cuál de las dos cosas pasó.
        if ($errores === []) {
            $errores['email'][] = 'El correo o la contraseña no son correctos, o la cuenta está bloqueada.';
        }

        Session::flash('_errors', $errores);
        Session::flash('_old', ['email' => $email]);

        $this->redirect('login');
    }

    // -----------------------------------------------------------------------
    // GET /registro
    // -----------------------------------------------------------------------
    public function mostrarRegistro(): void
    {
        $this->view('auth.registro', ['title' => 'Crear cuenta'], 'auth');
    }

    // -----------------------------------------------------------------------
    // POST /registro
    // -----------------------------------------------------------------------
    public function registrar(): void
    {
        $this->protegerCsrf();

        $nombre = $this->request->string('nombre');
        $email = mb_strtolower($this->request->string('email'));
        $password = (string) $this->request->post('password', '');
        $confirmacion = (string) $this->request->post('password_confirmacion', '');

        $errores = [];

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
        }

        if ($password === '') {
            $errores['password'][] = 'La contraseña es obligatoria.';
        } elseif (mb_strlen($password) < self::CLAVE_MINIMA) {
            $errores['password'][] = 'Mínimo ' . self::CLAVE_MINIMA . ' caracteres.';
        } elseif ($password !== $confirmacion) {
            $errores['password_confirmacion'][] = 'Las contraseñas no coinciden.';
        }

        if ($errores !== []) {
            Session::flash('_errors', $errores);
            Session::flash('_old', ['nombre' => $nombre, 'email' => $email]);

            $this->redirect('registro');
        }

        $usuarios = new Usuario();

        try {
            // Solo se pasan nombre y correo: el rol lo decide el modelo.
            $id = $usuarios->registrar(['nombre' => $nombre, 'email' => $email], $password);
        } catch (RuntimeException $e) {
            Session::flash('_errors', ['email' => [$e->getMessage()]]);
            Session::flash('_old', ['nombre' => $nombre, 'email' => $email]);

            $this->redirect('registro');
        }

        // Se abre la sesión de una vez para no obligar a escribir las
        // credenciales otra vez justo después de crearlas.
        $creado = $usuarios->find($id);

        if ($creado !== null) {
            Auth::abrirSesion($creado);
        }

        Session::flash('_messages', [
            'exito' => 'Tu cuenta se creó correctamente. ¡Bienvenido, ' . $nombre . '!',
        ]);

        $this->redirect(Auth::destinoInicial());
    }

    // -----------------------------------------------------------------------
    // POST /logout
    // -----------------------------------------------------------------------
    public function logout(): void
    {
        $this->protegerCsrf();

        Auth::salir();

        Session::flash('_messages', ['exito' => 'Cerraste sesión correctamente.']);

        // Al cerrar sesión se vuelve a la portada pública ('/'), no al formulario
        // de acceso: el visitante ya no tiene sesión y la portada se muestra a
        // cualquiera, así que es el destino natural para un usuario público.
        $this->redirect('');
    }
}
