<?php
/**
 * Layout de páginas públicas (con o sin sesión).
 *
 * El menú se adapta al visitante: siempre muestra Inicio, Catálogo y Admin,
 * y añade el nombre del usuario con "Salir" cuando hay sesión o "Ingresar"
 * cuando no la hay. Así la portada sirve igual para anónimos y autenticados.
 *
 * @var string $title
 * @var string $content
 */
$titulo = $title ?? 'Inicio';
$appNombre = (string) env('APP_NAME', 'Bibliotech');
$exito = mensaje_flash('exito');
$error = mensaje_flash('error');
$usuario = App\Core\Auth::usuario();

// Marca "Inicio" como activo solo en la raíz: activo() compara por subcadena
// y no sirve para la ruta vacía.
$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = App\Core\Request::baseUrl();

if ($base !== '' && str_starts_with($rutaActual, $base)) {
    $rutaActual = substr($rutaActual, strlen($base));
}

$inicioActivo = ('/' . trim($rutaActual, '/')) === '/' ? 'activo' : '';
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

            <div class="cabecera__derecha">
                <nav class="nav">
                    <a class="nav__enlace <?= e($inicioActivo) ?>" href="<?= e(url()) ?>">Inicio</a>
                    <a class="nav__enlace <?= activo('catalogo') ?>" href="<?= e(url('catalogo')) ?>">Catálogo</a>
                    <a class="nav__enlace <?= activo('panel') ?>" href="<?= e(url('panel')) ?>">Admin</a>
                </nav>

                <div class="sesion">
                    <?php if ($usuario !== null): ?>
                        <span class="sesion__datos">
                            <span class="sesion__nombre"><?= e($usuario['nombre'] ?? '') ?></span>
                            <span class="sesion__rol"><?= e(ucfirst((string) ($usuario['rol'] ?? ''))) ?></span>
                        </span>

                        <form method="POST" action="<?= e(url('logout')) ?>">
                            <?= csrf_field() ?>
                            <button class="boton boton--sesion" type="submit">Salir</button>
                        </form>
                    <?php else: ?>
                        <a class="boton" href="<?= e(url('login')) ?>">Ingresar</a>
                    <?php endif; ?>
                </div>
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

        <?= $content ?>
    </main>

    <footer class="pie">
        <div class="contenedor">
            <?= e($appNombre) ?> · Sistema de gestión de biblioteca
        </div>
    </footer>
</body>
</html>
