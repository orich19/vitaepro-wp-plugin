<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'vitaepro' ) );
}

$has_pdf_support = function_exists( 'vitaepro_pdf_dependencies_loaded' ) ? vitaepro_pdf_dependencies_loaded() : false;

if ( ! $has_pdf_support ) {
    echo '<div class="wrap">';
    echo '<h1>Exportar CV</h1>';
    echo '<div class="notice notice-error"><p>No se encontró Dompdf. Sube la carpeta /vendor/ completa al plugin para habilitar la exportación.</p></div>';
    echo '</div>';
    return;
}

$current_user = wp_get_current_user();
$user_name    = $current_user instanceof WP_User && $current_user->exists() ? $current_user->display_name : get_bloginfo( 'name' );
$user_email   = $current_user instanceof WP_User && $current_user->exists() ? $current_user->user_email : get_option( 'admin_email' );
$user_phone   = $current_user instanceof WP_User && $current_user->exists() ? get_user_meta( $current_user->ID, 'phone', true ) : '';

$user_name  = is_string( $user_name ) ? $user_name : '';
$user_email = is_string( $user_email ) ? $user_email : '';
$user_phone = is_string( $user_phone ) ? $user_phone : '';

$categories = VitaePro_PDF::get_all_categories();
$categories = is_array( $categories ) ? $categories : array();

$records_by_category = array();

if ( ! empty( $categories ) ) {
    foreach ( $categories as $category ) {
        $category_id = isset( $category['id'] ) ? (int) $category['id'] : 0;

        if ( $category_id <= 0 ) {
            continue;
        }

        $columns = isset( $category['columns'] ) && is_array( $category['columns'] ) ? $category['columns'] : array();
        $records = VitaePro_PDF::get_records_by_category( $category_id, 0, $columns );

        $records_by_category[ $category_id ] = is_array( $records ) ? $records : array();
    }
}

