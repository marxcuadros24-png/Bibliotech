<?php
/**
 * Catálogo de libros.
 *
 * La columna de acciones se arma con los permisos del rol: un estudiante solo
 * consulta, así que no ve botones que le responderían 403 (cada ruta vuelve a
 * validar sus permisos; esto solo evita ofrecer algo inaccesible).
 *
 * @var list<array<string, mixed>> $libros
 * @var string $busqueda
 */
$puedePrestar = puede('prestamos', 'crear');
$puedeEditar = puede('libros', 'editar');
$puedeEliminar = puede('libros', 'eliminar');
$mostrarAcciones = $puedePrestar || $puedeEditar || $puedeEliminar;
?>
<div class="encabezado-seccion">
    <div>
        <h1>Catálogo de libros</h1>
        <p class="subtitulo">
            <?= count($libros) ?> título(s) encontrado(s)
            <?= $busqueda !== '' ? ' para «' . e($busqueda) . '»' : '' ?>
        </p>
    </div>
    <?php if (puede('libros', 'crear')): ?>
        <a class="boton" href="<?= e(url('libros/crear')) ?>">Registrar libro</a>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <form class="buscador" method="GET" action="<?= e(url('libros')) ?>">
        <input type="text" name="buscar" value="<?= e($busqueda) ?>"
               placeholder="Buscar por título, autor, ISBN, editorial o categoría">
        <button class="boton" type="submit">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a class="boton boton--secundario" href="<?= e(url('libros')) ?>">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($libros === []): ?>
    <div class="vacio">
        <?= $busqueda !== ''
            ? 'No se encontraron libros con ese criterio de búsqueda.'
            : 'El catálogo está vacío. Registra el primer libro para empezar.' ?>
    </div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Categoría</th>
                    <th>Editorial</th>
                    <th>Año</th>
                    <th>Stock</th>
                    <?php if ($mostrarAcciones): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libros as $libro): ?>
                    <?php $disponibles = (int) $libro['stock_disponible']; ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('libros/' . $libro['id'])) ?>"><?= e($libro['titulo']) ?></a>
                        </td>
                        <td><?= e($libro['autor']) ?></td>
                        <td><?= $libro['categoria'] !== null ? e($libro['categoria']) : '—' ?></td>
                        <td><?= $libro['editorial'] !== null ? e($libro['editorial']) : '—' ?></td>
                        <td><?= $libro['anio'] !== null ? e($libro['anio']) : '—' ?></td>
                        <td>
                            <?php if ($disponibles === 0): ?>
                                <span class="etiqueta etiqueta--agotado">Sin ejemplares</span>
                            <?php else: ?>
                                <span class="etiqueta etiqueta--disponible">
                                    <?= e($libro['stock_disponible']) ?> de <?= e($libro['stock_total']) ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <?php if ($mostrarAcciones): ?>
                            <td>
                                <div class="acciones-fila">
                                    <?php if ($puedePrestar && $disponibles > 0): ?>
                                        <a class="boton boton--pequeno"
                                           href="<?= e(url('prestamos/crear?libro=' . $libro['id'])) ?>">Prestar</a>
                                    <?php endif; ?>

                                    <?php if ($puedeEditar): ?>
                                        <a class="boton boton--secundario boton--pequeno"
                                           href="<?= e(url('libros/' . $libro['id'] . '/editar')) ?>">Editar</a>
                                    <?php endif; ?>

                                    <?php if ($puedeEliminar): ?>
                                        <form method="POST"
                                              action="<?= e(url('libros/' . $libro['id'] . '/eliminar')) ?>"
                                              onsubmit="return confirm('¿Eliminar «<?= e($libro['titulo']) ?>» del catálogo?');">
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
