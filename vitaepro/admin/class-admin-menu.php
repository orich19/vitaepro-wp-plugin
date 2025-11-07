<?php

class VitaePro_Admin_Menu {

    public static function init() {
        add_action('admin_menu', [self::class, 'register_menu']);
    }

    public static function register_menu() {
        add_menu_page(
            'VitaePro',
            'VitaePro',
            'manage_options',
            'vitaepro-dashboard',
            [self::class, 'render_dashboard'],
            'dashicons-id',
            20
        );
    }

    public static function render_dashboard() {
        echo '<div class="wrap"><h1>VitaePro</h1><p>El plugin está funcionando correctamente.</p></div>';
    }
}
