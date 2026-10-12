<?php
/**
 * Panel de inicio.
 *
 * @var int $totalLibros
 * @var int $totalEjemplares
 * @var int $totalDisponibles
 * @var int $totalCategorias
 * @var int $totalUsuarios
 * @var list<array<string, mixed>> $primerosLibros
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Panel de la biblioteca</h1>
        <p class="subtitulo">Resumen del catálogo registrado en la base de datos.</p>
    </div>
    <a class="boton" href="<?= e(url('libros/crear')) ?>">Registrar libro</a>
</div>

<div class="indicadores">
    <div class="indicador">
        <div class="indicador__valor"><?= e($totalLibros) ?></div>
        <div class="indicador__etiqueta">Títulos</div>
    </div>
    <div class="indicador">
        <div class="indicador__valor"><?= e($totalEjemplares) ?></div>
        <div class="indicador__etiqueta">Ejemplares</div>
    </div>
    <div class="indicador">
        <div class="indicador__valor"><?= e($totalDisponibles) ?></div>
        <div class="indicador__etiqueta">Disponibles</div>
    </div>
    <div class="indicador">
        <div class="indicador__valor"><?= e($totalCategorias) ?></div>
        <div class="indicador__etiqueta">Categorías</div>
    </div>
    <div class="indicador">
        <div class="indicador__valor"><?= e($totalUsuarios) ?></div>
        <div class="indicador__etiqueta">Usuarios</div>
    </div>
</div>

<div class="tarjeta">
    <h2>Primeros títulos del catálogo</h2>

    <?php if ($primerosLibros === []): ?>
        <p class="subtitulo">Todavía no hay libros registrados.</p>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Categoría</th>
                        <th>Año</th>
                        <th>Disponibles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($primerosLibros as $libro): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('libros/' . $libro['id'])) ?>"><?= e($libro['titulo']) ?></a>
                            </td>
                            <td><?= e($libro['autor']) ?></td>
                            <td><?= $libro['categoria'] !== null ? e($libro['categoria']) : '—' ?></td>
                            <td><?= $libro['anio'] !== null ? e($libro['anio']) : '—' ?></td>
                            <td><?= e($libro['stock_disponible']) ?> / <?= e($libro['stock_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
