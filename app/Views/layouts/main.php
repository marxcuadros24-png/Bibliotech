<?php
/**
 * Layout de administración.
 *
 * Arma el armazón de las páginas con sesión: cabecera superior (logo a la
 * izquierda, enlaces y sesión a la derecha) y menú lateral en tarjeta con las
 * secciones del sistema. Cada enlace se pinta solo si el rol tiene permiso, de
 * modo que nadie ve accesos que le van a responder 403; igual cada ruta vuelve
 * a validar sus permisos, porque ocultar un enlace no es una medida de
 * seguridad.
 *
 * @var string $title
 * @var string $content
 */
$titulo = $title ?? 'Bibliotech';
$appNombre = (string) env('APP_NAME', 'Bibliotech');
$exito = mensaje_flash('exito');
$error = mensaje_flash('error');
$usuario = App\Core\Auth::usuario();
$esEstudiante = App\Core\Auth::es('estudiante');
$errores = errores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> · <?= e($appNombre) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
    <header class="cabecera">
        <div class="contenedor cabecera__interior">
            <a class="marca" href="<?= e(url('panel')) ?>">
                <span class="marca__icono">BT</span>
                <span class="marca__texto"><?= e($appNombre) ?></span>
            </a>

            <div class="cabecera__derecha">
                <nav class="nav">
                    <a class="nav__enlace" href="<?= e(url('catalogo')) ?>">Catálogo</a>
                    <a class="nav__enlace" href="<?= e(url()) ?>">Sitio público</a>
                </nav>

                <div class="sesion">
                    <span class="sesion__datos">
                        <span class="sesion__nombre"><?= e($usuario['nombre'] ?? '') ?></span>
                        <span class="sesion__rol"><?= e(ucfirst((string) ($usuario['rol'] ?? ''))) ?></span>
                    </span>

                    <form method="POST" action="<?= e(url('logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="boton boton--sesion" type="submit">Salir</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="contenedor panel">
        <aside class="menu-lateral">
            <p class="menu-lateral__titulo"><?= $esEstudiante ? 'Mi cuenta' : 'Administración' ?></p>

            <a class="menu-lateral__enlace <?= activo('panel') ?>" href="<?= e(url('panel')) ?>">
                <?= $esEstudiante ? 'Mi panel' : 'Dashboard' ?>
            </a>

            <?php if (puede('libros', 'ver')): ?>
                <a class="menu-lateral__enlace <?= activo('libros') ?>" href="<?= e(url('libros')) ?>">
                    <?= $esEstudiante ? 'Catálogo' : 'Libros' ?>
                </a>
            <?php endif; ?>

            <?php if (puede('categorias', 'ver')): ?>
                <a class="menu-lateral__enlace <?= activo('categorias') ?>" href="<?= e(url('categorias')) ?>">Categorías</a>
            <?php endif; ?>

            <a class="menu-lateral__enlace <?= activo('prestamos') ?>" href="<?= e(url('prestamos')) ?>">
                <?= $esEstudiante ? 'Mis préstamos' : 'Préstamos' ?>
            </a>

            <a class="menu-lateral__enlace <?= activo('multas') ?>" href="<?= e(url('multas')) ?>">
                <?= $esEstudiante ? 'Mis multas' : 'Multas' ?>
            </a>

            <?php if (puede('usuarios', 'ver')): ?>
                <a class="menu-lateral__enlace <?= activo('usuarios') ?>" href="<?= e(url('usuarios')) ?>">Usuarios</a>
            <?php endif; ?>

            <?php if (App\Core\Auth::es('administrador')): ?>
                <a class="menu-lateral__enlace <?= activo('reporte') ?>" href="<?= e(url('reporte')) ?>">Reporte general</a>
            <?php endif; ?>
        </aside>

        <main class="contenido contenido--admin">
            <?php if ($exito !== null): ?>
                <div class="aviso aviso--exito"><?= e($exito) ?></div>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="aviso aviso--error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($errores !== []): ?>
                <div class="aviso aviso--error">
                    Hay <?= e(count($errores)) ?> campo(s) con errores. Revisa el formulario.
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <footer class="pie">
        <div class="contenedor">
            <?= e($appNombre) ?> · Proyecto de Arquitectura y Programación Web ·
            PHP <?= e(PHP_VERSION) ?>
        </div>
    </footer>
</body>
</html>
