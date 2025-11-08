<?php
/**
 * Date helper functions for VitaePro.
 *
 * @package VitaePro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'vitaepro_calcular_tiempo' ) ) {
    /**
     * Calcula el tiempo transcurrido entre dos fechas.
     *
     * @param string $fecha_inicio Fecha de inicio en formato válido para DateTime.
     * @param string $fecha_fin    Fecha de fin en formato válido para DateTime.
     *
     * @return array{anios:int, meses:int, dias:int}
     */
    function vitaepro_calcular_tiempo( $fecha_inicio, $fecha_fin ) {
        $resultado = array(
            'anios' => 0,
            'meses' => 0,
            'dias'  => 0,
        );

        if ( empty( $fecha_inicio ) || empty( $fecha_fin ) ) {
            return $resultado;
        }

        try {
            $inicio = new DateTime( $fecha_inicio );
            $fin    = new DateTime( $fecha_fin );
        } catch ( Exception $e ) {
            return $resultado;
        }

        $intervalo = $inicio->diff( $fin );

        $resultado['anios'] = (int) $intervalo->y;
        $resultado['meses'] = (int) $intervalo->m;
        $resultado['dias']  = (int) $intervalo->d;

        return $resultado;
    }
}
