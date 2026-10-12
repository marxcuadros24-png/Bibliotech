<?php

declare(strict_types=1);

/**
 * Agrega a `multas` los datos con que se calculó el monto.
 *
 * Hasta ahora la multa solo guardaba el importe final, así que no había forma
 * de auditar de dónde salía. Con `dias_mora` y `tarifa_diaria` el cálculo
 * queda registrado aunque la tarifa cambie más adelante.
 */
return [
    'up' => [
        'ALTER TABLE `multas` ADD COLUMN `dias_mora` smallint(5) unsigned NOT NULL DEFAULT 0 AFTER `monto`',
        'ALTER TABLE `multas` ADD COLUMN `tarifa_diaria` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `dias_mora`',

        // Rellena las multas que ya existían a partir de las fechas del préstamo.
        // La tarifa se deduce del importe, así que no se fija ningún valor fijo.
        <<<'SQL'
        UPDATE `multas` m
        JOIN `prestamos` p ON p.`id` = m.`id_prestamo`
        SET m.`dias_mora` = GREATEST(
                DATEDIFF(COALESCE(p.`fecha_devolucion_real`, CURDATE()), p.`fecha_devolucion_esperada`),
                0
            ),
            m.`tarifa_diaria` = ROUND(
                m.`monto` / GREATEST(
                    DATEDIFF(COALESCE(p.`fecha_devolucion_real`, CURDATE()), p.`fecha_devolucion_esperada`),
                    1
                ),
                2
            )
        WHERE m.`monto` > 0
        SQL,
    ],

    'down' => [
        'ALTER TABLE `multas` DROP COLUMN `tarifa_diaria`',
        'ALTER TABLE `multas` DROP COLUMN `dias_mora`',
    ],
];
