<?php
/**
 * Layout principal.
 *
 * Variables disponibles: $title, $content y los mensajes flash de la sesión.
 *
 * @var string      $title
 * @var string      $content
 * @var string|null $mensaje
 */
$titulo = $title ?? 'Bibliotech';
$exito = mensaje_flash('exito');
$error = mensaje_flash('error');
$appNombre = (string) env('APP_NAME', 'Bibliotech');
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
                <a class="nav__enlace <?= activo('libros') ?>" href="<?= e(url('libros')) ?>">Catálogo</a>
                <a class="nav__enlace" href="<?= e(url('libros/crear')) ?>">Registrar libro</a>
                <a class="nav__enlace" href="<?= e(url('api/libros')) ?>">API</a>
            </nav>
        </div>
    </header>

    <main class="contenedor contenido">
        <?php if ($exito !== null): ?>
            <div class="aviso aviso--exito"><?= e($exito) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="aviso aviso--error"><?= e($error) ?></div>
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
