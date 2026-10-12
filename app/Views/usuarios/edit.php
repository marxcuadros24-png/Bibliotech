<?php
/**
 * @var array<string, mixed> $usuario
 * @var int $prestamos
 * @var bool $esMiCuenta
 * @var list<string> $rolesValidos
 * @var list<string> $estados
 * @var string $accion
 * @var string $metodo
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Editar usuario</h1>
        <p class="subtitulo">
            <?= e($usuario['nombre']) ?> ·
            <?= e($prestamos) ?> préstamo(s) en su historial
        </p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('usuarios')) ?>">Volver</a>
</div>

<?php require __DIR__ . '/_form.php'; ?>
