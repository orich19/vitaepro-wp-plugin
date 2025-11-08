<?php
ob_start();
ob_clean();

define('DONOTCACHEPAGE', true);
define('DONOTCACHCEOBJECT', true);
define('DONOTMINIFY', true);
define('SHORTINIT', true);

require_once dirname(__FILE__, 3) . '/vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);

ini_set('memory_limit', '256M');
set_time_limit(120);

$dompdf = new Dompdf($options);

$html = '
<html>
<head>
<style>
body { font-family: Helvetica, sans-serif; font-size: 14px; }
h1 { color: #333; }
.section-title { font-size: 18px; margin-top: 25px; }
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
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

ob_end_clean();

$dompdf->stream("vitaepro-cv.pdf", ["Attachment" => true]);
exit;
