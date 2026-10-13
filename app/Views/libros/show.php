<?php
/**
 * Ficha de un libro.
 *
 * @var array<string, mixed> $libro
 */
$disponible = (int) $libro['stock_disponible'];
?>
<?php
// Los botones siguen los permisos del rol: el estudiante solo consulta.
$puedePrestar = puede('prestamos', 'crear') && $disponible > 0;
$puedeEditar = puede('libros', 'editar');
$puedeEliminar = puede('libros', 'eliminar');
?>
<div class="encabezado-seccion">
    <div>
        <h1><?= e($libro['titulo']) ?></h1>
        <p class="subtitulo"><?= e($libro['autor']) ?></p>
    </div>
    <div class="acciones-formulario">
        <?php if ($puedePrestar): ?>
            <a class="boton" href="<?= e(url('prestamos/crear?libro=' . $libro['id'])) ?>">Registrar préstamo</a>
        <?php endif; ?>

        <?php if ($puedeEditar): ?>
            <a class="boton boton--secundario" href="<?= e(url('libros/' . $libro['id'] . '/editar')) ?>">Editar</a>
        <?php endif; ?>

        <a class="boton boton--secundario" href="<?= e(url('libros')) ?>">Volver al catálogo</a>
    </div>
</div>

<div class="tarjeta">
    <h2>Ficha del libro</h2>

    <dl class="detalle-lista">
        <dt>Categoría</dt>
        <dd><?= $libro['categoria'] !== null ? e($libro['categoria']) : 'Sin categoría' ?></dd>

        <dt>Autor</dt>
        <dd><?= e($libro['autor']) ?></dd>

        <dt>Editorial</dt>
        <dd><?= $libro['editorial'] !== null ? e($libro['editorial']) : '—' ?></dd>

        <dt>Año de publicación</dt>
        <dd><?= $libro['anio'] !== null ? e($libro['anio']) : '—' ?></dd>

        <dt>ISBN</dt>
        <dd><?= $libro['isbn'] !== null ? e($libro['isbn']) : '—' ?></dd>

        <dt>Existencias</dt>
        <dd>
            <?php if ($disponible === 0): ?>
                <span class="etiqueta etiqueta--agotado">Sin ejemplares disponibles</span>
            <?php else: ?>
                <span class="etiqueta etiqueta--disponible">
                    <?= e($disponible) ?> de <?= e($libro['stock_total']) ?> disponibles
                </span>
            <?php endif; ?>
        </dd>

        <dt>Registrado</dt>
        <dd><?= e($libro['created_at']) ?></dd>

        <dt>Última actualización</dt>
        <dd><?= e($libro['updated_at']) ?></dd>
    </dl>
</div>

<?php if ($libro['sinopsis'] !== null && $libro['sinopsis'] !== ''): ?>
    <div class="tarjeta">
        <h2>Sinopsis</h2>
        <p><?= nl2br(e($libro['sinopsis'])) ?></p>
    </div>
<?php endif; ?>

<?php if ($libro['portada_url'] !== null && $libro['portada_url'] !== ''): ?>
    <div class="tarjeta">
        <h2>Portada</h2>
        <p><a href="<?= e($libro['portada_url']) ?>" target="_blank" rel="noopener">
            <?= e($libro['portada_url']) ?>
        </a></p>
    </div>
<?php endif; ?>

<?php if ($puedeEliminar): ?>
    <div class="tarjeta">
        <h2>Eliminar del catálogo</h2>
        <p class="subtitulo">
            Esta acción borra el registro de forma permanente. Los préstamos y reservas
            asociados dependen de las reglas de integridad de la base de datos.
        </p>

        <form method="POST" action="<?= e(url('libros/' . $libro['id'] . '/eliminar')) ?>"
              onsubmit="return confirm('¿Eliminar «<?= e($libro['titulo']) ?>» del catálogo?');">
            <?= csrf_field() ?>
            <button class="boton boton--peligro" type="submit">Eliminar libro</button>
        </form>
    </div>
<?php endif; ?>
