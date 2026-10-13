<?php
/**
 * Layout reducido para el login: sin navegación, porque todavía no hay sesión.
 *
 * Aun así deja un enlace de vuelta a la portada ("← Volver al inicio"), para que
 * un visitante anónimo no quede encerrado en el formulario: la portada pública
 * en '/' siempre está a un clic.
 *
 * @var string      $title
 * @var string      $content
 */
$titulo = $title ?? 'Acceso';
$appNombre = (string) env('APP_NAME', 'Bibliotech');
$exito = mensaje_flash('exito');
$error = mensaje_flash('error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> · <?= e($appNombre) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="cuerpo-acceso">
    <main class="acceso">
        <div class="acceso__marca">
            <span class="marca__icono">BT</span>
            <h1 class="acceso__titulo"><?= e($appNombre) ?></h1>
            <p class="subtitulo">Sistema de gestión de biblioteca</p>
        </div>

        <?php if ($exito !== null): ?>
            <div class="aviso aviso--exito"><?= e($exito) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="aviso aviso--error"><?= e($error) ?></div>
        <?php endif; ?>

        <?= $content ?>

        <p class="acceso__pie">
            <a href="<?= e(url()) ?>">← Volver al inicio</a>
        </p>
    </main>
</body>
</html>
