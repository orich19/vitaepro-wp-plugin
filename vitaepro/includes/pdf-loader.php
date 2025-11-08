<?php
if ( ! defined('ABSPATH') ) exit;

$dompdf_autoload = plugin_dir_path(__FILE__) . '../vendor/dompdf/autoload.inc.php';
if ( file_exists($dompdf_autoload) ) {
    require_once $dompdf_autoload;
}
