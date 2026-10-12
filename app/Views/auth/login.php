<?php
// Los errores y el correo ya escrito se recuperan con errores() y old(),
// que leen los mensajes flash dejados por AuthController::login().
?>
<form class="tarjeta formulario-acceso" method="POST" action="<?= e(url('login')) ?>">
    <?= csrf_field() ?>

    <div class="<?= errores('email') === [] ? 'campo' : 'campo campo--error' ?>">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" maxlength="180"
               autocomplete="username" value="<?= e(old('email')) ?>" autofocus required>
        <?php foreach (errores('email') as $mensaje): ?>
            <span class="campo__error"><?= e($mensaje) ?></span>
        <?php endforeach; ?>
    </div>

    <div class="<?= errores('password') === [] ? 'campo' : 'campo campo--error' ?>">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password"
               autocomplete="current-password" required>
        <?php foreach (errores('password') as $mensaje): ?>
            <span class="campo__error"><?= e($mensaje) ?></span>
        <?php endforeach; ?>
    </div>

    <button class="boton boton--bloque" type="submit">Entrar</button>

    <p class="acceso__pie">
        ¿No tienes cuenta? <a href="<?= e(url('registro')) ?>">Regístrate</a>
    </p>
</form>
