<?php
/**
 * @var list<array<string, mixed>> $multas
 * @var string $estado
 * @var string $busqueda
 * @var array<string, array{cantidad:int, total:float}> $resumen
 * @var float $tarifa
 * @var bool $soloPropios
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1><?= $soloPropios ? 'Mis multas' : 'Multas' ?></h1>
        <p class="subtitulo">
            Generadas automáticamente al devolver con atraso, a
            <?= e(moneda($tarifa)) ?> por día de mora.
        </p>
    </div>
</div>

<?php if (!$soloPropios): ?>
    <div class="indicadores">
        <div class="indicador indicador--alerta">
            <div class="indicador__valor"><?= e(moneda($resumen['pendiente']['total'])) ?></div>
            <div class="indicador__etiqueta">
                Por cobrar (<?= e($resumen['pendiente']['cantidad']) ?>)
            </div>
        </div>
        <div class="indicador">
            <div class="indicador__valor"><?= e(moneda($resumen['pagada']['total'])) ?></div>
            <div class="indicador__etiqueta">
                Cobrado (<?= e($resumen['pagada']['cantidad']) ?>)
            </div>
        </div>
        <div class="indicador">
            <div class="indicador__valor"><?= e(moneda($resumen['condonada']['total'])) ?></div>
            <div class="indicador__etiqueta">
                Condonado (<?= e($resumen['condonada']['cantidad']) ?>)
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="tarjeta">
    <form class="buscador" method="GET" action="<?= e(url('multas')) ?>">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (['pendiente', 'pagada', 'condonada'] as $opcion): ?>
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
            <a class="boton boton--secundario" href="<?= e(url('multas')) ?>">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($multas === []): ?>
    <div class="vacio">
        <?= $soloPropios
            ? 'No tienes multas registradas.'
            : 'No hay multas que coincidan con el filtro.' ?>
    </div>
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
                    <th>Devolución esperada</th>
                    <th>Cálculo</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Generada</th>
                    <?php if (!$soloPropios): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($multas as $multa): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('prestamos/' . $multa['id_prestamo'])) ?>">
                                #<?= e($multa['id_prestamo']) ?>
                            </a>
                        </td>

                        <?php if (!$soloPropios): ?>
                            <td>
                                <?= e($multa['usuario']) ?>
                                <br><span class="subtitulo"><?= e($multa['usuario_email']) ?></span>
                            </td>
                        <?php endif; ?>

                        <td><?= e($multa['libro']) ?></td>
                        <td><?= e(fecha($multa['fecha_devolucion_esperada'])) ?></td>

                        <td>
                            <span class="subtitulo">
                                <?= e((int) $multa['dias_mora']) ?> día(s) ×
                                <?= e(moneda($multa['tarifa_diaria'])) ?>
                            </span>
                        </td>

                        <td><strong><?= e(moneda($multa['monto'])) ?></strong></td>

                        <td>
                            <span class="etiqueta <?= e(clase_estado((string) $multa['estado'])) ?>">
                                <?= e(nombre_estado((string) $multa['estado'])) ?>
                            </span>
                        </td>

                        <td><?= e(fecha($multa['fecha_generacion'])) ?></td>

                        <?php if (!$soloPropios): ?>
                            <td>
                                <?php if ($multa['estado'] === 'pendiente'): ?>
                                    <div class="acciones-fila">
                                        <?php if (puede('multas', 'pagar')): ?>
                                            <form method="POST" action="<?= e(url('multas/' . $multa['id'] . '/pagar')) ?>">
                                                <?= csrf_field() ?>
                                                <button class="boton boton--pequeno" type="submit">Pagar</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (puede('multas', 'condonar')): ?>
                                            <form method="POST" action="<?= e(url('multas/' . $multa['id'] . '/condonar')) ?>"
                                                  onsubmit="return confirm('¿Condonar la multa de <?= e(moneda($multa['monto'])) ?>?');">
                                                <?= csrf_field() ?>
                                                <button class="boton boton--secundario boton--pequeno" type="submit">Condonar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?= e(fecha($multa['fecha_pago'])) ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
