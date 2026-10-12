<?php
/**
 * @var int $status
 * @var string $mensaje
 * @var string|null $detalle
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1><?= e($status) ?> · Error en el servidor</h1>
        <p class="subtitulo"><?= e($mensaje) ?></p>
    </div>
</div>

<div class="tarjeta">
    <p>
        Si el problema persiste, revisa el registro de errores y confirma que la base
        de datos esté disponible y migrada.
    </p>

    <div class="acciones-formulario">
        <a class="boton" href="<?= e(url()) ?>">Volver al inicio</a>
    </div>
</div>

<?php if ($detalle !== null): ?>
    <div class="tarjeta">
        <h2>Detalle técnico</h2>
        <pre><?= e($detalle) ?></pre>
    </div>
<?php endif; ?>
