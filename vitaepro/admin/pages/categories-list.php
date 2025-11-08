<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'vitaepro' ) );
}

$controller   = new VitaePro_Category_Controller();
$notice_class = '';
$notice_text  = '';

if ( isset( $_GET['action'], $_GET['id'] ) && 'delete' === $_GET['action'] ) {
    $category_id = absint( $_GET['id'] );

    check_admin_referer( 'vitaepro_delete_category_' . $category_id );

    $result = $controller->delete_category( $category_id );

    if ( is_wp_error( $result ) ) {
        $notice_class = 'notice notice-error';
        $notice_text  = $result->get_error_message();
    } else {
        $redirect_url = add_query_arg(
            array(
                'page'              => 'vitaepro-categories-list',
                'vitaepro_message'  => 'deleted',
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }
}

if ( isset( $_GET['vitaepro_message'] ) ) {
    if ( 'deleted' === $_GET['vitaepro_message'] ) {
        $notice_class = 'notice notice-success is-dismissible';
        $notice_text  = __( 'La categoría se eliminó correctamente.', 'vitaepro' );
    }
}

$categories = $controller->get_categories();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Categorías', 'vitaepro' ); ?></h1>
    <p>
        <a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=vitaepro-categories-create' ) ); ?>">
            <?php esc_html_e( 'Crear nueva categoría', 'vitaepro' ); ?>
        </a>
    </p>
    <?php if ( ! empty( $notice_text ) ) : ?>
        <div class="<?php echo esc_attr( $notice_class ); ?>">
            <p><?php echo esc_html( $notice_text ); ?></p>
        </div>
    <?php endif; ?>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Nombre', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Slug', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Tipo de tabla', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Fecha de creación', 'vitaepro' ); ?></th>
                <th><?php esc_html_e( 'Acciones', 'vitaepro' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $categories ) ) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e( 'No hay categorías registradas todavía.', 'vitaepro' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $categories as $category ) : ?>
                    <?php
                    $edit_url = add_query_arg(
                        array(
                            'page'   => 'vitaepro-categories-create',
                            'action' => 'edit',
                            'id'     => $category->id,
                        ),
                        admin_url( 'admin.php' )
                    );

                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'page'   => 'vitaepro-categories-list',
                                'action' => 'delete',
                                'id'     => $category->id,
                            ),
                            admin_url( 'admin.php' )
                        ),
                        'vitaepro_delete_category_' . $category->id
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $category->id ); ?></td>
                        <td><?php echo esc_html( $category->name ); ?></td>
                        <td><?php echo esc_html( $category->slug ); ?></td>
                        <td><?php echo esc_html( $category->table_type ); ?></td>
                        <td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $category->created_at, true ) ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'vitaepro' ); ?></a> |
                            <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( '¿Estás seguro de que deseas eliminar esta categoría?', 'vitaepro' ) ); ?>');"><?php esc_html_e( 'Eliminar', 'vitaepro' ); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
