<?php
/**
 * Listado de usuarios con filtros.
 *
 * @var list<array<string, mixed>> $usuarios
 * @var array<string, int> $roles
 * @var string $busqueda
 * @var string $rol    Filtro por rol ('' = todos)
 * @var string $estado Filtro por estado ('' = todos)
 */
$idPropio = App\Core\Auth::id();
$rolesValidos = App\Models\Usuario::ROLES;
$estadosValidos = App\Models\Usuario::ESTADOS;
$hayFiltros = $busqueda !== '' || $rol !== '' || $estado !== '';
?>
<div class="encabezado-seccion">
    <div>
        <h1>Usuarios</h1>
        <p class="subtitulo">
            <?= count($usuarios) ?> cuenta(s) listada(s) ·
            <?= e($roles['administrador']) ?> admin ·
            <?= e($roles['bibliotecario']) ?> bibliotecario(s) ·
            <?= e($roles['estudiante']) ?> estudiante(s)
        </p>
    </div>
    <?php if (puede('usuarios', 'crear')): ?>
        <a class="boton" href="<?= e(url('usuarios/crear')) ?>">Nuevo usuario</a>
    <?php endif; ?>
</div>

<div class="tarjeta">
    <form class="buscador" method="GET" action="<?= e(url('usuarios')) ?>">
        <input type="text" name="buscar" value="<?= e($busqueda) ?>"
               placeholder="Buscar por nombre o correo">

        <select name="rol">
            <option value="">Todos los roles</option>
            <?php foreach ($rolesValidos as $opcion): ?>
                <option value="<?= e($opcion) ?>" <?= $rol === $opcion ? 'selected' : '' ?>>
                    <?= e(ucfirst($opcion)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($estadosValidos as $opcion): ?>
                <option value="<?= e($opcion) ?>" <?= $estado === $opcion ? 'selected' : '' ?>>
                    <?= e(ucfirst($opcion)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="boton" type="submit">Filtrar</button>

        <?php if ($hayFiltros): ?>
            <a class="boton boton--secundario" href="<?= e(url('usuarios')) ?>">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($usuarios === []): ?>
    <div class="vacio">No hay usuarios que coincidan con el filtro.</div>
<?php else: ?>
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
                                <?php if (puede('usuarios', 'editar')): ?>
                                    <a class="boton boton--secundario boton--pequeno"
                                       href="<?= e(url('usuarios/' . $usuario['id'] . '/editar')) ?>">Editar</a>
                                <?php endif; ?>

                                <?php if (!$soyYo && puede('usuarios', 'eliminar')): ?>
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
<?php endif; ?>
