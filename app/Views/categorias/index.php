<?php
/**
 * Columnas: nombre, descripción, cuántos libros la usan y acciones.
 *
 * @var list<array<string, mixed>> $categorias
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Categorías</h1>
        <p class="subtitulo"><?= count($categorias) ?> categoría(s) registrada(s).</p>
    </div>
    <?php if (puede('categorias', 'crear')): ?>
        <a class="boton" href="<?= e(url('categorias/crear')) ?>">Nueva categoría</a>
    <?php endif; ?>
</div>

<?php if ($categorias === []): ?>
    <div class="vacio">No hay categorías registradas.</div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Libros</th>
                    <?php if (puede('categorias', 'editar')): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $categoria): ?>
                    <tr>
                        <td><strong><?= e($categoria['nombre']) ?></strong></td>
                        <td><?= $categoria['descripcion'] !== null && $categoria['descripcion'] !== ''
                            ? e($categoria['descripcion'])
                            : '—' ?></td>
                        <td>
                            <a href="<?= e(url('libros?buscar=' . urlencode((string) $categoria['nombre']))) ?>">
                                <?= e((int) $categoria['total_libros']) ?>
                            </a>
                        </td>
                        <?php if (puede('categorias', 'editar')): ?>
                            <td>
                                <div class="acciones-fila">
                                    <a class="boton boton--secundario boton--pequeno"
                                       href="<?= e(url('categorias/' . $categoria['id'] . '/editar')) ?>">Editar</a>

                                    <?php if (puede('categorias', 'eliminar')): ?>
                                        <form method="POST"
                                              action="<?= e(url('categorias/' . $categoria['id'] . '/eliminar')) ?>"
                                              onsubmit="return confirm('¿Eliminar la categoría «<?= e($categoria['nombre']) ?>»? Los libros quedarían sin categoría.');">
                                            <?= csrf_field() ?>
                                            <button class="boton boton--peligro boton--pequeno" type="submit">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
