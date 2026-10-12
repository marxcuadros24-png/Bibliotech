<?php
/**
 * @var array<string, mixed> $usuario
 * @var list<string> $rolesValidos
 * @var list<string> $estados
 * @var string $accion
 * @var string $metodo
 */
?>
<div class="encabezado-seccion">
    <div>
        <h1>Nuevo usuario</h1>
        <p class="subtitulo">La contraseña se guarda cifrada con bcrypt.</p>
    </div>
    <a class="boton boton--secundario" href="<?= e(url('usuarios')) ?>">Volver</a>
</div>

<?php require __DIR__ . '/_form.php'; ?>
