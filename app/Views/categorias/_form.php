<?php
/**
 * @var array<string, mixed> $categoria
 * @var string $accion
 * @var string $metodo
 */
$esEdicion = ($metodo ?? 'crear') === 'editar';
?>
<form method="POST" action="<?= e($accion) ?>">
    <?= csrf_field() ?>

    <div class="tarjeta">
        <h2>Datos de la categoría</h2>

        <div class="<?= errores('nombre') === [] ? 'campo' : 'campo campo--error' ?>">
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" maxlength="100"
                   value="<?= e((string) old('nombre', (string) ($categoria['nombre'] ?? ''))) ?>" required>
            <span class="campo__ayuda">Debe ser único dentro del catálogo.</span>
            <?php foreach (errores('nombre') as $mensaje): ?>
                <span class="campo__error"><?= e($mensaje) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="<?= errores('descripcion') === [] ? 'campo' : 'campo campo--error' ?>">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion"><?= e((string) old('descripcion', (string) ($categoria['descripcion'] ?? ''))) ?></textarea>
            <?php foreach (errores('descripcion') as $mensaje): ?>
                <span class="campo__error"><?= e($mensaje) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="acciones-formulario">
        <button class="boton" type="submit"><?= $esEdicion ? 'Guardar cambios' : 'Crear categoría' ?></button>
        <a class="boton boton--secundario" href="<?= e(url('categorias')) ?>">Cancelar</a>
    </div>
</form>
