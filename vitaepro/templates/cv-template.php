<?php
/**
 * CV template for VitaePro.
 *
 * Variables available in scope:
 * - $cv_user (array)
 * - $cv_categories (array)
 *
 * @package VitaePro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cv_user             = isset( $cv_user ) && is_array( $cv_user ) ? $cv_user : array();
$categories          = isset( $categories ) && is_array( $categories ) ? $categories : array();
$records_by_category = isset( $records_by_category ) && is_array( $records_by_category ) ? $records_by_category : array();

$user_name  = isset( $cv_user['name'] ) ? $cv_user['name'] : '';
$user_email = isset( $cv_user['email'] ) ? $cv_user['email'] : '';
$user_url   = isset( $cv_user['url'] ) ? $cv_user['url'] : '';
$summary    = isset( $cv_user['description'] ) ? $cv_user['description'] : '';
?>
<div class="vitaepro-cv">
    <header class="vitaepro-cv__header">
        <?php if ( $user_name ) : ?>
            <h1 class="vitaepro-cv__title"><?php echo esc_html( $user_name ); ?></h1>
        <?php endif; ?>

        <ul class="vitaepro-cv__contact">
            <?php if ( $user_email ) : ?>
                <li><strong><?php esc_html_e( 'Email:', 'vitaepro' ); ?></strong> <span><?php echo esc_html( $user_email ); ?></span></li>
            <?php endif; ?>
            <?php if ( $user_url ) : ?>
                <li><strong><?php esc_html_e( 'Sitio web:', 'vitaepro' ); ?></strong> <span><?php echo esc_html( $user_url ); ?></span></li>
            <?php endif; ?>
        </ul>

        <?php if ( $summary ) : ?>
            <p class="vitaepro-cv__summary"><?php echo nl2br( esc_html( $summary ) ); ?></p>
        <?php endif; ?>
    </header>

    <?php if ( empty( $categories ) ) : ?>
        <p class="vitaepro-cv-empty"><?php esc_html_e( 'No hay información disponible.', 'vitaepro' ); ?></p>
    <?php else : ?>
        <?php foreach ( $categories as $category ) : ?>
            <?php
            $category_name        = isset( $category['name'] ) ? $category['name'] : '';
            $category_description = isset( $category['description'] ) ? $category['description'] : '';
            $columns              = isset( $category['columns'] ) && is_array( $category['columns'] ) ? $category['columns'] : array();
            $category_id          = isset( $category['id'] ) ? (int) $category['id'] : 0;
            $records              = isset( $records_by_category[ $category_id ] ) && is_array( $records_by_category[ $category_id ] ) ? $records_by_category[ $category_id ] : array();
            ?>
            <section class="vitaepro-cv__section">
                <?php if ( $category_name ) : ?>
                    <h2 class="vitaepro-cv__section-title"><?php echo esc_html( $category_name ); ?></h2>
                <?php endif; ?>

                <?php if ( $category_description ) : ?>
                    <p class="vitaepro-cv__section-description"><?php echo esc_html( $category_description ); ?></p>
                <?php endif; ?>

                <?php if ( empty( $records ) ) : ?>
                    <p class="vitaepro-cv-empty"><?php esc_html_e( 'Sin registros para mostrar en esta sección.', 'vitaepro' ); ?></p>
                <?php else : ?>
                    <div class="vitaepro-cv__entries">
                        <?php foreach ( $records as $record ) : ?>
                            <?php
                            $data      = isset( $record['data'] ) && is_array( $record['data'] ) ? $record['data'] : array();
                            $created   = isset( $record['created_at'] ) ? $record['created_at'] : '';
                            $created   = $created ? mysql2date( get_option( 'date_format' ), $created, true ) : '';
                            ?>
                            <article class="vitaepro-cv__entry">
                                <?php if ( $created ) : ?>
                                    <p class="vitaepro-cv__entry-date"><?php echo esc_html( $created ); ?></p>
                                <?php endif; ?>
                                <table class="vitaepro-cv__details" role="presentation">
                                    <tbody>
                                        <?php if ( ! empty( $columns ) ) : ?>
                                            <?php foreach ( $columns as $column ) : ?>
                                                <?php
                                                $key   = isset( $column['key'] ) ? $column['key'] : '';
                                                $label = isset( $column['label'] ) ? $column['label'] : $key;
                                                $value = isset( $data[ $key ] ) ? $data[ $key ] : '';
                                                $value = is_string( $value ) ? $value : ( is_scalar( $value ) ? (string) $value : '' );
                                                ?>
                                                <tr>
                                                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                                                    <td><?php echo nl2br( esc_html( $value ) ); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <?php foreach ( $data as $key => $value ) : ?>
                                                <?php
                                                $label = is_string( $key ) ? $key : ( is_scalar( $key ) ? (string) $key : '' );
                                                $value = is_string( $value ) ? $value : ( is_scalar( $value ) ? (string) $value : '' );
                                                ?>
                                                <tr>
                                                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                                                    <td><?php echo nl2br( esc_html( $value ) ); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
