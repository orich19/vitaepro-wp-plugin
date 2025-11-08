<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'vitaepro' ) );
}

$user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;

if ( $user_id <= 0 ) {
    wp_die( esc_html__( 'Falta el parámetro de usuario para exportar.', 'vitaepro' ) );
}

if ( ! class_exists( 'VitaePro_PDF' ) ) {
    wp_die( esc_html__( 'El módulo de exportación no está disponible.', 'vitaepro' ) );
}

VitaePro_PDF::generate_pdf( $user_id );
exit;
