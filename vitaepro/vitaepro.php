<?php
/**
 * Plugin Name: VitaePro
 * Plugin URI: https://vitaepro.sendatec.net
 * Description: Generador avanzado de Currículums Vitae con tablas dinámicas y cálculos automáticos.
 * Version: 0.1
 * Author: Tu Nombre / Sendatec
 * Text Domain: vitaepro
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Seguridad básica

require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';

register_activation_hook(__FILE__, ['VitaePro_Activator', 'activate']);

class VitaePro {

    public function __construct() {
        // Cargar archivos base
        $this->load_dependencies();

        if ( class_exists( 'VitaePro_PDF' ) ) {
            VitaePro_PDF::init();
        }

        // Hooks iniciales (puedes agregar más luego)
        add_action('init', [$this, 'init_plugin']);

        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'admin/class-admin-menu.php';
            VitaePro_Admin_Menu::init();
        }
    }

    public function load_dependencies() {
        require_once plugin_dir_path(__FILE__) . 'includes/helpers/compat.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-table-types.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-record-controller.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-category-controller.php';
        require_once plugin_dir_path(__FILE__) . 'includes/helpers/date-calculator.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-vitaepro-pdf.php';
    }

    public function init_plugin() {
        // Aquí irá cualquier cosa que quieras registrar al iniciar el plugin
    }
}

new VitaePro();
