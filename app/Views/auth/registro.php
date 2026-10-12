<?php
// Los errores y lo ya escrito se recuperan con errores() y old(), que leen los
// mensajes flash dejados por AuthController::registrar().
//
// No hay campo de rol a propósito: el rol lo fija el modelo (Usuario::ROL_PUBLICO).
// Si se pudiera elegir desde aquí, cualquiera se registraría como administrador.
?>
<form class="tarjeta formulario-acceso" method="POST" action="<?= e(url('registro')) ?>">
    <?= csrf_field() ?>

    <div class="<?= errores('nombre') === [] ? 'campo' : 'campo campo--error' ?>">
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" maxlength="120"
               value="<?= e(old('nombre')) ?>" autocomplete="name" autofocus required>
        <?php foreach (errores('nombre') as $mensaje): ?>
            <span class="campo__error"><?= e($mensaje) ?></span>
        <?php endforeach; ?>
    </div>

    <div class="<?= errores('email') === [] ? 'campo' : 'campo campo--error' ?>">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" maxlength="180"
               value="<?= e(old('email')) ?>" autocomplete="username" required>
        <span class="campo__ayuda">Lo usarás para iniciar sesión.</span>
        <?php foreach (errores('email') as $mensaje): ?>
            <span class="campo__error"><?= e($mensaje) ?></span>
        <?php endforeach; ?>
    </div>

    <div class="<?= errores('password') === [] ? 'campo' : 'campo campo--error' ?>">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               autocomplete="new-password" required>
        <span class="campo__ayuda">Mínimo 8 caracteres.</span>
        <?php foreach (errores('password') as $mensaje): ?>
            <span class="campo__error"><?= e($mensaje) ?></span>
        <?php endforeach; ?>
    </div>

    <div class="<?= errores('password_confirmacion') === [] ? 'campo' : 'campo campo--error' ?>">
        <label for="password_confirmacion">Repetir contraseña</label>
        <input type="password" id="password_confirmacion" name="password_confirmacion"
               autocomplete="new-password" required>
        <?php foreach (errores('password_confirmacion') as $mensaje): ?>
            <span class="campo__error"><?= e($mensaje) ?></span>
        <?php endforeach; ?>
    </div>

    <button class="boton boton--bloque" type="submit">Crear cuenta</button>

    <p class="acceso__pie">
        Las cuentas nuevas entran como <strong>estudiante</strong>.
        Un administrador puede cambiarte el rol después.<br>
        ¿Ya tienes cuenta? <a href="<?= e(url('login')) ?>">Inicia sesión</a>
    </p>
</form>
