<?php
/**
 * Layout principal.
 *
 * El menú se arma con los permisos del usuario, así que cada rol ve solo las
 * secciones a las que puede entrar. Igual cada ruta valida sus permisos: ocultar
 * un enlace no es una medida de seguridad, solo evita ofrecer algo inaccesible.
 *
 * @var string      $title
 * @var string      $content
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
            <a class="marca" href="<?= e(url()) ?>">
                <span class="marca__icono">BT</span>
                <span class="marca__texto"><?= e($appNombre) ?></span>
            </a>

            <nav class="nav">
                <a class="nav__enlace <?= activo('prestamos') === '' && activo('multas') === '' && activo('libros') === '' && activo('categorias') === '' && activo('usuarios') === '' ? 'activo' : '' ?>"
                   href="<?= e(url()) ?>">Panel</a>

                <a class="nav__enlace <?= activo('libros') ?>" href="<?= e(url('libros')) ?>">Catálogo</a>

                <a class="nav__enlace <?= activo('prestamos') ?>" href="<?= e(url('prestamos')) ?>">
                    <?= $esEstudiante ? 'Mis préstamos' : 'Préstamos' ?>
                </a>

                <a class="nav__enlace <?= activo('multas') ?>" href="<?= e(url('multas')) ?>">
                    <?= $esEstudiante ? 'Mis multas' : 'Multas' ?>
                </a>

                <?php if (puede('categorias', 'ver')): ?>
                    <a class="nav__enlace <?= activo('categorias') ?>" href="<?= e(url('categorias')) ?>">Categorías</a>
                <?php endif; ?>

                <?php if (puede('usuarios', 'ver')): ?>
                    <a class="nav__enlace <?= activo('usuarios') ?>" href="<?= e(url('usuarios')) ?>">Usuarios</a>
                <?php endif; ?>
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
    </header>

    <main class="contenedor contenido">
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

    <footer class="pie">
        <div class="contenedor">
            <?= e($appNombre) ?> · Proyecto de Arquitectura y Programación Web ·
            PHP <?= e(PHP_VERSION) ?>
        </div>
    </footer>
</body>
</html>
