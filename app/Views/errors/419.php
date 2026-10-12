<?php
/**
 * @var int $status
 * @var string $mensaje
 * @var string|null $detalle
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1><?= e($status) ?> · Sesión expirada</h1>
        <p class="subtitulo"><?= e($mensaje) ?></p>
    </div>
</div>

<div class="tarjeta">
    <p>
        Por seguridad, el formulario dejó de ser válido. Vuelve a abrirlo y envíalo
        de nuevo.
    </p>

    <div class="acciones-formulario">
        <a class="boton" href="<?= e(url('libros')) ?>">Ir al catálogo</a>
        <a class="boton boton--secundario" href="<?= e(url()) ?>">Ir al inicio</a>
    </div>
</div>

<?php if ($detalle !== null): ?>
    <div class="tarjeta">
        <h2>Detalle técnico</h2>
        <pre><?= e($detalle) ?></pre>
    </div>
<?php endif; ?>
