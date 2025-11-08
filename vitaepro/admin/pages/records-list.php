<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'vitaepro' ) );
}

$record_controller   = new VitaePro_Record_Controller();
$category_controller = new VitaePro_Category_Controller();
$notice_class        = '';
$notice_text         = '';

$forced_category_id   = isset( $forced_category_id ) ? absint( $forced_category_id ) : 0;
$forced_category_name = isset( $forced_category_name ) ? sanitize_text_field( $forced_category_name ) : '';
$current_page_slug    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'vitaepro-records-list';
$has_forced_category  = $forced_category_id > 0;
$current_category_id  = $has_forced_category ? $forced_category_id : ( isset( $_GET['category_id'] ) ? absint( wp_unslash( $_GET['category_id'] ) ) : 0 );

if ( isset( $_GET['action'], $_GET['id'] ) ) {
    $action    = sanitize_key( wp_unslash( $_GET['action'] ) );
    $record_id = absint( wp_unslash( $_GET['id'] ) );

    if ( 'delete' === $action && $record_id > 0 ) {
        check_admin_referer( 'vitaepro_delete_record_' . $record_id );

        $result = $record_controller->delete_record( $record_id );

        if ( ! $result ) {
            $notice_class = 'notice notice-error';
            $notice_text  = $record_controller->get_last_error();

            if ( empty( $notice_text ) ) {
                $notice_text = __( 'Ocurrió un error al eliminar el registro.', 'vitaepro' );
            }
        } else {
            $redirect_args = array(
                'page'             => $current_page_slug,
                'vitaepro_message' => 'deleted',
            );

            if ( ! $has_forced_category && $current_category_id > 0 ) {
                $redirect_args['category_id'] = $current_category_id;
            }

            $redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );

            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}

if ( isset( $_GET['vitaepro_message'] ) ) {
    switch ( sanitize_key( wp_unslash( $_GET['vitaepro_message'] ) ) ) {
        case 'created':
            $notice_class = 'notice notice-success is-dismissible';
            $notice_text  = __( 'El registro se creó correctamente.', 'vitaepro' );
            break;
        case 'updated':
            $notice_class = 'notice notice-success is-dismissible';
            $notice_text  = __( 'El registro se actualizó correctamente.', 'vitaepro' );
            break;
        case 'deleted':
            $notice_class = 'notice notice-success is-dismissible';
            $notice_text  = __( 'El registro se eliminó correctamente.', 'vitaepro' );
            break;
    }
}

$categories     = $category_controller->get_categories();
$categories     = is_array( $categories ) ? $categories : array();
$categories_map = array();
$columns_by_cat = array();

if ( ! empty( $categories ) ) {
    foreach ( $categories as $category ) {
        if ( ! isset( $category->id ) ) {
            continue;
        }

        $category_id = (int) $category->id;

        $categories_map[ $category_id ] = $category;

        $schema                       = json_decode( $category->schema_json, true );
        $columns_by_cat[ $category_id ] = isset( $schema['columns'] ) && is_array( $schema['columns'] ) ? $schema['columns'] : array();
    }
}

if ( $has_forced_category && '' === $forced_category_name && isset( $categories_map[ $forced_category_id ] ) ) {
    $forced_category_name = sanitize_text_field( $categories_map[ $forced_category_id ]->name );
}

global $wpdb;

$records          = array();
$table_records    = $wpdb->prefix . 'vitaepro_records';
$table_categories = $wpdb->prefix . 'vitaepro_categories';
$dompdf_autoload  = dirname( dirname( __DIR__ ) ) . '/vendor/dompdf/autoload.inc.php';
$has_dompdf       = file_exists( $dompdf_autoload );

if ( $current_category_id > 0 ) {
    $records = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.*, c.name AS category_name FROM {$table_records} r LEFT JOIN {$table_categories} c ON r.category_id = c.id WHERE r.category_id = %d ORDER BY r.id DESC",
            $current_category_id
        )
    );
}

if ( ! empty( $records ) ) {
    foreach ( $records as $record ) {
        $decoded      = isset( $record->data_json ) ? json_decode( $record->data_json, true ) : array();
        $record->data = is_array( $decoded ) ? $decoded : array();

        if ( empty( $record->category_name ) && isset( $categories_map[ $record->category_id ] ) ) {
            $record->category_name = $categories_map[ $record->category_id ]->name;
        }
    }
}

$create_args = array(
    'page' => 'vitaepro-records-create',
);

if ( $current_category_id > 0 ) {
    $create_args['category_id'] = $current_category_id;
}

