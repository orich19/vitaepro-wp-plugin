<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'vitaepro' ) );
}

$controller   = new VitaePro_Category_Controller();
$table_types  = VitaePro_Table_Types::get_table_types();
$notice_type  = '';
$notice_class = '';
$notice_text  = '';

$values = array(
    'name'       => '',
    'slug'       => '',
    'table_type' => '',
);

if ( isset( $_POST['vitaepro_category_action'] ) && 'create' === $_POST['vitaepro_category_action'] ) {
    check_admin_referer( 'vitaepro_create_category', 'vitaepro_category_nonce' );

    $values['name']       = isset( $_POST['vitaepro_category_name'] ) ? sanitize_text_field( wp_unslash( $_POST['vitaepro_category_name'] ) ) : '';
    $values['slug']       = isset( $_POST['vitaepro_category_slug'] ) ? sanitize_title( wp_unslash( $_POST['vitaepro_category_slug'] ) ) : '';
    $values['table_type'] = isset( $_POST['vitaepro_category_table_type'] ) ? sanitize_key( wp_unslash( $_POST['vitaepro_category_table_type'] ) ) : '';

    if ( empty( $values['name'] ) ) {
        $notice_type  = 'error';
        $notice_text  = __( 'El nombre de la categoría es obligatorio.', 'vitaepro' );
        $notice_class = 'notice notice-error';
    } elseif ( empty( $values['table_type'] ) || ! isset( $table_types[ $values['table_type'] ] ) ) {
        $notice_type  = 'error';
        $notice_text  = __( 'Selecciona un tipo de tabla válido.', 'vitaepro' );
        $notice_class = 'notice notice-error';
    } else {
        $schema_data = $table_types[ $values['table_type'] ];

        $result = $controller->create_category(
            array(
                'name'        => $values['name'],
                'slug'        => $values['slug'] ? $values['slug'] : $values['name'],
                'table_type'  => $values['table_type'],
                'schema_json' => $schema_data,
            )
        );

        if ( is_wp_error( $result ) ) {
            $notice_type  = 'error';
            $notice_text  = $result->get_error_message();
            $notice_class = 'notice notice-error';
        } else {
            $notice_type  = 'success';
            $notice_text  = __( 'La categoría se creó correctamente.', 'vitaepro' );
            $notice_class = 'notice notice-success is-dismissible';
            $values       = array(
                'name'       => '',
                'slug'       => '',
                'table_type' => '',
            );
        }
    }
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Crear Categoría', 'vitaepro' ); ?></h1>
    <?php if ( ! empty( $notice_text ) ) : ?>
        <div class="<?php echo esc_attr( $notice_class ); ?>">
            <p><?php echo esc_html( $notice_text ); ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=vitaepro-categories-create' ) ); ?>">
        <?php wp_nonce_field( 'vitaepro_create_category', 'vitaepro_category_nonce' ); ?>
        <input type="hidden" name="vitaepro_category_action" value="create" />

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="vitaepro-category-name"><?php esc_html_e( 'Nombre de la categoría', 'vitaepro' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="vitaepro_category_name" id="vitaepro-category-name" class="regular-text" value="<?php echo esc_attr( $values['name'] ); ?>" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vitaepro-category-slug"><?php esc_html_e( 'Slug', 'vitaepro' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="vitaepro_category_slug" id="vitaepro-category-slug" class="regular-text" value="<?php echo esc_attr( $values['slug'] ); ?>" />
                        <p class="description"><?php esc_html_e( 'Si se deja vacío se generará automáticamente a partir del nombre.', 'vitaepro' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vitaepro-category-table-type"><?php esc_html_e( 'Tipo de tabla', 'vitaepro' ); ?></label>
                    </th>
                    <td>
                        <select name="vitaepro_category_table_type" id="vitaepro-category-table-type" required>
                            <option value=""><?php esc_html_e( 'Selecciona un tipo de tabla', 'vitaepro' ); ?></option>
                            <?php foreach ( $table_types as $table_slug => $table_definition ) : ?>
                                <option value="<?php echo esc_attr( $table_slug ); ?>" <?php selected( $values['table_type'], $table_slug ); ?>>
                                    <?php echo esc_html( $table_definition['label'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button( __( 'Crear Categoría', 'vitaepro' ) ); ?>
    </form>
</div>
