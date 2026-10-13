<?php
/**
 * Reporte general de la biblioteca (solo administradores).
 *
 * Lo alimenta DashboardController::reporte() con los conteos totales.
 *
 * @var int   $totalLibros
 * @var int   $totalUsuarios
 * @var int   $prestamosActivos
 * @var int   $multasPendientes
 * @var float $montoPendiente
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Reporte general</h1>
        <p class="subtitulo">Conteos totales del sistema, solo para administradores.</p>
    </div>
</div>

<div class="indicadores">
    <div class="indicador">
        <div class="indicador__valor"><?= e($totalLibros) ?></div>
        <div class="indicador__etiqueta">Libros registrados</div>
    </div>
    <div class="indicador">
        <div class="indicador__valor"><?= e($totalUsuarios) ?></div>
        <div class="indicador__etiqueta">Usuarios</div>
    </div>
    <div class="indicador">
        <div class="indicador__valor"><?= e($prestamosActivos) ?></div>
        <div class="indicador__etiqueta">Préstamos activos</div>
    </div>
    <div class="indicador <?= $multasPendientes > 0 ? 'indicador--alerta' : '' ?>">
        <div class="indicador__valor"><?= e($multasPendientes) ?></div>
        <div class="indicador__etiqueta">Multas pendientes</div>
    </div>
</div>

<div class="tarjeta">
    <h2>Resumen</h2>
    <dl class="detalle-lista">
        <dt>Libros en el catálogo</dt>
        <dd><?= e($totalLibros) ?></dd>

        <dt>Usuarios registrados</dt>
        <dd><?= e($totalUsuarios) ?></dd>

        <dt>Préstamos activos</dt>
        <dd><?= e($prestamosActivos) ?></dd>

        <dt>Multas pendientes de cobro</dt>
        <dd>
            <?= e($multasPendientes) ?>
            <?php if ($multasPendientes > 0): ?>
                — <strong><?= e(moneda($montoPendiente)) ?></strong>
            <?php endif; ?>
        </dd>
    </dl>

    <div class="acciones-formulario">
        <a class="boton boton--secundario" href="<?= e(url('libros')) ?>">Ver catálogo</a>
        <a class="boton boton--secundario" href="<?= e(url('usuarios')) ?>">Ver usuarios</a>
        <a class="boton boton--secundario" href="<?= e(url('prestamos')) ?>">Ver préstamos</a>
        <a class="boton boton--secundario" href="<?= e(url('multas')) ?>">Ver multas</a>
    </div>
</div>
