<?php
/**
 * Catálogo visible para visitantes anónimos.
 *
 * Lo alimenta CatalogoPublicoController::index() y se renderiza con el layout
 * público (sin navegación interna). Es de solo lectura: no hay botones de
 * acción, que además no tendrían sentido sin sesión.
 *
 * @var list<array<string, mixed>> $libros
 * @var string $busqueda
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Catálogo de libros</h1>
        <p class="subtitulo">
            <?= count($libros) ?> título(s) en el catálogo
            <?= $busqueda !== '' ? 'para «' . e($busqueda) . '»' : '' ?>
        </p>
    </div>
</div>

<div class="tarjeta">
    <form class="buscador" method="GET" action="<?= e(url('catalogo')) ?>">
        <input type="text" name="buscar" value="<?= e($busqueda) ?>"
               placeholder="Buscar por título, autor, ISBN, editorial o categoría">
        <button class="boton" type="submit">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a class="boton boton--secundario" href="<?= e(url('catalogo')) ?>">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($libros === []): ?>
    <div class="vacio">
        <?= $busqueda !== ''
            ? 'No se encontraron libros con ese criterio de búsqueda.'
            : 'El catálogo todavía está vacío.' ?>
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
                    <th>Disponibilidad</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td><?= e($libro['titulo']) ?></td>
                        <td><?= e($libro['autor']) ?></td>
                        <td><?= $libro['categoria'] !== null ? e($libro['categoria']) : '—' ?></td>
                        <td><?= $libro['editorial'] !== null ? e($libro['editorial']) : '—' ?></td>
                        <td><?= $libro['anio'] !== null ? e($libro['anio']) : '—' ?></td>
                        <td>
                            <?php if ((int) $libro['stock_disponible'] === 0): ?>
                                <span class="etiqueta etiqueta--agotado">Sin ejemplares</span>
                            <?php else: ?>
                                <span class="etiqueta etiqueta--disponible">
                                    <?= e($libro['stock_disponible']) ?> disponible(s)
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
