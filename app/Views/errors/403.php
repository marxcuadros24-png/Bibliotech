<?php
/**
 * @var int $status
 * @var string $mensaje
 * @var string|null $detalle
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1><?= e($status) ?> · Acceso denegado</h1>
        <p class="subtitulo"><?= e($mensaje) ?></p>
    </div>
</div>

<div class="tarjeta">
    <p>
        Tu cuenta no tiene los permisos necesarios para ver esta sección. Si crees
        que es un error, pídele a un administrador que revise tu rol.
    </p>

    <div class="acciones-formulario">
        <a class="boton" href="<?= e(url()) ?>">Volver al panel</a>
    </div>
</div>

<?php if ($detalle !== null): ?>
    <div class="tarjeta">
        <h2>Detalle técnico</h2>
        <pre><?= e($detalle) ?></pre>
    </div>
<?php endif; ?>
