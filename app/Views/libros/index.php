<?php
/**
 * Catálogo de libros.
 *
 * @var list<array<string, mixed>> $libros
 * @var string $busqueda
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Catálogo de libros</h1>
        <p class="subtitulo">
            <?= count($libros) ?> título(s) encontrado(s)
            <?= $busqueda !== '' ? ' para «' . e($busqueda) . '»' : '' ?>
        </p>
    </div>
    <a class="boton" href="<?= e(url('libros/crear')) ?>">Registrar libro</a>
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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('libros/' . $libro['id'])) ?>"><?= e($libro['titulo']) ?></a>
                        </td>
                        <td><?= e($libro['autor']) ?></td>
                        <td><?= $libro['categoria'] !== null ? e($libro['categoria']) : '—' ?></td>
                        <td><?= $libro['editorial'] !== null ? e($libro['editorial']) : '—' ?></td>
                        <td><?= $libro['anio'] !== null ? e($libro['anio']) : '—' ?></td>
                        <td>
                            <?php if ((int) $libro['stock_disponible'] === 0): ?>
                                <span class="etiqueta etiqueta--agotado">Sin ejemplares</span>
                            <?php else: ?>
                                <span class="etiqueta etiqueta--disponible">
                                    <?= e($libro['stock_disponible']) ?> de <?= e($libro['stock_total']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="boton boton--secundario boton--pequeno"
                               href="<?= e(url('libros/' . $libro['id'] . '/editar')) ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
