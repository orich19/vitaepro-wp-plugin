<?php
/**
 * VitaePro PDF utilities.
 *
 * @package VitaePro
 */

use Dompdf\Dompdf;
use Dompdf\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles CV HTML generation, public rendering and PDF exports.
 */
class VitaePro_PDF {
    const QUERY_VAR_USER = 'vitaepro_cv_user';
    const QUERY_VAR_PDF  = 'vitaepro_cv_pdf';

    /**
     * Whether the CV stylesheet must be enqueued on the front end.
     *
     * @var bool
     */
    private static $enqueue_style = false;

    /**
     * Boot PDF helpers.
     *
     * @return void
     */
    public static function init() {
        add_action( 'init', array( self::class, 'register_rewrite_rules' ) );
        add_filter( 'query_vars', array( self::class, 'register_query_vars' ) );
        add_action( 'template_redirect', array( self::class, 'handle_template_redirect' ) );
        add_shortcode( 'vitaepro_cv', array( self::class, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( self::class, 'maybe_enqueue_public_assets' ) );
    }

    /**
     * Register custom rewrite rules.
     *
     * @return void
     */
    public static function register_rewrite_rules() {
        add_rewrite_rule(
            '^cv/([0-9]+)/pdf/?$',
            'index.php?' . self::QUERY_VAR_USER . '=$matches[1]&' . self::QUERY_VAR_PDF . '=1',
            'top'
        );

        add_rewrite_rule(
            '^cv/([0-9]+)/?$',
            'index.php?' . self::QUERY_VAR_USER . '=$matches[1]',
            'top'
        );
    }
    /**
     * Register public query vars.
     *
     * @param array $vars Query vars.
     *
     * @return array
     */
    public static function register_query_vars( $vars ) {
        $vars[] = self::QUERY_VAR_USER;
        $vars[] = self::QUERY_VAR_PDF;

        return $vars;
    }

    /**
     * Handle template redirects for public CV endpoints.
     *
     * @return void
     */
    public static function handle_template_redirect() {
        $user_id = absint( get_query_var( self::QUERY_VAR_USER ) );

        if ( $user_id <= 0 ) {
            return;
        }

        if ( absint( get_query_var( self::QUERY_VAR_PDF ) ) > 0 ) {
            self::generate_pdf( $user_id );
            exit;
        }

        self::render_public_cv_page( $user_id );
        exit;
    }

    /**
     * Render the CV HTML for a given user.
     *
     * @param int $user_id User ID.
     *
     * @return string
     */
    public static function generate_cv_html( $user_id ) {
        $user_id = absint( $user_id );

        if ( $user_id <= 0 ) {
            return '';
        }

        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return '';
        }

        $cv_user = array(
            'id'          => $user_id,
            'name'        => $user->display_name ? $user->display_name : $user->user_login,
            'email'       => $user->user_email,
            'url'         => $user->user_url,
            'description' => get_user_meta( $user_id, 'description', true ),
        );

        $cv_user['description'] = is_string( $cv_user['description'] ) ? $cv_user['description'] : '';
        $cv_user['description'] = sanitize_textarea_field( $cv_user['description'] );

        $categories    = self::get_categories_with_records( $user_id );
        $template_path = self::get_plugin_dir() . 'templates/cv-template.php';
        $cv_categories = $categories;

        if ( ! file_exists( $template_path ) ) {
            return '';
        }

        ob_start();
        include $template_path;
        $html = ob_get_clean();

        if ( ! is_string( $html ) ) {
            $html = '';
        }

        return apply_filters( 'vitaepro_cv_html', $html, $user_id, $cv_categories, $cv_user );
    }
    /**
     * Render the public CV page.
     *
     * @param int $user_id User ID.
     *
     * @return void
     */
    public static function render_public_cv_page( $user_id = 0 ) {
        $user_id = absint( $user_id );

        if ( $user_id <= 0 ) {
            self::render_not_found();
            return;
        }

        $cv_html = self::generate_cv_html( $user_id );

        if ( '' === trim( $cv_html ) ) {
            self::render_not_found();
            return;
        }

        $pdf_url = self::get_public_pdf_url( $user_id );

        status_header( 200 );
        nocache_headers();

        $charset = get_bloginfo( 'charset' );
        $charset = $charset ? $charset : 'utf-8';

        echo '<!DOCTYPE html><html lang="' . esc_attr( get_locale() ) . '"><head>';
        echo '<meta charset="' . esc_attr( $charset ) . '" />';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
        echo '<title>' . esc_html( sprintf( __( 'Currículum de Usuario #%d', 'vitaepro' ), $user_id ) ) . '</title>';
        echo '<link rel="stylesheet" href="' . esc_url( self::get_cv_style_url() ) . '" media="all" />';
        echo '</head><body class="vitaepro-cv-page">';
        echo '<div class="vitaepro-cv-wrapper">';
        echo '<div class="vitaepro-cv-actions">';
        echo '<a class="vitaepro-button" href="' . esc_url( $pdf_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Exportar PDF', 'vitaepro' ) . '</a>';
        echo '</div>';
        echo $cv_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already sanitized in template.
        echo '</div></body></html>';
    }

    /**
     * Generate the PDF for a given user.
     *
     * @param int $user_id User ID.
     *
     * @return void
     */
    public static function generate_pdf( $user_id ) {
        $user_id = absint( $user_id );

        if ( $user_id <= 0 ) {
            wp_die( esc_html__( 'El ID de usuario proporcionado no es válido.', 'vitaepro' ) );
        }

        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            wp_die( esc_html__( 'El usuario solicitado no existe.', 'vitaepro' ) );
        }

        if ( ! self::maybe_include_autoloader() || ! class_exists( '\\Dompdf\\Dompdf' ) ) {
            wp_die(
                sprintf(
                    '<p>%s</p><p><code>composer require dompdf/dompdf</code></p>',
                    esc_html__( 'No se encontró Dompdf. Ejecuta el comando indicado en la carpeta del plugin para instalar las dependencias.', 'vitaepro' )
                )
            );
        }

        $cv_html = self::generate_cv_html( $user_id );

        if ( '' === trim( $cv_html ) ) {
            wp_die( esc_html__( 'No hay información disponible para generar el currículum.', 'vitaepro' ) );
        }

        $css = self::get_cv_css();

        $document = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>' . $cv_html . '</body></html>';

        $options = new Options();
        $options->set( 'isRemoteEnabled', true );
        $options->setDefaultPaperSize( 'a4' );

        $dompdf = new Dompdf( $options );
        $dompdf->loadHtml( $document, 'UTF-8' );
        $dompdf->setPaper( 'A4', 'portrait' );
        $dompdf->render();

        $filename = sprintf( 'cv-%s-%d.pdf', sanitize_title( $user->display_name ? $user->display_name : $user->user_login ), $user_id );

        $dompdf->stream( $filename, array( 'Attachment' => 1 ) );
        exit;
    }
    /**
     * Shortcode handler.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public static function render_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'user' => 0,
            ),
            $atts,
            'vitaepro_cv'
        );

        $user_id = absint( $atts['user'] );

        if ( $user_id <= 0 ) {
            return '';
        }

        $cv_html = self::generate_cv_html( $user_id );

        if ( '' === trim( $cv_html ) ) {
            return '';
        }

        self::$enqueue_style = true;

        return '<div class="vitaepro-cv-shortcode">' . $cv_html . '</div>';
    }

    /**
     * Enqueue stylesheet when required by the shortcode.
     *
     * @return void
     */
    public static function maybe_enqueue_public_assets() {
        if ( ! self::$enqueue_style ) {
            return;
        }

        wp_enqueue_style( 'vitaepro-cv-style', self::get_cv_style_url(), array(), '1.0.0', 'all' );
    }

