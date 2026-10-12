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
        <h1>Registrar libro</h1>
        <p class="subtitulo">Los campos marcados con * son obligatorios.</p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('libros')) ?>">Volver al catálogo</a>
</div>

<?php require __DIR__ . '/_form.php'; ?>
