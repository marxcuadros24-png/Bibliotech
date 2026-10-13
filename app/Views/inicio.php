<?php
/**
 * Portada pública del sistema.
 *
 * La alimenta InicioController::index() y se renderiza con el layout público.
 * El botón "Comenzar" manda al panel si ya hay sesión y al login si no; así el
 * mismo botón sirve para los dos públicos sin duplicar la vista.
 */
$usuario = App\Core\Auth::usuario();
$destinoComenzar = $usuario !== null ? url('panel') : url('login');
?>
<section class="hero">
    <div class="hero__contenido">
        <h1 class="hero__titulo">Tu biblioteca, ordenada en un solo lugar</h1>
        <p class="hero__texto">
            Consulta el catálogo, gestiona préstamos y controla el inventario desde
            una plataforma simple, pensada para estudiantes y personal de biblioteca.
        </p>
        <div class="hero__acciones">
            <a class="boton boton--grande" href="<?= e($destinoComenzar) ?>">Comenzar</a>
        </div>
    </div>
</section>

<section class="caracteristicas">
    <h2 class="caracteristicas__titulo">Características</h2>

    <div class="rejilla-tres">
        <article class="caracteristica">
            <h3 class="caracteristica__titulo">Reservas Online</h3>
            <p class="caracteristica__texto">
                Reserva títulos desde el catálogo y consulta su disponibilidad en
                tiempo real, sin acercarte al mostrador.
            </p>
        </article>

        <article class="caracteristica">
            <h3 class="caracteristica__titulo">Control de stock</h3>
            <p class="caracteristica__texto">
                Lleva el inventario de ejemplares y sabe en todo momento cuántos
                están disponibles o prestados.
            </p>
        </article>

        <article class="caracteristica">
            <h3 class="caracteristica__titulo">Gestión de Multas</h3>
            <p class="caracteristica__texto">
                Calcula y administra automáticamente las multas por devoluciones
                tardías, con su cobro o condonación.
            </p>
        </article>
    </div>
</section>
