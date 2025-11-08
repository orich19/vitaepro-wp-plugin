<?php
/**
 * Helper utilities to load Dompdf without Composer.
 *
 * @package VitaePro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'vitaepro_get_vendor_path' ) ) {
    /**
     * Retrieve the absolute path to the plugin vendor directory.
     *
     * @return string
     */
    function vitaepro_get_vendor_path() {
        return dirname( __DIR__ ) . '/vendor';
    }
}

if ( ! function_exists( 'vitaepro_get_vendor_autoload_candidates' ) ) {
    /**
     * Return all possible autoloader locations.
     *
     * @return array
     */
    function vitaepro_get_vendor_autoload_candidates() {
        $vendor_path = rtrim( vitaepro_get_vendor_path(), '/\\' );

        return array(
            $vendor_path . '/autoload.php',
            $vendor_path . '/dompdf/autoload.inc.php',
            $vendor_path . '/dompdf/dompdf/autoload.inc.php',
        );
    }
}

if ( ! function_exists( 'vitaepro_pdf_dependencies_loaded' ) ) {
    /**
     * Ensure Dompdf and its dependencies are available.
     *
     * @return bool
     */
    function vitaepro_pdf_dependencies_loaded() {
        static $loaded = null;

        if ( null !== $loaded ) {
            return $loaded;
        }

        if ( class_exists( '\\Dompdf\\Dompdf' ) && class_exists( '\\Dompdf\\Options' ) ) {
            $loaded = true;

            return $loaded;
        }

        $autoloaders = vitaepro_get_vendor_autoload_candidates();

        foreach ( $autoloaders as $autoloader ) {
            if ( ! $autoloader || ! file_exists( $autoloader ) ) {
                continue;
            }

            require_once $autoloader;

            if ( class_exists( '\\Dompdf\\Dompdf' ) && class_exists( '\\Dompdf\\Options' ) ) {
                $loaded = true;

                return $loaded;
            }
        }

        $loaded = false;

        return $loaded;
    }
}

if ( ! function_exists( 'vitaepro_has_pdf_support' ) ) {
    /**
     * Public helper to check Dompdf availability.
     *
     * @return bool
     */
    function vitaepro_has_pdf_support() {
        return vitaepro_pdf_dependencies_loaded();
    }
}
