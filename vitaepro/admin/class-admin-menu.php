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

        add_submenu_page(
            'vitaepro-dashboard',
            __('Categorías', 'vitaepro'),
            __('Categorías', 'vitaepro'),
            'manage_options',
            'vitaepro-categories-list',
            [self::class, 'render_categories_list']
        );

        add_submenu_page(
            'vitaepro-dashboard',
            __('Crear Categoría', 'vitaepro'),
            __('Crear Categoría', 'vitaepro'),
            'manage_options',
            'vitaepro-categories-create',
            [self::class, 'render_categories_create']
        );
    }

    public static function render_dashboard() {
        echo '<div class="wrap"><h1>VitaePro</h1><p>El plugin está funcionando correctamente.</p></div>';
    }

    public static function render_categories_list() {
        self::load_view('categories-list');
    }

    public static function render_categories_create() {
        self::load_view('categories-create');
    }

    private static function load_view($view) {
        $view_file = plugin_dir_path(__DIR__) . 'admin/pages/' . $view . '.php';

        if (file_exists($view_file)) {
            include $view_file;
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('No se pudo cargar la vista solicitada.', 'vitaepro')
        );
    }
}
