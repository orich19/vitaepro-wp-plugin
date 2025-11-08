<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'No tienes permisos suficientes para exportar el currículum.', 'vitaepro' ) );
}

if ( ! class_exists( 'VitaePro_PDF' ) ) {
    wp_die( esc_html__( 'El módulo de exportación no está disponible.', 'vitaepro' ) );
}

$user_id = get_current_user_id();

if ( $user_id <= 0 ) {
    wp_die( esc_html__( 'No hay un usuario válido para generar el currículum.', 'vitaepro' ) );
}

VitaePro_PDF::generate_pdf( $user_id );
exit;
