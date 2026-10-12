<?php
/**
 * Formulario compartido por las vistas de crear y editar.
 *
 * @var array<string, mixed>       $libro
 * @var list<array<string, mixed>> $categorias
 * @var string $accion URL de destino del POST
 * @var string $metodo 'crear' o 'editar', solo cambia los textos
 */
$esEdicion = ($metodo ?? 'crear') === 'editar';

/** Valor mostrado: lo que se escribió antes (si hubo error) o el dato guardado. */
$valor = static function (string $campo) use ($libro): string {
    $guardado = $libro[$campo] ?? '';

    return (string) old($campo, $guardado === null ? '' : $guardado);
};

/** Marca el campo con borde rojo si tiene errores. */
$clase = static function (string $campo): string {
    return errores($campo) === [] ? 'campo' : 'campo campo--error';
};

/** Lista de mensajes de error de un campo. */
$mensajes = static function (string $campo): void {
    foreach (errores($campo) as $mensaje) {
        echo '<span class="campo__error">' . e($mensaje) . '</span>';
    }
};
?>
<form method="POST" action="<?= e($accion) ?>">
    <?= csrf_field() ?>

    <div class="tarjeta">
        <h2>Datos del libro</h2>

        <div class="<?= $clase('titulo') ?>">
            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" maxlength="255"
                   value="<?= e($valor('titulo')) ?>" required>
            <?php $mensajes('titulo'); ?>
        </div>

        <div class="<?= $clase('autor') ?>">
            <label for="autor">Autor *</label>
            <input type="text" id="autor" name="autor" maxlength="180"
                   value="<?= e($valor('autor')) ?>" required>
            <span class="campo__ayuda">Tal como aparece en la portada. Se guarda como texto.</span>
            <?php $mensajes('autor'); ?>
        </div>

        <div class="fila-campos">
            <div class="<?= $clase('isbn') ?>">
                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" name="isbn" maxlength="20"
                       value="<?= e($valor('isbn')) ?>">
                <span class="campo__ayuda">Opcional y único en el catálogo.</span>
                <?php $mensajes('isbn'); ?>
            </div>

            <div class="<?= $clase('id_categoria') ?>">
                <label for="id_categoria">Categoría</label>
                <select id="id_categoria" name="id_categoria">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= e($categoria['id']) ?>"
                            <?= (string) $valor('id_categoria') === (string) $categoria['id'] ? 'selected' : '' ?>>
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php $mensajes('id_categoria'); ?>
            </div>
        </div>

        <div class="fila-campos">
            <div class="<?= $clase('editorial') ?>">
                <label for="editorial">Editorial</label>
                <input type="text" id="editorial" name="editorial" maxlength="150"
                       value="<?= e($valor('editorial')) ?>">
                <?php $mensajes('editorial'); ?>
            </div>

            <div class="<?= $clase('anio') ?>">
                <label for="anio">Año de publicación</label>
                <input type="number" id="anio" name="anio" min="1000" max="<?= e((int) date('Y') + 1) ?>"
                       value="<?= e($valor('anio')) ?>">
                <?php $mensajes('anio'); ?>
            </div>
        </div>

        <div class="<?= $clase('portada_url') ?>">
            <label for="portada_url">URL de la portada</label>
            <input type="url" id="portada_url" name="portada_url" maxlength="500"
                   placeholder="https://..."
                   value="<?= e($valor('portada_url')) ?>">
            <?php $mensajes('portada_url'); ?>
        </div>

        <div class="<?= $clase('sinopsis') ?>">
            <label for="sinopsis">Sinopsis</label>
            <textarea id="sinopsis" name="sinopsis"><?= e($valor('sinopsis')) ?></textarea>
            <?php $mensajes('sinopsis'); ?>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Inventario</h2>

        <div class="fila-campos">
            <div class="<?= $clase('stock_total') ?>">
                <label for="stock_total">Stock total</label>
                <input type="number" id="stock_total" name="stock_total" min="0"
                       value="<?= e($valor('stock_total') === '' ? '0' : $valor('stock_total')) ?>">
                <?php $mensajes('stock_total'); ?>
            </div>

            <div class="<?= $clase('stock_disponible') ?>">
                <label for="stock_disponible">Stock disponible</label>
                <input type="number" id="stock_disponible" name="stock_disponible" min="0"
                       value="<?= e($valor('stock_disponible') === '' ? '0' : $valor('stock_disponible')) ?>">
                <span class="campo__ayuda">No puede superar el stock total.</span>
                <?php $mensajes('stock_disponible'); ?>
            </div>
        </div>
    </div>

    <div class="acciones-formulario">
        <button class="boton" type="submit">
            <?= $esEdicion ? 'Guardar cambios' : 'Registrar libro' ?>
        </button>
        <a class="boton boton--secundario" href="<?= e(url('libros')) ?>">Cancelar</a>
    </div>
</form>
