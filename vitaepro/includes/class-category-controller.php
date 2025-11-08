<?php
/**
 * VitaePro Category Controller.
 *
 * @package VitaePro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VitaePro_Category_Controller {

    /**
     * @var string
     */
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vitaepro_categories';
    }

    /**
     * Create a new category.
     *
     * @param array $data Category data.
     * @return int|WP_Error Inserted ID on success, WP_Error on failure.
     */
    public function create_category( $data ) {
        global $wpdb;

        $name       = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $slug       = isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '';
        $table_type = isset( $data['table_type'] ) ? sanitize_key( $data['table_type'] ) : '';
        $schema     = isset( $data['schema_json'] ) ? $data['schema_json'] : array();

        if ( empty( $name ) ) {
            return new WP_Error( 'vitaepro_category_invalid_name', __( 'El nombre de la categoría es obligatorio.', 'vitaepro' ) );
        }

        if ( empty( $slug ) ) {
            $slug = sanitize_title( $name );
        }

        if ( $this->slug_exists( $slug ) ) {
            return new WP_Error( 'vitaepro_category_duplicate_slug', __( 'El slug ya existe. Por favor elige otro.', 'vitaepro' ) );
        }

        if ( empty( $table_type ) ) {
            return new WP_Error( 'vitaepro_category_invalid_table_type', __( 'Selecciona un tipo de tabla válido.', 'vitaepro' ) );
        }

        $schema_json = is_array( $schema ) ? wp_json_encode( $schema ) : wp_kses_post( $schema );

        $now = current_time( 'mysql' );

        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'name'       => $name,
                'slug'       => $slug,
                'table_type' => $table_type,
                'schema_json'=> $schema_json,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ( false === $inserted ) {
            return new WP_Error( 'vitaepro_category_insert_error', __( 'No se pudo crear la categoría.', 'vitaepro' ) );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get all categories.
     *
     * @return array
     */
    public function get_categories() {
        global $wpdb;

        return $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY created_at DESC" );
    }

    /**
     * Get a single category by ID.
     *
     * @param int $id Category ID.
     * @return object|null
     */
    public function get_category( $id ) {
        global $wpdb;

        $category_id = absint( $id );

        if ( $category_id <= 0 ) {
            return null;
        }

        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $category_id ) );
    }

    /**
     * Update a category by ID.
     *
     * @param int   $id   Category ID.
     * @param array $data Category data.
     * @return bool|WP_Error
     */
    public function update_category( $id, $data ) {
        global $wpdb;

        $category_id = absint( $id );

        if ( $category_id <= 0 ) {
            return new WP_Error( 'vitaepro_category_invalid_id', __( 'ID de categoría inválido.', 'vitaepro' ) );
        }

        $fields = array();
        $format = array();

        if ( isset( $data['name'] ) ) {
            $fields['name'] = sanitize_text_field( $data['name'] );
            $format[]       = '%s';
        }

        if ( isset( $data['slug'] ) ) {
            $slug = sanitize_title( $data['slug'] );

            if ( $this->slug_exists( $slug, $category_id ) ) {
                return new WP_Error( 'vitaepro_category_duplicate_slug', __( 'El slug ya existe. Por favor elige otro.', 'vitaepro' ) );
            }

            $fields['slug'] = $slug;
            $format[]       = '%s';
        }

        if ( isset( $data['table_type'] ) ) {
            $fields['table_type'] = sanitize_key( $data['table_type'] );
            $format[]             = '%s';
        }

        if ( isset( $data['schema_json'] ) ) {
            $schema_json          = is_array( $data['schema_json'] ) ? wp_json_encode( $data['schema_json'] ) : wp_kses_post( $data['schema_json'] );
            $fields['schema_json'] = $schema_json;
            $format[]              = '%s';
        }

        if ( empty( $fields ) ) {
            return new WP_Error( 'vitaepro_category_empty_update', __( 'No se proporcionaron datos para actualizar.', 'vitaepro' ) );
        }

        $fields['updated_at'] = current_time( 'mysql' );
        $format[]             = '%s';

        $updated = $wpdb->update(
            $this->table_name,
            $fields,
            array( 'id' => $category_id ),
            $format,
            array( '%d' )
        );

        if ( false === $updated ) {
            return new WP_Error( 'vitaepro_category_update_error', __( 'No se pudo actualizar la categoría.', 'vitaepro' ) );
        }

        return true;
    }

    /**
     * Delete a category by ID.
     *
     * @param int $id Category ID.
     * @return bool|WP_Error
     */
    public function delete_category( $id ) {
        global $wpdb;

        $category_id = absint( $id );

        if ( $category_id <= 0 ) {
            return new WP_Error( 'vitaepro_category_invalid_id', __( 'ID de categoría inválido.', 'vitaepro' ) );
        }

        $deleted = $wpdb->delete(
            $this->table_name,
            array( 'id' => $category_id ),
            array( '%d' )
        );

        if ( false === $deleted ) {
            return new WP_Error( 'vitaepro_category_delete_error', __( 'No se pudo eliminar la categoría.', 'vitaepro' ) );
        }

        return true;
    }

    /**
     * Check if a slug already exists.
     *
     * @param string   $slug Slug to check.
     * @param int|null $exclude_id Optional ID to exclude from the check.
     * @return bool
     */
    private function slug_exists( $slug, $exclude_id = null ) {
        global $wpdb;

        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s";
        $args  = array( $slug );

        if ( null !== $exclude_id ) {
            $query .= ' AND id != %d';
            $args[] = absint( $exclude_id );
        }

        $prepared = $wpdb->prepare( $query, $args );

        return (int) $wpdb->get_var( $prepared ) > 0;
    }
}
