<?php
/**
 * Manual autoloader for Dompdf and its bundled dependencies.
 *
 * This file replaces the Composer generated autoloader so the plugin can run
 * on environments where Composer is not available. Only the namespaces used by
 * Dompdf are registered here.
 */

if ( defined( 'VITAEPRO_VENDOR_AUTOLOAD_LOADED' ) ) {
    return true;
}

define( 'VITAEPRO_VENDOR_AUTOLOAD_LOADED', true );

$vendor_dir = __DIR__;

// List of namespaces and their base directories relative to the vendor folder.
$dompdf_base = $vendor_dir . '/dompdf/dompdf/src/';

if ( ! is_dir( $dompdf_base ) ) {
    $dompdf_base = $vendor_dir . '/dompdf/src/';
}

$fontlib_base = $vendor_dir . '/phenx/php-font-lib/src/FontLib/';

if ( ! is_dir( $fontlib_base ) ) {
    $fontlib_base = $vendor_dir . '/php-font-lib/src/FontLib/';
}

$svglib_base = $vendor_dir . '/phenx/php-svg-lib/src/Svg/';

if ( ! is_dir( $svglib_base ) ) {
    $svglib_base = $vendor_dir . '/php-svg-lib/src/Svg/';
}

$csslib_base = $vendor_dir . '/sabberworm/php-css-parser/lib/Sabberworm/CSS/';

if ( ! is_dir( $csslib_base ) ) {
    $csslib_base = $vendor_dir . '/php-css-parser/lib/Sabberworm/CSS/';
}

$namespace_map = array(
    'Dompdf\\'        => $dompdf_base,
    'FontLib\\'       => $fontlib_base,
    'Svg\\'           => $svglib_base,
    'Sabberworm\\CSS\\' => $csslib_base,
);

spl_autoload_register(
    static function ( $class ) use ( $namespace_map ) {
        foreach ( $namespace_map as $prefix => $base_dir ) {
            $prefix_length = strlen( $prefix );

            if ( 0 !== strncmp( $class, $prefix, $prefix_length ) ) {
                continue;
            }

            // Ensure the base directory exists before attempting to load files.
            if ( ! is_dir( $base_dir ) ) {
                continue;
            }

            $relative_class = substr( $class, $prefix_length );
            $relative_path  = str_replace( '\\', '/', $relative_class ) . '.php';
            $file           = $base_dir . $relative_path;

            if ( file_exists( $file ) ) {
                require_once $file;
            }

            if ( class_exists( $class, false ) || interface_exists( $class, false ) || trait_exists( $class, false ) ) {
                return true;
            }
        }

        return null;
    },
    true,
    true
);

$helpers_candidates = array(
    $vendor_dir . '/dompdf/dompdf/src/Helpers.php',
    $vendor_dir . '/dompdf/src/Helpers.php',
);

$functions_candidates = array(
    $vendor_dir . '/dompdf/dompdf/src/functions.php',
    $vendor_dir . '/dompdf/src/functions.php',
);

$included_files = array_merge( $helpers_candidates, $functions_candidates );

foreach ( $included_files as $include ) {
    if ( file_exists( $include ) ) {
        require_once $include;
    }
}

return true;