    /**
     * Prepare categories with user records.
     *
     * @param int $user_id User ID.
     *
     * @return array
     */
    private static function get_categories_with_records( $user_id ) {
        global $wpdb;

        $table_categories = $wpdb->prefix . 'vitaepro_categories';
        $categories       = $wpdb->get_results( "SELECT * FROM {$table_categories} ORDER BY id ASC" );

        if ( empty( $categories ) ) {
            return array();
        }

        $record_controller = new VitaePro_Record_Controller();
        $prepared          = array();

        foreach ( $categories as $category ) {
            if ( ! isset( $category->id ) ) {
                continue;
            }

            $category_id = (int) $category->id;

            if ( $category_id <= 0 ) {
                continue;
            }

            $columns = self::prepare_category_columns( $category );
            $records = $record_controller->get_records_by_user_and_category( $user_id, $category_id );

            $records_output = array();

            if ( ! empty( $records ) ) {
                foreach ( $records as $record ) {
                    $record_data = array();

                    foreach ( $columns as $column ) {
                        $key  = $column['key'];
                        $type = $column['type'];
                        $raw  = isset( $record->data[ $key ] ) ? $record->data[ $key ] : '';

                        $record_data[ $key ] = self::prepare_value_for_display( $raw, $type );
                    }

                    $records_output[] = array(
                        'id'         => isset( $record->id ) ? (int) $record->id : 0,
                        'created_at' => isset( $record->created_at ) ? $record->created_at : '',
                        'data'       => $record_data,
                    );
                }
            }

            $prepared[] = array(
                'id'          => $category_id,
                'name'        => isset( $category->name ) ? sanitize_text_field( $category->name ) : '',
                'description' => isset( $category->description ) ? sanitize_textarea_field( $category->description ) : '',
                'columns'     => $columns,
                'records'     => $records_output,
            );
        }

        return $prepared;
    }
    /**
     * Prepare columns for display.
     *
     * @param object $category Category data.
     *
     * @return array
     */
    private static function prepare_category_columns( $category ) {
        $columns = array();
        $schema  = isset( $category->schema_json ) ? json_decode( $category->schema_json, true ) : array();
        $schema  = is_array( $schema ) ? $schema : array();

        if ( isset( $schema['columns'] ) && is_array( $schema['columns'] ) ) {
            foreach ( $schema['columns'] as $column_key => $definition ) {
                if ( is_array( $definition ) ) {
                    $type = isset( $definition['type'] ) ? $definition['type'] : 'text';
                    $label = isset( $definition['label'] ) ? $definition['label'] : $column_key;
                } else {
                    $type  = 'text';
                    $label = $column_key;
                }

                $columns[] = array(
                    'key'   => is_string( $column_key ) ? $column_key : sanitize_key( (string) $column_key ),
                    'type'  => sanitize_key( is_string( $type ) ? $type : 'text' ),
                    'label' => sanitize_text_field( is_string( $label ) ? $label : $column_key ),
                );
            }
        }

        return $columns;
    }

