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

$cv_user       = isset( $cv_user ) && is_array( $cv_user ) ? $cv_user : array();
$cv_categories = isset( $cv_categories ) && is_array( $cv_categories ) ? $cv_categories : array();

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

    <?php if ( empty( $cv_categories ) ) : ?>
        <p class="vitaepro-cv-empty"><?php esc_html_e( 'No hay información disponible.', 'vitaepro' ); ?></p>
    <?php else : ?>
        <?php foreach ( $cv_categories as $category ) : ?>
            <?php
            $category_name        = isset( $category['name'] ) ? $category['name'] : '';
            $category_description = isset( $category['description'] ) ? $category['description'] : '';
            $columns              = isset( $category['columns'] ) && is_array( $category['columns'] ) ? $category['columns'] : array();
            $records              = isset( $category['records'] ) && is_array( $category['records'] ) ? $category['records'] : array();
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
                    <div class="vitaepro-cv__table-wrapper">
                        <table class="vitaepro-cv__table" role="presentation">
                            <thead>
                                <tr>
                                    <?php foreach ( $columns as $column ) : ?>
                                        <?php $label = isset( $column['label'] ) ? $column['label'] : ''; ?>
                                        <th scope="col"><?php echo esc_html( $label ); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $records as $record ) : ?>
                                    <?php $data = isset( $record['data'] ) && is_array( $record['data'] ) ? $record['data'] : array(); ?>
                                    <tr>
                                        <?php foreach ( $columns as $column ) : ?>
                                            <?php
                                            $key     = isset( $column['key'] ) ? $column['key'] : '';
                                            $value   = isset( $data[ $key ] ) ? $data[ $key ] : '';
                                            $value   = is_string( $value ) ? $value : ( is_scalar( $value ) ? (string) $value : '' );
                                            ?>
                                            <td><?php echo nl2br( esc_html( $value ) ); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
