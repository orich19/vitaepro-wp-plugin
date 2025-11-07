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

class VitaePro {

    public function __construct() {
        // Cargar archivos base
        $this->load_dependencies();

        // Hooks iniciales (puedes agregar más luego)
        add_action('init', [$this, 'init_plugin']);
    }

    public function load_dependencies() {
        // Aquí cargarás archivos como category-manager, calculator, etc
        // Ejemplo:
        // require_once plugin_dir_path(__FILE__) . 'includes/helpers/date-calculator.php';
    }

    public function init_plugin() {
        // Aquí irá cualquier cosa que quieras registrar al iniciar el plugin
    }
}

new VitaePro();
