<?php

declare(strict_types=1);

/**
 * Reglas de negocio de la biblioteca.
 *
 * Se mantienen fuera del código para poder ajustarlas sin tocar los módulos.
 * Los valores se leen del .env y tienen como respaldo los que ya usaba la base
 * de datos existente.
 */
return [
    // Días que se suman a la fecha del préstamo para calcular la devolución esperada.
    // El bibliotecario puede cambiarla al registrar cada préstamo.
    'dias_prestamo' => (int) env('DIAS_PRESTAMO', 7),

    // Tarifa diaria de mora. El valor por defecto (1.50) se dedujo de la multa
    // que ya existe en db_libros: 6 días de mora por un monto de 9.00.
    'multa_por_dia' => (float) env('MULTA_POR_DIA', 1.50),

    // Símbolo usado al mostrar montos.
    'moneda' => env('MONEDA', 'S/'),

    // Cuántos días de mora se muestran en el panel resumido.
    'dias_alerta_mora' => 3,
];
