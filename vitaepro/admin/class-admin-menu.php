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

        add_submenu_page(
            'vitaepro-dashboard',
            __('Registros', 'vitaepro'),
            __('Registros', 'vitaepro'),
            'manage_options',
            'vitaepro-records-list',
            [self::class, 'render_records_list']
        );

        $category_controller = new VitaePro_Category_Controller();
        $categories          = $category_controller->get_categories();

        if ( ! empty( $categories ) ) {
            foreach ( $categories as $category ) {
                if ( ! isset( $category->id ) ) {
                    continue;
                }

                $category_id   = absint( $category->id );
                $category_name = isset( $category->name ) ? sanitize_text_field( $category->name ) : '';

                if ( $category_id <= 0 || '' === $category_name ) {
                    continue;
                }

                $menu_slug = 'vitaepro-records-list-' . $category_id;

                add_submenu_page(
                    'vitaepro-dashboard',
                    sprintf(
                        /* translators: %s: category name */
                        __( 'Registros: %s', 'vitaepro' ),
                        $category_name
                    ),
                    $category_name,
                    'manage_options',
                    $menu_slug,
                    function () use ( $category_id, $category_name ) {
                        self::render_records_list( $category_id, $category_name );
                    }
                );
            }
        }

        // Crear Registro (oculto)
        add_submenu_page(
            null,
            __('Crear Registro', 'vitaepro'),
            '',
            'manage_options',
            'vitaepro-records-create',
            [self::class, 'render_records_create']
        );

        // Editar Registro (oculto)
        add_submenu_page(
            null,
            __('Editar Registro', 'vitaepro'),
            '',
            'manage_options',
            'vitaepro-records-edit',
            [self::class, 'render_records_edit']
        );

        add_submenu_page(
            null,
            'Exportar CV',
            '',
            'manage_options',
            'vitaepro-export-cv',
            ['VitaePro_Admin_Menu', 'render_export_cv']
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

    public static function render_records_list( $category_id = 0, $category_name = '' ) {
        self::load_view(
            'records-list',
            array(
                'forced_category_id'   => absint( $category_id ),
                'forced_category_name' => sanitize_text_field( $category_name ),
            )
        );
    }

    public static function render_records_create() {
        self::load_view('records-create');
    }

    public static function render_records_edit() {
        self::load_view('records-edit');
    }

    public static function render_export_cv() {
        self::load_view('export-cv');
    }

    private static function load_view($view, $args = array()) {
        $view_file = plugin_dir_path(__DIR__) . 'admin/pages/' . $view . '.php';

        if (file_exists($view_file)) {
            if ( ! empty( $args ) && is_array( $args ) ) {
                foreach ( $args as $key => $value ) {
                    ${$key} = $value;
                }
            }
            include $view_file;
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('No se pudo cargar la vista solicitada.', 'vitaepro')
        );
    }
}
