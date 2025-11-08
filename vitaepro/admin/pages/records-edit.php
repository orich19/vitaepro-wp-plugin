<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'vitaepro' ) );
}

$record_controller   = new VitaePro_Record_Controller();
$category_controller = new VitaePro_Category_Controller();

$record_id = isset( $_REQUEST['id'] ) ? absint( wp_unslash( $_REQUEST['id'] ) ) : 0;

if ( $record_id <= 0 ) {
    wp_die( esc_html__( 'El registro solicitado no es válido.', 'vitaepro' ) );
}

$record = $record_controller->get_record( $record_id );

if ( ! $record ) {
    wp_die( esc_html__( 'El registro solicitado no existe.', 'vitaepro' ) );
}

$category = $category_controller->get_category( $record->category_id );

if ( ! $category ) {
    wp_die( esc_html__( 'La categoría asociada al registro no existe.', 'vitaepro' ) );
}

$schema  = json_decode( $category->schema_json, true );
$columns = isset( $schema['columns'] ) && is_array( $schema['columns'] ) ? $schema['columns'] : array();
$values  = is_object( $record ) && isset( $record->data ) && is_array( $record->data ) ? $record->data : array();

foreach ( $columns as $key => $definition ) {
    if ( ! isset( $values[ $key ] ) ) {
        $values[ $key ] = '';
    }
}

$notice_class = '';
$notice_text  = '';

if ( isset( $_POST['vitaepro_record_action'] ) && 'update' === sanitize_key( wp_unslash( $_POST['vitaepro_record_action'] ) ) ) {
    check_admin_referer( 'vitaepro_update_record', 'vitaepro_record_nonce' );

    $record_data = array();

    foreach ( $columns as $key => $definition ) {
        $type    = isset( $definition['type'] ) ? sanitize_key( $definition['type'] ) : 'text';
        $raw     = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';
        $options = isset( $definition['options'] ) && is_array( $definition['options'] ) ? $definition['options'] : array();

        switch ( $type ) {
            case 'textarea':
                $value = sanitize_textarea_field( wp_unslash( $raw ) );
                break;
            case 'number':
                $raw   = is_array( $raw ) ? '' : $raw;
                $raw   = '' === $raw ? '' : wp_unslash( $raw );
                $value = '' !== $raw && is_numeric( $raw ) ? 0 + $raw : '';
                break;
            case 'date':
                $value = sanitize_text_field( wp_unslash( $raw ) );
                break;
            case 'checkbox':
                $value = ! empty( $raw ) ? 1 : 0;
                break;
            case 'select':
                $raw_value = sanitize_text_field( wp_unslash( $raw ) );
                if ( ! empty( $options ) ) {
                    $allowed = array();
                    foreach ( $options as $option_key => $option_label ) {
                        if ( is_array( $option_label ) && isset( $option_label['value'] ) ) {
                            $allowed[ (string) $option_label['value'] ] = true;
                        } elseif ( is_string( $option_key ) ) {
                            $allowed[ (string) $option_key ] = true;
                            if ( is_string( $option_label ) ) {
                                $allowed[ (string) $option_label ] = true;
                            }
                        } elseif ( is_string( $option_label ) ) {
                            $allowed[ (string) $option_label ] = true;
                        }
                    }

                    $value = isset( $allowed[ $raw_value ] ) ? $raw_value : '';
                } else {
                    $value = $raw_value;
                }
                break;
            case 'text':
            default:
                $value = sanitize_text_field( wp_unslash( $raw ) );
                break;
        }

        $record_data[ $key ] = $value;
        $values[ $key ]      = $value;
    }

    $result = $record_controller->update_record( $record_id, $record_data );

    if ( ! $result ) {
        $notice_class = 'notice notice-error';
        $notice_text  = $record_controller->get_last_error();

        if ( empty( $notice_text ) ) {
            $notice_text = __( 'Ocurrió un error al actualizar el registro.', 'vitaepro' );
        }
    } else {
        $redirect_url = add_query_arg(
            array(
                'page'             => 'vitaepro-records-list',
                'vitaepro_message' => 'updated',
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }
}

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Editar Registro', 'vitaepro' ); ?></h1>
    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vitaepro-records-list' ) ); ?>">
            <?php esc_html_e( '← Volver a Registros', 'vitaepro' ); ?>
        </a>
    </p>

    <?php if ( ! empty( $notice_text ) ) : ?>
        <div class="<?php echo esc_attr( $notice_class ); ?>">
            <p><?php echo esc_html( $notice_text ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( add_query_arg( array( 'page' => 'vitaepro-records-edit', 'id' => $record_id ), admin_url( 'admin.php' ) ) ); ?>">
        <?php wp_nonce_field( 'vitaepro_update_record', 'vitaepro_record_nonce' ); ?>
        <input type="hidden" name="vitaepro_record_action" value="update" />
        <input type="hidden" name="id" value="<?php echo esc_attr( $record_id ); ?>" />

        <h2><?php echo esc_html( $category->name ); ?></h2>

        <table class="form-table" role="presentation">
            <tbody>
                <?php foreach ( $columns as $key => $definition ) : ?>
                    <?php
                    $label       = isset( $definition['label'] ) ? $definition['label'] : $key;
                    $type        = isset( $definition['type'] ) ? sanitize_key( $definition['type'] ) : 'text';
                    $description = isset( $definition['description'] ) ? $definition['description'] : '';
                    $value       = isset( $values[ $key ] ) ? $values[ $key ] : '';
                    $options     = isset( $definition['options'] ) && is_array( $definition['options'] ) ? $definition['options'] : array();
                    $readonly    = in_array( $key, array( 'anios', 'meses', 'dias' ), true );
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
                        </th>
                        <td>
                            <?php if ( 'textarea' === $type ) : ?>
                                <textarea name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" rows="5" cols="50" <?php readonly( $readonly ); ?>><?php echo esc_textarea( $value ); ?></textarea>
                            <?php elseif ( 'number' === $type ) : ?>
                                <input type="number" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php readonly( $readonly ); ?> />
                            <?php elseif ( 'date' === $type ) : ?>
                                <input type="date" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
                            <?php elseif ( 'checkbox' === $type ) : ?>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! empty( $value ) ); ?> />
                                    <?php esc_html_e( 'Sí', 'vitaepro' ); ?>
                                </label>
                            <?php elseif ( 'select' === $type ) : ?>
                                <select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php disabled( $readonly ); ?>>
                                    <option value=""><?php esc_html_e( 'Selecciona una opción', 'vitaepro' ); ?></option>
                                    <?php foreach ( $options as $option_key => $option_label ) : ?>
                                        <?php
                                        if ( is_array( $option_label ) ) {
                                            $option_value = isset( $option_label['value'] ) ? (string) $option_label['value'] : '';
                                            $option_text  = isset( $option_label['label'] ) ? $option_label['label'] : $option_value;
                                        } elseif ( is_string( $option_key ) ) {
                                            $option_value = (string) $option_key;
                                            $option_text  = $option_label;
                                        } else {
                                            $option_value = (string) $option_label;
                                            $option_text  = $option_label;
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_text ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="regular-text" value="<?php echo esc_attr( $value ); ?>" <?php readonly( $readonly ); ?> />
                            <?php endif; ?>

                            <?php if ( ! empty( $description ) ) : ?>
                                <p class="description"><?php echo esc_html( $description ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button( __( 'Actualizar registro', 'vitaepro' ) ); ?>
    </form>
</div>
