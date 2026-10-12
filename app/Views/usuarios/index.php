<?php
/**
 * @var list<array<string, mixed>> $usuarios
 * @var array<string, int> $roles
 * @var int|null $idPropio
 */
$idPropio = App\Core\Auth::id();
?>
<div class="encabezado-seccion">
    <div>
        <h1>Usuarios</h1>
        <p class="subtitulo">
            <?= e($roles['administrador']) ?> admin ·
            <?= e($roles['bibliotecario']) ?> bibliotecario(s) ·
            <?= e($roles['estudiante']) ?> estudiante(s)
        </p>
    </div>
    <a class="boton" href="<?= e(url('usuarios/crear')) ?>">Nuevo usuario</a>
</div>

<div class="tabla-envoltura">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Préstamos vigentes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <?php $soyYo = $idPropio === (int) $usuario['id']; ?>
                <tr class="<?= $usuario['estado'] === 'bloqueado' ? 'fila-atenuada' : '' ?>">
                    <td>
                        <strong><?= e($usuario['nombre']) ?></strong>
                        <?php if ($soyYo): ?>
                            <span class="etiqueta">tú</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($usuario['email']) ?></td>
                    <td><span class="etiqueta"><?= e(ucfirst((string) $usuario['rol'])) ?></span></td>
                    <td>
                        <span class="etiqueta <?= $usuario['estado'] === 'activo' ? 'etiqueta--disponible' : 'etiqueta--agotado' ?>">
                            <?= e(ucfirst((string) $usuario['estado'])) ?>
                        </span>
                    </td>
                    <td><?= e((int) $usuario['prestamos_activos']) ?></td>
                    <td>
                        <div class="acciones-fila">
                            <a class="boton boton--secundario boton--pequeno"
                               href="<?= e(url('usuarios/' . $usuario['id'] . '/editar')) ?>">Editar</a>

                            <?php if (!$soyYo): ?>
                                <form method="POST"
                                      action="<?= e(url('usuarios/' . $usuario['id'] . '/eliminar')) ?>"
                                      onsubmit="return confirm('¿Eliminar la cuenta de <?= e($usuario['nombre']) ?>?');">
                                    <?= csrf_field() ?>
                                    <button class="boton boton--peligro boton--pequeno" type="submit">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
