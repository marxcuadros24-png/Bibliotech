<?php
/**
 * @var array<string, mixed>       $libro
 * @var list<array<string, mixed>> $categorias
 * @var string $accion
 * @var string $metodo
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Editar libro</h1>
        <p class="subtitulo"><?= e($libro['titulo']) ?></p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('libros/' . $libro['id'])) ?>">
        Volver al detalle
    </a>
</div>

<?php require __DIR__ . '/_form.php'; ?>
