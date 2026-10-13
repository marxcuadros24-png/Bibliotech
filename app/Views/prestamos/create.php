<?php
/**
 * @var list<array<string, mixed>> $libros
 * @var list<array<string, mixed>> $usuarios
 * @var int $diasPorDefecto
 * @var string $fechaSugerida
 * @var int $idUsuarioSugerido
 * @var int $idLibroSugerido
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Registrar préstamo</h1>
        <p class="subtitulo">
            Al guardar se descuenta un ejemplar del inventario automáticamente.
        </p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('prestamos')) ?>">Volver</a>
</div>

<?php if ($libros === []): ?>
    <div class="vacio">
        No hay ejemplares disponibles para prestar.
        <a href="<?= e(url('libros')) ?>">Revisa el inventario</a> o registra más stock.
    </div>

    <div class="acciones-formulario">
        <a class="boton boton--secundario" href="<?= e(url('prestamos')) ?>">Volver</a>
    </div>
<?php else: ?>
    <form method="POST" action="<?= e(url('prestamos')) ?>">
        <?= csrf_field() ?>

        <div class="tarjeta">
            <h2>Datos del préstamo</h2>

            <div class="<?= errores('id_usuario') === [] ? 'campo' : 'campo campo--error' ?>">
                <label for="id_usuario">Usuario que lo recibe *</label>
                <select id="id_usuario" name="id_usuario" required>
                    <option value="">Selecciona un usuario…</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= e($usuario['id']) ?>"
                            <?= (string) old('id_usuario', (string) $idUsuarioSugerido) === (string) $usuario['id'] ? 'selected' : '' ?>>
                            <?= e($usuario['nombre']) ?> — <?= e($usuario['email']) ?>
                            (<?= e(ucfirst((string) $usuario['rol'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="campo__ayuda">Solo aparecen las cuentas activas.</span>
                <?php foreach (errores('id_usuario') as $mensaje): ?>
                    <span class="campo__error"><?= e($mensaje) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="<?= errores('id_libro') === [] ? 'campo' : 'campo campo--error' ?>">
                <label for="id_libro">Libro *</label>
                <select id="id_libro" name="id_libro" required>
                    <option value="">Selecciona un libro…</option>
                    <?php foreach ($libros as $libro): ?>
                        <option value="<?= e($libro['id']) ?>"
                            <?= (string) old('id_libro', (string) $idLibroSugerido) === (string) $libro['id'] ? 'selected' : '' ?>>
                            <?= e($libro['titulo']) ?> — <?= e($libro['autor']) ?>
                            (<?= e($libro['stock_disponible']) ?> disponible(s))
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="campo__ayuda">
                    Solo se listan libros con al menos un ejemplar en estantería.
                </span>
                <?php foreach (errores('id_libro') as $mensaje): ?>
                    <span class="campo__error"><?= e($mensaje) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="<?= errores('fecha_devolucion_esperada') === [] ? 'campo' : 'campo campo--error' ?>">
                <label for="fecha_devolucion_esperada">Fecha de devolución esperada</label>
                <input type="date" id="fecha_devolucion_esperada" name="fecha_devolucion_esperada"
                       value="<?= e((string) old('fecha_devolucion_esperada', $fechaSugerida)) ?>">
                <span class="campo__ayuda">
                    Por defecto <?= e($diasPorDefecto) ?> días desde hoy (DIAS_PRESTAMO en el .env).
                    Puedes cambiarla para este préstamo.
                </span>
                <?php foreach (errores('fecha_devolucion_esperada') as $mensaje): ?>
                    <span class="campo__error"><?= e($mensaje) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="acciones-formulario">
            <button class="boton" type="submit">Registrar préstamo</button>
            <a class="boton boton--secundario" href="<?= e(url('prestamos')) ?>">Cancelar</a>
        </div>
    </form>
<?php endif; ?>
