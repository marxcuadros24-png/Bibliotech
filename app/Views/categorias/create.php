<?php
/**
 * @var array<string, mixed> $categoria
 * @var string $accion
 * @var string $metodo
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Nueva categoría</h1>
        <p class="subtitulo">Agrupa los libros por tema.</p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('categorias')) ?>">Volver</a>
</div>

<?php require __DIR__ . '/_form.php'; ?>
