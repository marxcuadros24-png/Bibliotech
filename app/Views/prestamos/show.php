<?php
/**
 * @var array<string, mixed> $prestamo
 * @var list<array<string, mixed>> $multas
 * @var bool $abierto
 * @var int $diasHoy
 * @var float $multaHoy
 * @var float $tarifa
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Préstamo #<?= e($prestamo['id']) ?></h1>
        <p class="subtitulo">
            <?= e($prestamo['libro']) ?> · <?= e($prestamo['usuario']) ?>
        </p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('prestamos')) ?>">Volver</a>
</div>

<div class="tarjeta">
    <h2>Ficha del préstamo</h2>

    <dl class="detalle-lista">
        <dt>Estado</dt>
        <dd>
            <span class="etiqueta <?= e(clase_estado((string) $prestamo['estado'])) ?>">
                <?= e(nombre_estado((string) $prestamo['estado'])) ?>
            </span>
        </dd>

        <dt>Usuario</dt>
        <dd><?= e($prestamo['usuario']) ?> · <?= e($prestamo['usuario_email']) ?></dd>

        <dt>Libro</dt>
        <dd><?= e($prestamo['libro']) ?> · <?= e($prestamo['libro_autor']) ?></dd>

        <dt>ISBN</dt>
        <dd><?= $prestamo['libro_isbn'] !== null ? e($prestamo['libro_isbn']) : '—' ?></dd>

        <dt>Fecha del préstamo</dt>
        <dd><?= e(fecha($prestamo['fecha_prestamo'])) ?></dd>

        <dt>Devolución esperada</dt>
        <dd><?= e(fecha($prestamo['fecha_devolucion_esperada'])) ?></dd>

        <dt>Devolución real</dt>
        <dd><?= e(fecha($prestamo['fecha_devolucion_real'])) ?></dd>

        <dt>Atraso</dt>
        <dd>
            <?php if ($abierto && $diasHoy > 0): ?>
                <span class="etiqueta etiqueta--agotado">
                    <?= e($diasHoy) ?> día(s) · multa estimada <?= e(moneda($multaHoy)) ?>
                </span>
            <?php elseif ($abierto): ?>
                <span class="etiqueta etiqueta--disponible">Dentro del plazo</span>
            <?php else: ?>
                <?= e((int) $prestamo['dias_atraso']) > 0
                    ? e((int) $prestamo['dias_atraso']) . ' día(s) de mora'
                    : 'Sin mora' ?>
            <?php endif; ?>
        </dd>
    </dl>
</div>

<?php if ($abierto && puede('prestamos', 'devolver')): ?>
    <div class="tarjeta">
        <h2>Registrar devolución</h2>

        <p class="subtitulo">
            Al confirmar se repone el ejemplar en el inventario. Si la devolución
            queda después del <?= e(fecha($prestamo['fecha_devolucion_esperada'])) ?>,
            el sistema genera la multa automáticamente a
            <?= e(moneda($tarifa)) ?> por día de mora.
        </p>

        <form method="POST" action="<?= e(url('prestamos/' . $prestamo['id'] . '/devolver')) ?>">
            <?= csrf_field() ?>

            <div class="campo">
                <label for="fecha_devolucion_real">Fecha de devolución</label>
                <input type="date" id="fecha_devolucion_real" name="fecha_devolucion_real"
                       value="<?= e(date('Y-m-d')) ?>">
                <span class="campo__ayuda">Por defecto hoy.</span>
            </div>

            <button class="boton" type="submit">Confirmar devolución</button>
        </form>
    </div>
<?php endif; ?>

<div class="tarjeta">
    <h2>Multas de este préstamo</h2>

    <?php if ($multas === []): ?>
        <p class="subtitulo">Este préstamo no ha generado multas.</p>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>Generada</th>
                        <th>Días de mora</th>
                        <th>Tarifa</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Pagada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($multas as $multa): ?>
                        <tr>
                            <td><?= e(fecha($multa['fecha_generacion'], 'd/m/Y H:i')) ?></td>
                            <td><?= e((int) $multa['dias_mora']) ?></td>
                            <td><?= e(moneda($multa['tarifa_diaria'])) ?></td>
                            <td><strong><?= e(moneda($multa['monto'])) ?></strong></td>
                            <td>
                                <span class="etiqueta <?= e(clase_estado((string) $multa['estado'])) ?>">
                                    <?= e(nombre_estado((string) $multa['estado'])) ?>
                                </span>
                            </td>
                            <td><?= e(fecha($multa['fecha_pago'], 'd/m/Y H:i')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
