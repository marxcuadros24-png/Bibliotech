<?php
/**
 * @var array<string, mixed> $categoria
 * @var int $libros
 * @var string $accion
 * @var string $metodo
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Editar categoría</h1>
        <p class="subtitulo">
            <?= e($categoria['nombre']) ?> ·
            <?= e($libros) ?> libro(s) asociado(s)
        </p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('categorias')) ?>">Volver</a>
</div>

<?php require __DIR__ . '/_form.php'; ?>
