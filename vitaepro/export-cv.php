<?php
/**
 * Standalone script to test Dompdf integration within the plugin.
 */

declare( strict_types=1 );

// Prevent caching of the generated document.
if ( ! defined( 'DONOTCACHEPAGE' ) ) {
    define( 'DONOTCACHEPAGE', true );
}

if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
    define( 'DONOTCACHEOBJECT', true );
}

if ( ! defined( 'DONOTMINIFY' ) ) {
    define( 'DONOTMINIFY', true );
}

if ( ! defined( 'SHORTINIT' ) ) {
    define( 'SHORTINIT', true );
}

$plugin_root   = __DIR__;
$includes_path = $plugin_root . '/includes/pdf-loader.php';

if ( file_exists( $includes_path ) ) {
    require_once $includes_path;
}

if ( ! function_exists( 'vitaepro_pdf_dependencies_loaded' ) || ! vitaepro_pdf_dependencies_loaded() ) {
    header( 'HTTP/1.1 500 Internal Server Error' );
    header( 'Content-Type: text/plain; charset=utf-8' );
    echo 'No se encontró Dompdf. Sube la carpeta /vendor/ completa al plugin para habilitar la exportación.';
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

ini_set( 'memory_limit', '256M' );
set_time_limit( 120 );

$options = new Options();
$options->set( 'isRemoteEnabled', true );
$options->setChroot( $plugin_root );
$options->setDefaultPaperSize( 'a4' );

$dompdf = new Dompdf( $options );

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #222; margin: 0; padding: 32px; }
        h1 { color: #333; font-size: 26px; margin-bottom: 18px; text-transform: uppercase; letter-spacing: 1px; }
        .section-title { font-size: 18px; margin-top: 25px; border-bottom: 1px solid #dcdcde; padding-bottom: 6px; }
        ul { margin: 0 0 12px 18px; }
        li { margin-bottom: 6px; }
    </style>
</head>
<body>
    <h1>Currículum Vitae</h1>
    <p><strong>Nombre:</strong> Usuario de Prueba</p>
    <p><strong>Generado por:</strong> Plugin VitaePro</p>

    <div class="section-title">Experiencia Laboral</div>
    <ul>
        <li>Empresa X — 2018 - 2020</li>
        <li>Empresa Y — 2020 - 2023</li>
    </ul>

    <div class="section-title">Formación Académica</div>
    <ul>
        <li>Licenciatura en Ciencias</li>
    </ul>
</body>
</html>
HTML;

$dompdf->loadHtml( $html, 'UTF-8' );
$dompdf->setPaper( 'A4', 'portrait' );
$dompdf->render();

// Clear all active output buffers before streaming the PDF.
while ( ob_get_level() > 0 ) {
    ob_end_clean();
}

$dompdf->stream( 'vitaepro-cv.pdf', array( 'Attachment' => true ) );
exit;
