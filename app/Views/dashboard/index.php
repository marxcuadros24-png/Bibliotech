<?php
/**
 * Panel de inicio. Recibe dos juegos de datos distintos según el rol, por eso
 * la vista se divide en dos bloques excluyentes.
 *
 * @var bool $esPersonal
 */
?>
<?php if ($esPersonal): ?>
    <?php
    /**
     * @var int $totalLibros
     * @var int $disponibles
     * @var int $ejemplares
     * @var array<string, int> $prestamos
     * @var array<string, array{cantidad:int, total:float}> $multas
     * @var int $categorias
     * @var int $usuarios
     * @var array<string, int> $roles
     * @var list<array<string, mixed>> $porVencer
     * @var list<array<string, mixed>> $atrasados
     */
    ?>
    <div class="encabezado-seccion">
        <div>
            <h1>Panel de la biblioteca</h1>
            <p class="subtitulo">Estado general del catálogo y los préstamos.</p>
        </div>
        <div class="acciones-formulario">
            <a class="boton" href="<?= e(url('prestamos/crear')) ?>">Registrar préstamo</a>
            <a class="boton boton--secundario" href="<?= e(url('libros/crear')) ?>">Registrar libro</a>
        </div>
    </div>

    <div class="indicadores">
        <div class="indicador">
            <div class="indicador__valor"><?= e($totalLibros) ?></div>
            <div class="indicador__etiqueta">Títulos</div>
        </div>
        <div class="indicador">
            <div class="indicador__valor"><?= e($disponibles) ?></div>
            <div class="indicador__etiqueta">Ejemplares disponibles</div>
        </div>
        <div class="indicador">
            <div class="indicador__valor"><?= e($prestamos['activo']) ?></div>
            <div class="indicador__etiqueta">Préstamos activos</div>
        </div>
        <div class="indicador indicador--alerta">
            <div class="indicador__valor"><?= e($prestamos['vencido']) ?></div>
            <div class="indicador__etiqueta">Préstamos vencidos</div>
        </div>
        <div class="indicador indicador--alerta">
            <div class="indicador__valor"><?= e(moneda($multas['pendiente']['total'])) ?></div>
            <div class="indicador__etiqueta">
                Multas por cobrar (<?= e($multas['pendiente']['cantidad']) ?>)
            </div>
        </div>
        <div class="indicador">
            <div class="indicador__valor"><?= e($ejemplares) ?></div>
            <div class="indicador__etiqueta">Ejemplares en total</div>
        </div>
    </div>

    <div class="rejilla-dos">
        <div class="tarjeta">
            <h2>Préstamos vencidos</h2>

            <?php if ($atrasados === []): ?>
                <p class="subtitulo">Ningún préstamo está atrasado.</p>
            <?php else: ?>
                <div class="tabla-envoltura">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Libro</th>
                                <th>Debía devolver</th>
                                <th>Atraso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atrasados as $prestamo): ?>
                                <tr>
                                    <td><a href="<?= e(url('prestamos/' . $prestamo['id'])) ?>"><?= e($prestamo['usuario']) ?></a></td>
                                    <td><?= e($prestamo['libro']) ?></td>
                                    <td><?= e(fecha($prestamo['fecha_devolucion_esperada'])) ?></td>
                                    <td><span class="etiqueta etiqueta--agotado"><?= e((int) $prestamo['dias_atraso']) ?> día(s)</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="tarjeta">
            <h2>Próximos a vencer</h2>

            <?php if ($porVencer === []): ?>
                <p class="subtitulo">No hay préstamos activos.</p>
            <?php else: ?>
                <div class="tabla-envoltura">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Libro</th>
                                <th>Devolución</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($porVencer as $prestamo): ?>
                                <tr>
                                    <td><a href="<?= e(url('prestamos/' . $prestamo['id'])) ?>"><?= e($prestamo['usuario']) ?></a></td>
                                    <td><?= e($prestamo['libro']) ?></td>
                                    <td><?= e(fecha($prestamo['fecha_devolucion_esperada'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Composición de la biblioteca</h2>
        <dl class="detalle-lista">
            <dt>Categorías registradas</dt>
            <dd><?= e($categorias) ?></dd>

            <dt>Usuarios en total</dt>
            <dd>
                <?= e($usuarios) ?>
                <span class="subtitulo">
                    (<?= e($roles['administrador']) ?> admin,
                    <?= e($roles['bibliotecario']) ?> bibliotecario(s),
                    <?= e($roles['estudiante']) ?> estudiante(s))
                </span>
            </dd>

            <dt>Préstamos devueltos</dt>
            <dd><?= e($prestamos['devuelto']) ?></dd>

            <dt>Multas cobradas</dt>
            <dd><?= e(moneda($multas['pagada']['total'])) ?> en <?= e($multas['pagada']['cantidad']) ?> multa(s)</dd>
        </dl>
    </div>

<?php else: ?>
    <?php
    /**
     * @var list<array<string, mixed>> $misPrestamos
     * @var list<array<string, mixed>> $miHistorial
     * @var list<array<string, mixed>> $misMultas
     * @var float $totalPendiente
     */
    ?>
    <div class="encabezado-seccion">
        <div>
            <h1>Mi panel</h1>
            <p class="subtitulo">Tus préstamos y multas.</p>
        </div>
        <a class="boton boton--secundario" href="<?= e(url('libros')) ?>">Explorar el catálogo</a>
    </div>

    <div class="indicadores">
        <div class="indicador">
            <div class="indicador__valor"><?= e(count($misPrestamos)) ?></div>
            <div class="indicador__etiqueta">Préstamos vigentes</div>
        </div>
        <div class="indicador <?= $totalPendiente > 0 ? 'indicador--alerta' : '' ?>">
            <div class="indicador__valor"><?= e(moneda($totalPendiente)) ?></div>
            <div class="indicador__etiqueta">Multas por pagar</div>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Préstamos vigentes</h2>

        <?php if ($misPrestamos === []): ?>
            <p class="subtitulo">No tienes préstamos pendientes de devolución.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table>
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Autor</th>
                            <th>Debes devolver</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($misPrestamos as $prestamo): ?>
                            <tr>
                                <td><?= e($prestamo['libro']) ?></td>
                                <td><?= e($prestamo['libro_autor']) ?></td>
                                <td><?= e(fecha($prestamo['fecha_devolucion_esperada'])) ?></td>
                                <td>
                                    <?php if ((int) $prestamo['dias_atraso'] > 0): ?>
                                        <span class="etiqueta etiqueta--agotado">
                                            <?= e((int) $prestamo['dias_atraso']) ?> día(s) de atraso
                                        </span>
                                    <?php else: ?>
                                        <span class="etiqueta etiqueta--disponible">En plazo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h2>Mis multas pendientes</h2>

        <?php if ($misMultas === []): ?>
            <p class="subtitulo">No tienes multas por pagar.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table>
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Días de mora</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($misMultas as $multa): ?>
                            <tr>
                                <td><?= e($multa['libro']) ?></td>
                                <td><?= e((int) $multa['dias_mora']) ?> × <?= e(moneda($multa['tarifa_diaria'])) ?></td>
                                <td><strong><?= e(moneda($multa['monto'])) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h2>Historial reciente</h2>

        <?php if ($miHistorial === []): ?>
            <p class="subtitulo">Todavía no tienes préstamos registrados.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table>
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Prestado</th>
                            <th>Devuelto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($miHistorial as $prestamo): ?>
                            <tr>
                                <td><?= e($prestamo['libro']) ?></td>
                                <td><?= e(fecha($prestamo['fecha_prestamo'])) ?></td>
                                <td><?= e(fecha($prestamo['fecha_devolucion_real'])) ?></td>
                                <td><span class="etiqueta"><?= e(nombre_estado($prestamo['estado'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
