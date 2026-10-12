<?php
/**
 * @var list<array<string, mixed>> $prestamos
 * @var string $estado
 * @var string $busqueda
 * @var array<string, int> $conteo
 * @var bool $soloPropios
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1><?= $soloPropios ? 'Mis préstamos' : 'Préstamos' ?></h1>
        <p class="subtitulo">
            <?= count($prestamos) ?> registro(s)
            <?= !$soloPropios ? ' · ' . e($conteo['activo']) . ' activo(s) · ' . e($conteo['vencido']) . ' vencido(s)' : '' ?>
        </p>
    </div>
    <?php if (puede('prestamos', 'crear')): ?>
        <a class="boton" href="<?= e(url('prestamos/crear')) ?>">Registrar préstamo</a>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <form class="buscador" method="GET" action="<?= e(url('prestamos')) ?>">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (['activo', 'vencido', 'devuelto', 'cancelado'] as $opcion): ?>
                <option value="<?= e($opcion) ?>" <?= $estado === $opcion ? 'selected' : '' ?>>
                    <?= e(nombre_estado($opcion)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (!$soloPropios): ?>
            <input type="text" name="buscar" value="<?= e($busqueda) ?>"
                   placeholder="Buscar por usuario o libro">
        <?php endif; ?>

        <button class="boton" type="submit">Filtrar</button>
        <?php if ($estado !== '' || $busqueda !== ''): ?>
            <a class="boton boton--secundario" href="<?= e(url('prestamos')) ?>">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($prestamos === []): ?>
    <div class="vacio">No hay préstamos que coincidan con el filtro.</div>
<?php else: ?>
    <div class="tabla-envoltura">
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <?php if (!$soloPropios): ?>
                        <th>Usuario</th>
                    <?php endif; ?>
                    <th>Libro</th>
                    <th>Prestado</th>
                    <th>Debe devolver</th>
                    <th>Devuelto</th>
                    <th>Estado</th>
                    <th>Multas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestamos as $prestamo): ?>
                    <tr>
                        <td>#<?= e($prestamo['id']) ?></td>

                        <?php if (!$soloPropios): ?>
                            <td>
                                <?= e($prestamo['usuario']) ?>
                                <br><span class="subtitulo"><?= e($prestamo['usuario_email']) ?></span>
                            </td>
                        <?php endif; ?>

                        <td>
                            <?= e($prestamo['libro']) ?>
                            <br><span class="subtitulo"><?= e($prestamo['libro_autor']) ?></span>
                        </td>

                        <td><?= e(fecha($prestamo['fecha_prestamo'])) ?></td>
                        <td><?= e(fecha($prestamo['fecha_devolucion_esperada'])) ?></td>
                        <td><?= e(fecha($prestamo['fecha_devolucion_real'])) ?></td>

                        <td>
                            <span class="etiqueta <?= e(clase_estado((string) $prestamo['estado'])) ?>">
                                <?= e(nombre_estado((string) $prestamo['estado'])) ?>
                            </span>
                            <?php if ((int) $prestamo['dias_atraso'] > 0 && $prestamo['estado'] !== 'devuelto'): ?>
                                <br><span class="subtitulo"><?= e((int) $prestamo['dias_atraso']) ?> día(s)</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= (int) $prestamo['total_multas'] > 0
                                ? '<span class="etiqueta etiqueta--agotado">' . e((int) $prestamo['total_multas']) . '</span>'
                                : '—' ?>
                        </td>

                        <td>
                            <a class="boton boton--secundario boton--pequeno"
                               href="<?= e(url('prestamos/' . $prestamo['id'])) ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