    /**
     * Prepare a record value for display.
     *
     * @param mixed  $value Raw value.
     * @param string $type  Field type.
     *
     * @return string
     */
    private static function prepare_value_for_display( $value, $type ) {
        if ( is_array( $value ) ) {
            $value = implode( ', ', array_map( 'sanitize_text_field', array_map( 'wp_unslash', $value ) ) );
        }

        if ( is_object( $value ) ) {
            $value = json_encode( $value );
        }

        $type = sanitize_key( $type );

        switch ( $type ) {
            case 'number':
                if ( is_numeric( $value ) ) {
                    $value = (string) ( 0 + $value );
                }
                $value = sanitize_text_field( $value );
                break;
            case 'textarea':
                $value = sanitize_textarea_field( (string) $value );
                break;
            case 'checkbox':
                $value = ! empty( $value ) ? __( 'Sí', 'vitaepro' ) : __( 'No', 'vitaepro' );
                break;
            case 'date':
                $value = sanitize_text_field( (string) $value );
                break;
            default:
                $value = sanitize_text_field( (string) $value );
                break;
        }

        return $value;
    }

    /**
     * Retrieve the CV stylesheet contents.
     *
     * @return string
     */
    private static function get_cv_css() {
        $style_path = self::get_plugin_dir() . 'templates/cv-style.css';

        if ( ! file_exists( $style_path ) ) {
            return '';
        }

        $css = file_get_contents( $style_path );

        if ( ! is_string( $css ) ) {
            return '';
        }

        return $css;
    }

    /**
     * Build the public PDF URL.
     *
     * @param int $user_id User ID.
     *
     * @return string
     */
    private static function get_public_pdf_url( $user_id ) {
        $user_id = absint( $user_id );

        if ( $user_id <= 0 ) {
            return home_url( '/' );
        }

        $path = sprintf( 'cv/%d/pdf', $user_id );

        return home_url( user_trailingslashit( $path ) );
    }

    /**
     * Get the stylesheet URL.
     *
     * @return string
     */
    private static function get_cv_style_url() {
        return plugins_url( 'templates/cv-style.css', self::get_plugin_file() );
    }

    /**
     * Retrieve the plugin directory path.
     *
     * @return string
     */
    private static function get_plugin_dir() {
        return plugin_dir_path( self::get_plugin_file() );
    }

    /**
     * Retrieve the main plugin file path.
     *
     * @return string
     */
    private static function get_plugin_file() {
        return dirname( __DIR__ ) . '/vitaepro.php';
    }

    /**
     * Attempt to include the Composer autoloader.
     *
     * @return bool
     */
    private static function maybe_include_autoloader() {
        static $loaded = null;

        if ( null !== $loaded ) {
            return $loaded;
        }

        $autoload = self::get_plugin_dir() . 'vendor/autoload.php';

        if ( file_exists( $autoload ) ) {
            require_once $autoload;
            $loaded = true;
        } else {
            $loaded = false;
        }

        return $loaded;
    }

    /**
     * Render a not found message.
     *
     * @return void
     */
    private static function render_not_found() {
        status_header( 404 );
        nocache_headers();

        $message = esc_html__( 'El currículum solicitado no está disponible.', 'vitaepro' );

        echo '<!DOCTYPE html><html><head><meta charset="utf-8" /><title>' . esc_html__( 'Currículum no encontrado', 'vitaepro' ) . '</title>';
        echo '<link rel="stylesheet" href="' . esc_url( self::get_cv_style_url() ) . '" media="all" />';
        echo '</head><body class="vitaepro-cv-page"><div class="vitaepro-cv-wrapper"><p class="vitaepro-cv-empty">' . esc_html( $message ) . '</p></div></body></html>';
    }
}