$locale      = get_locale();
$language    = $locale ? str_replace( '_', '-', $locale ) : 'es-ES';
$generated   = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
$style_block = '
    body { font-family: "Helvetica", "Arial", sans-serif; font-size: 12px; color: #222; margin: 0; padding: 0; }
    .vitaepro-pdf { padding: 32px; }
    .vitaepro-pdf__header { border-bottom: 2px solid #1d2327; margin-bottom: 24px; padding-bottom: 16px; }
    .vitaepro-pdf__title { font-size: 26px; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 1px; color: #1d2327; }
    .vitaepro-pdf__meta { margin: 0; padding: 0; list-style: none; display: flex; gap: 16px; flex-wrap: wrap; }
    .vitaepro-pdf__meta li { font-size: 12px; }
    .vitaepro-pdf__meta strong { text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; margin-right: 4px; }
    .vitaepro-pdf__section { margin-bottom: 28px; }
    .vitaepro-pdf__section-title { font-size: 18px; margin: 0 0 12px; color: #1d2327; border-bottom: 1px solid #dcdcde; padding-bottom: 6px; text-transform: uppercase; }
    .vitaepro-pdf__section-description { margin: 0 0 12px; color: #555; font-size: 12px; }
    .vitaepro-pdf__table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .vitaepro-pdf__table th { background: #f6f7f7; text-align: left; padding: 8px; border: 1px solid #dcdcde; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
    .vitaepro-pdf__table td { padding: 8px; border: 1px solid #dcdcde; vertical-align: top; }
    .vitaepro-pdf__empty { font-style: italic; color: #767676; }
    .vitaepro-pdf__footer { margin-top: 32px; font-size: 10px; color: #767676; text-align: right; }
';

ob_start();
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $language ); ?>">
<head>
    <meta charset="utf-8" />
    <style><?php echo $style_block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
</head>
<body>
<div class="vitaepro-pdf">
    <header class="vitaepro-pdf__header">
        <?php if ( $user_name ) : ?>
            <h1 class="vitaepro-pdf__title"><?php echo esc_html( $user_name ); ?></h1>
        <?php endif; ?>
        <ul class="vitaepro-pdf__meta">
            <?php if ( $user_email ) : ?>
                <li><strong><?php esc_html_e( 'Email', 'vitaepro' ); ?>:</strong> <span><?php echo esc_html( $user_email ); ?></span></li>
            <?php endif; ?>
            <?php if ( $user_phone ) : ?>
                <li><strong><?php esc_html_e( 'Teléfono', 'vitaepro' ); ?>:</strong> <span><?php echo esc_html( $user_phone ); ?></span></li>
            <?php endif; ?>
            <?php if ( $generated ) : ?>
                <li><strong><?php esc_html_e( 'Generado', 'vitaepro' ); ?>:</strong> <span><?php echo esc_html( $generated ); ?></span></li>
            <?php endif; ?>
        </ul>
    </header>

    <?php if ( empty( $categories ) ) : ?>
        <p class="vitaepro-pdf__empty"><?php esc_html_e( 'No hay categorías configuradas en el plugin.', 'vitaepro' ); ?></p>
    <?php else : ?>
        <?php foreach ( $categories as $category ) : ?>
            <?php
            $category_id   = isset( $category['id'] ) ? (int) $category['id'] : 0;
            $category_name = isset( $category['name'] ) ? $category['name'] : '';
            $description   = isset( $category['description'] ) ? $category['description'] : '';
            $columns       = isset( $category['columns'] ) && is_array( $category['columns'] ) ? $category['columns'] : array();
            $records       = isset( $records_by_category[ $category_id ] ) ? $records_by_category[ $category_id ] : array();
            ?>
            <section class="vitaepro-pdf__section">
                <?php if ( $category_name ) : ?>
                    <h2 class="vitaepro-pdf__section-title"><?php echo esc_html( $category_name ); ?></h2>
                <?php endif; ?>

                <?php if ( $description ) : ?>
                    <p class="vitaepro-pdf__section-description"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>

                <?php if ( empty( $records ) ) : ?>
                    <p class="vitaepro-pdf__empty"><?php esc_html_e( 'Sin registros para mostrar.', 'vitaepro' ); ?></p>
                <?php else : ?>
                    <?php
                    $header_columns = array();

                    if ( ! empty( $columns ) ) {
                        foreach ( $columns as $column ) {
                            $column_key   = isset( $column['key'] ) ? $column['key'] : '';
                            $column_label = isset( $column['label'] ) ? $column['label'] : $column_key;

                            if ( '' === $column_key ) {
                                continue;
                            }

                            $header_columns[] = array(
                                'key'   => $column_key,
                                'label' => $column_label,
                            );
                        }
                    }

                    if ( empty( $header_columns ) ) {
                        $first_record = reset( $records );

                        if ( $first_record && isset( $first_record['data'] ) && is_array( $first_record['data'] ) ) {
                            foreach ( $first_record['data'] as $data_key => $data_value ) {
                                $header_columns[] = array(
                                    'key'   => $data_key,
                                    'label' => is_string( $data_key ) ? $data_key : ( is_scalar( $data_key ) ? (string) $data_key : '' ),
                                );
                            }
                        }
                    }
                    ?>
                    <table class="vitaepro-pdf__table">
                        <thead>
                            <tr>
                                <?php foreach ( $header_columns as $header_column ) : ?>
                                    <th><?php echo esc_html( $header_column['label'] ); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $records as $record ) : ?>
                                <?php $data = isset( $record['data'] ) && is_array( $record['data'] ) ? $record['data'] : array(); ?>
                                <tr>
                                    <?php foreach ( $header_columns as $header_column ) : ?>
                                        <?php
                                        $key   = $header_column['key'];
                                        $value = isset( $data[ $key ] ) ? $data[ $key ] : '';
                                        $value = is_string( $value ) ? $value : ( is_scalar( $value ) ? (string) $value : '' );
                                        ?>
                                        <td><?php echo nl2br( esc_html( $value ) ); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <footer class="vitaepro-pdf__footer">
        <?php esc_html_e( 'Documento generado por el plugin VitaePro.', 'vitaepro' ); ?>
    </footer>
</div>
</body>
</html>
<?php
$html = ob_get_contents();

$options = new Options();
$options->set( 'isRemoteEnabled', true );

$dompdf = new Dompdf( $options );

ob_end_clean();

$dompdf->loadHtml( $html );
$dompdf->setPaper( 'A4', 'portrait' );
$dompdf->render();
$dompdf->stream( 'curriculum.pdf', ['Attachment' => true] );
exit;
