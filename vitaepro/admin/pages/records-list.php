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
            $redirect_url = add_query_arg(
                array(
                    'page'             => 'vitaepro-records-list',
                    'vitaepro_message' => 'deleted',
                ),
                admin_url( 'admin.php' )
            );

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
$all_records    = array();

if ( ! empty( $categories ) ) {
    foreach ( $categories as $category ) {
        $categories_map[ $category->id ] = $category;

        $schema = json_decode( $category->schema_json, true );
        $columns_by_cat[ $category->id ] = isset( $schema['columns'] ) && is_array( $schema['columns'] ) ? $schema['columns'] : array();

        $records = $record_controller->get_records_by_category( $category->id );

        if ( ! empty( $records ) ) {
            foreach ( $records as $record ) {
                $all_records[] = $record;
            }
        }
    }
}

usort(
    $all_records,
    function ( $a, $b ) {
        return strcmp( (string) $b->created_at, (string) $a->created_at );
    }
);

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Registros', 'vitaepro' ); ?></h1>

    <?php if ( ! empty( $categories ) ) : ?>
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
            <?php if ( empty( $all_records ) ) : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e( 'No hay registros disponibles actualmente.', 'vitaepro' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $all_records as $record ) : ?>
                    <?php
                    $category_name  = isset( $categories_map[ $record->category_id ] ) ? $categories_map[ $record->category_id ]->name : '';
                    $columns        = isset( $columns_by_cat[ $record->category_id ] ) ? $columns_by_cat[ $record->category_id ] : array();
                    $record_data    = is_object( $record ) && isset( $record->data ) && is_array( $record->data ) ? $record->data : array();
                    $primary_values = array();

                    if ( ! empty( $columns ) && ! empty( $record_data ) ) {
                        $keys = array_keys( $columns );
                        $keys = array_slice( $keys, 0, 2 );

                        foreach ( $keys as $key ) {
                            $label = isset( $columns[ $key ]['label'] ) ? $columns[ $key ]['label'] : $key;
                            $value = isset( $record_data[ $key ] ) ? $record_data[ $key ] : '';
                            $primary_values[] = sprintf( '%s: %s', esc_html( $label ), esc_html( (string) $value ) );
                        }
                    }

                    $edit_url = add_query_arg(
                        array(
                            'page' => 'vitaepro-records-edit',
                            'id'   => $record->id,
                        ),
                        admin_url( 'admin.php' )
                    );

                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'page'   => 'vitaepro-records-list',
                                'action' => 'delete',
                                'id'     => $record->id,
                            ),
                            admin_url( 'admin.php' )
                        ),
                        'vitaepro_delete_record_' . $record->id
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $record->id ); ?></td>
                        <td><?php echo esc_html( $category_name ); ?></td>
                        <td>
                            <?php if ( ! empty( $primary_values ) ) : ?>
                                <?php foreach ( $primary_values as $value ) : ?>
                                    <div><?php echo esc_html( $value ); ?></div>
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
