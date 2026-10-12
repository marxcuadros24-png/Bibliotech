<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;

class AuthController extends Controller
{
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
    // POST /logout
    // -----------------------------------------------------------------------
    public function logout(): void
    {
        $this->protegerCsrf();

        Auth::salir();

        Session::flash('_messages', ['exito' => 'Cerraste sesión correctamente.']);

        $this->redirect('login');
    }
}
