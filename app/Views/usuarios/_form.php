<?php
/**
 * @var array<string, mixed> $usuario
 * @var list<string> $rolesValidos
 * @var list<string> $estados
 * @var string $accion
 * @var string $metodo
 * @var bool|null $esMiCuenta
 */
$esEdicion = ($metodo ?? 'crear') === 'editar';
$esMiCuenta = $esMiCuenta ?? false;

$valor = static function (string $campo, string $defecto = '') use ($usuario): string {
    $guardado = $usuario[$campo] ?? $defecto;

    return (string) old($campo, $guardado === null ? '' : $guardado);
};

$clase = static fn (string $campo): string => errores($campo) === [] ? 'campo' : 'campo campo--error';
?>
<form method="POST" action="<?= e($accion) ?>">
    <?= csrf_field() ?>

    <div class="tarjeta">
        <h2>Datos de la cuenta</h2>

        <div class="<?= $clase('nombre') ?>">
            <label for="nombre">Nombre completo *</label>
            <input type="text" id="nombre" name="nombre" maxlength="120"
                   value="<?= e($valor('nombre')) ?>" required>
            <?php foreach (errores('nombre') as $mensaje): ?>
                <span class="campo__error"><?= e($mensaje) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="<?= $clase('email') ?>">
            <label for="email">Correo electrónico *</label>
            <input type="text" id="email" name="email" maxlength="180"
                   value="<?= e($valor('email')) ?>" required>
            <span class="campo__ayuda">Se usa para iniciar sesión. Debe ser único.</span>
            <?php foreach (errores('email') as $mensaje): ?>
                <span class="campo__error"><?= e($mensaje) ?></span>
            <?php endforeach; ?>
        </div>

        <div class="fila-campos">
            <div class="<?= $clase('rol') ?>">
                <label for="rol">Rol *</label>
                <select id="rol" name="rol">
                    <?php foreach ($rolesValidos as $rol): ?>
                        <option value="<?= e($rol) ?>" <?= $valor('rol') === $rol ? 'selected' : '' ?>>
                            <?= e(ucfirst($rol)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php foreach (errores('rol') as $mensaje): ?>
                    <span class="campo__error"><?= e($mensaje) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="<?= $clase('estado') ?>">
                <label for="estado">Estado *</label>
                <select id="estado" name="estado">
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= e($estado) ?>" <?= $valor('estado') === $estado ? 'selected' : '' ?>>
                            <?= e(ucfirst($estado)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php foreach (errores('estado') as $mensaje): ?>
                    <span class="campo__error"><?= e($mensaje) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($esMiCuenta): ?>
            <div class="aviso aviso--info">
                Estás editando tu propia cuenta: no puedes bloquearte ni quitarte el rol
                de administrador si eres el último.
            </div>
        <?php endif; ?>
    </div>

    <div class="tarjeta">
        <h2><?= $esEdicion ? 'Cambiar contraseña' : 'Contraseña' ?></h2>

        <?php if ($esEdicion): ?>
            <p class="subtitulo">
                Deja los campos vacíos si no quieres cambiarla. La contraseña actual
                nunca se muestra: se guarda cifrada.
            </p>
        <?php endif; ?>

        <div class="fila-campos">
            <div class="<?= $clase('password') ?>">
                <label for="password">
                    <?= $esEdicion ? 'Nueva contraseña' : 'Contraseña *' ?>
                </label>
                <input type="password" id="password" name="password"
                       autocomplete="new-password" <?= $esEdicion ? '' : 'required' ?>>
                <span class="campo__ayuda">Mínimo 8 caracteres.</span>
                <?php foreach (errores('password') as $mensaje): ?>
                    <span class="campo__error"><?= e($mensaje) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="<?= $clase('password_confirmacion') ?>">
                <label for="password_confirmacion">Repetir contraseña</label>
                <input type="password" id="password_confirmacion" name="password_confirmacion"
                       autocomplete="new-password">
                <?php foreach (errores('password_confirmacion') as $mensaje): ?>
                    <span class="campo__error"><?= e($mensaje) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="acciones-formulario">
        <button class="boton" type="submit"><?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?></button>
        <a class="boton boton--secundario" href="<?= e(url('usuarios')) ?>">Cancelar</a>
    </div>
</form>