$create_url = add_query_arg( $create_args, admin_url( 'admin.php' ) );

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Registros', 'vitaepro' ); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=vitaepro-export-cv'); ?>" class="button button-primary">
        Exportar CV en PDF
    </a>

    <?php if ( ! $has_dompdf ) : ?>
        <div class="notice notice-error" style="margin-top: 15px;">
            <p>No se encontró Dompdf. Sube la carpeta /vendor/dompdf/ al plugin para habilitar la exportación.</p>
        </div>
    <?php endif; ?>

    <?php if ( $has_forced_category && $current_category_id > 0 ) : ?>
        <h2 class="wp-heading-inline"><?php echo esc_html( $forced_category_name ); ?></h2>
        <p>
            <a class="button button-primary" href="<?php echo esc_url( $create_url ); ?>">
                <?php esc_html_e( 'Crear registro', 'vitaepro' ); ?>
            </a>
        </p>
    <?php else : ?>
        <?php if ( ! empty( $categories ) ) : ?>
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-bottom: 20px;">
                <input type="hidden" name="page" value="<?php echo esc_attr( $current_page_slug ); ?>" />
                <label for="vitaepro-filter-category" style="margin-right: 10px;">
                    <?php esc_html_e( 'Selecciona una categoría para ver los registros:', 'vitaepro' ); ?>
                </label>
                <select name="category_id" id="vitaepro-filter-category" required>
                    <option value=""><?php esc_html_e( 'Selecciona una categoría', 'vitaepro' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo esc_attr( $category->id ); ?>" <?php selected( $current_category_id, (int) $category->id ); ?>>
                            <?php echo esc_html( $category->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button( __( 'Ver registros', 'vitaepro' ), 'secondary', 'submit', false ); ?>
            </form>

            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-bottom: 20px;">
                <input type="hidden" name="page" value="vitaepro-records-create" />
                <label for="vitaepro-record-category" style="margin-right: 10px;">
                    <?php esc_html_e( 'Selecciona una categoría para crear un registro:', 'vitaepro' ); ?>
                </label>
                <select name="category_id" id="vitaepro-record-category" required>
                    <option value=""><?php esc_html_e( 'Selecciona una categoría', 'vitaepro' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo esc_attr( $category->id ); ?>">
                            <?php echo esc_html( $category->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button( __( 'Crear registro', 'vitaepro' ), 'primary', 'submit', false ); ?>
            </form>
        <?php else : ?>
            <div class="notice notice-warning" style="margin-bottom: 20px;">
                <p><?php esc_html_e( 'Debes crear categorías antes de añadir registros.', 'vitaepro' ); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( ! empty( $notice_text ) ) : ?>
        <div class="<?php echo esc_attr( $notice_class ); ?>">
            <p><?php echo esc_html( $notice_text ); ?></p>
        </div>
    <?php endif; ?>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Categoría', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Valores principales', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Fecha', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Acciones', 'vitaepro' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $current_category_id <= 0 ) : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e( 'Selecciona una categoría para ver los registros.', 'vitaepro' ); ?></td>
                </tr>
            <?php elseif ( empty( $records ) ) : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e( 'No hay registros disponibles actualmente.', 'vitaepro' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $records as $record ) : ?>
                    <?php
                    $category_name  = isset( $record->category_name ) ? $record->category_name : '';
                    $category_name  = $category_name ? $category_name : ( isset( $categories_map[ $record->category_id ] ) ? $categories_map[ $record->category_id ]->name : '' );
                    $columns        = isset( $columns_by_cat[ $record->category_id ] ) ? $columns_by_cat[ $record->category_id ] : array();
                    $record_data    = isset( $record->data ) && is_array( $record->data ) ? $record->data : array();
                    $primary_values = array();

                    if ( ! empty( $columns ) && ! empty( $record_data ) ) {
                        $keys = array_keys( $columns );
                        $keys = array_slice( $keys, 0, 2 );

                        foreach ( $keys as $key ) {
                            $label = isset( $columns[ $key ]['label'] ) ? $columns[ $key ]['label'] : $key;
                            $value = isset( $record_data[ $key ] ) ? $record_data[ $key ] : '';
                            $primary_values[] = array(
                                'label' => $label,
                                'value' => $value,
                            );
                        }
                    }

                    $edit_args = array(
                        'page' => 'vitaepro-records-edit',
                        'id'   => $record->id,
                    );

                    if ( $current_category_id > 0 ) {
                        $edit_args['category_id'] = $current_category_id;
                    }

                    $edit_url = add_query_arg( $edit_args, admin_url( 'admin.php' ) );

                    $delete_args = array(
                        'page'   => $current_page_slug,
                        'action' => 'delete',
                        'id'     => $record->id,
                    );

                    if ( ! $has_forced_category && $current_category_id > 0 ) {
                        $delete_args['category_id'] = $current_category_id;
                    }

                    $delete_url = wp_nonce_url(
                        add_query_arg( $delete_args, admin_url( 'admin.php' ) ),
                        'vitaepro_delete_record_' . $record->id
                    );

                    ?>
                    <tr>
                        <td><?php echo esc_html( $record->id ); ?></td>
                        <td><?php echo esc_html( $category_name ); ?></td>
                        <td>
                            <?php if ( ! empty( $primary_values ) ) : ?>
                                <?php foreach ( $primary_values as $primary_value ) : ?>
                                    <div><?php echo esc_html( sprintf( '%s: %s', $primary_value['label'], (string) $primary_value['value'] ) ); ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $record->created_at, true ) ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'vitaepro' ); ?></a> |
                            <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( '¿Estás seguro de que deseas eliminar este registro?', 'vitaepro' ) ); ?>');"><?php esc_html_e( 'Eliminar', 'vitaepro' ); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
