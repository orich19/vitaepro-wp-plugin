<?php
/**
 * VitaePro Record Controller.
 *
 * @package VitaePro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VitaePro_Record_Controller {

    /**
     * @var string
     */
    private $table_name;

    /**
     * @var string
     */
    private $categories_table;

    public function __construct() {
        global $wpdb;

        $this->table_name       = $wpdb->prefix . 'vitaepro_records';
        $this->categories_table = $wpdb->prefix . 'vitaepro_categories';
    }

    /**
     * Create a new record.
     *
     * @param int   $category_id Category ID.
     * @param int   $user_id     User ID.
     * @param array $data_array  Record data.
     *
     * @return int|WP_Error Inserted ID on success, WP_Error on failure.
     */
    public function create_record( $category_id, $user_id, $data_array ) {
        global $wpdb;

        $category_id = absint( $category_id );
        $user_id     = absint( $user_id );

        if ( $category_id <= 0 ) {
            return new WP_Error( 'vitaepro_record_invalid_category', __( 'Categoría inválida.', 'vitaepro' ) );
        }

        if ( ! $this->category_exists( $category_id ) ) {
            return new WP_Error( 'vitaepro_record_missing_category', __( 'La categoría seleccionada no existe.', 'vitaepro' ) );
        }

        $category    = $this->get_category( $category_id );
        $record_data = $this->prepare_record_data( $category, $data_array );

        $now = current_time( 'mysql' );

        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'category_id' => $category_id,
                'user_id'     => $user_id,
                'data_json'   => wp_json_encode( $record_data ),
                'created_at'  => $now,
                'updated_at'  => $now,
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return new WP_Error( 'vitaepro_record_insert_error', __( 'No se pudo crear el registro.', 'vitaepro' ) );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing record.
     *
     * @param int   $record_id  Record ID.
     * @param array $data_array Record data.
     *
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function update_record( $record_id, $data_array ) {
        global $wpdb;

        $record_id = absint( $record_id );

        if ( $record_id <= 0 ) {
            return new WP_Error( 'vitaepro_record_invalid_id', __( 'ID de registro inválido.', 'vitaepro' ) );
        }

        $record = $this->get_record( $record_id );

        if ( ! $record ) {
            return new WP_Error( 'vitaepro_record_not_found', __( 'El registro solicitado no existe.', 'vitaepro' ) );
        }

        $category = $this->get_category( (int) $record->category_id );

        if ( ! $category ) {
            return new WP_Error( 'vitaepro_record_missing_category', __( 'La categoría asociada al registro no existe.', 'vitaepro' ) );
        }

        $record_data = $this->prepare_record_data( $category, $data_array );

        $updated = $wpdb->update(
            $this->table_name,
            array(
                'data_json'  => wp_json_encode( $record_data ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $record_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            return new WP_Error( 'vitaepro_record_update_error', __( 'No se pudo actualizar el registro.', 'vitaepro' ) );
        }

        return true;
    }

    /**
     * Delete a record.
     *
     * @param int $record_id Record ID.
     *
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function delete_record( $record_id ) {
        global $wpdb;

        $record_id = absint( $record_id );

        if ( $record_id <= 0 ) {
            return new WP_Error( 'vitaepro_record_invalid_id', __( 'ID de registro inválido.', 'vitaepro' ) );
        }

        $deleted = $wpdb->delete(
            $this->table_name,
            array( 'id' => $record_id ),
            array( '%d' )
        );

        if ( false === $deleted ) {
            return new WP_Error( 'vitaepro_record_delete_error', __( 'No se pudo eliminar el registro.', 'vitaepro' ) );
        }

        return true;
    }

    /**
     * Retrieve a single record.
     *
     * @param int $record_id Record ID.
     *
     * @return object|null
     */
    public function get_record( $record_id ) {
        global $wpdb;

        $record_id = absint( $record_id );

        if ( $record_id <= 0 ) {
            return null;
        }

        $record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $record_id ) );

        return $this->format_record_output( $record );
    }

    /**
     * Retrieve all records for a category.
     *
     * @param int $category_id Category ID.
     *
     * @return array
     */
    public function get_records_by_category( $category_id ) {
        global $wpdb;

        $category_id = absint( $category_id );

        if ( $category_id <= 0 ) {
            return array();
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE category_id = %d ORDER BY created_at DESC",
                $category_id
            )
        );

        return $this->format_records_output( $results );
    }

    /**
     * Retrieve records for a user and category.
     *
     * @param int $user_id     User ID.
     * @param int $category_id Category ID.
     *
     * @return array
     */
    public function get_records_by_user_and_category( $user_id, $category_id ) {
        global $wpdb;

        $user_id     = absint( $user_id );
        $category_id = absint( $category_id );

        if ( $user_id <= 0 || $category_id <= 0 ) {
            return array();
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE user_id = %d AND category_id = %d ORDER BY created_at DESC",
                $user_id,
                $category_id
            )
        );

        return $this->format_records_output( $results );
    }

    /**
     * Check whether a category exists.
     *
     * @param int $category_id Category ID.
     *
     * @return bool
     */
    private function category_exists( $category_id ) {
        global $wpdb;

        $category_id = absint( $category_id );

        if ( $category_id <= 0 ) {
            return false;
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->categories_table} WHERE id = %d",
                $category_id
            )
        );

        return (int) $count > 0;
    }

    /**
     * Retrieve category data.
     *
     * @param int $category_id Category ID.
     *
     * @return object|null
     */
    private function get_category( $category_id ) {
        global $wpdb;

        $category_id = absint( $category_id );

        if ( $category_id <= 0 ) {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->categories_table} WHERE id = %d",
                $category_id
            )
        );
    }

    /**
     * Prepare record data based on the category schema.
     *
     * @param object     $category   Category object.
     * @param array|null $data_array Raw data.
     *
     * @return array
     */
    private function prepare_record_data( $category, $data_array ) {
        $data_array = is_array( $data_array ) ? $data_array : array();
        $schema     = array();

        if ( $category && ! empty( $category->schema_json ) ) {
            $decoded = json_decode( $category->schema_json, true );

            if ( isset( $decoded['columns'] ) && is_array( $decoded['columns'] ) ) {
                $schema = $decoded['columns'];
            }
        }

        $prepared = array();

        foreach ( $schema as $column_key => $definition ) {
            $raw_value = isset( $data_array[ $column_key ] ) ? $data_array[ $column_key ] : '';
            $type      = isset( $definition['type'] ) ? $definition['type'] : 'text';

            switch ( $type ) {
                case 'textarea':
                    $value = sanitize_textarea_field( wp_unslash( $raw_value ) );
                    break;
                case 'number':
                    $value = is_numeric( $raw_value ) ? 0 + $raw_value : '';
                    break;
                case 'date':
                    $value = sanitize_text_field( wp_unslash( $raw_value ) );
                    break;
                case 'checkbox':
                    $value = ! empty( $raw_value ) ? 1 : 0;
                    break;
                case 'text':
                default:
                    $value = sanitize_text_field( wp_unslash( $raw_value ) );
                    break;
            }

            $prepared[ $column_key ] = $value;
        }

        if ( isset( $prepared['fecha_inicio'], $prepared['fecha_fin'] ) && ! empty( $prepared['fecha_inicio'] ) && ! empty( $prepared['fecha_fin'] ) ) {
            if ( function_exists( 'vitaepro_calcular_tiempo' ) ) {
                $tiempo = vitaepro_calcular_tiempo( $prepared['fecha_inicio'], $prepared['fecha_fin'] );

                foreach ( array( 'anios', 'meses', 'dias' ) as $time_key ) {
                    if ( array_key_exists( $time_key, $prepared ) ) {
                        $prepared[ $time_key ] = isset( $tiempo[ $time_key ] ) ? (int) $tiempo[ $time_key ] : 0;
                    } elseif ( isset( $schema[ $time_key ] ) ) {
                        $prepared[ $time_key ] = isset( $tiempo[ $time_key ] ) ? (int) $tiempo[ $time_key ] : 0;
                    }
                }
            }
        }

        return $prepared;
    }

    /**
     * Format a record with decoded data JSON.
     *
     * @param object|null $record Record object.
     *
     * @return object|null
     */
    private function format_record_output( $record ) {
        if ( ! $record ) {
            return null;
        }

        $decoded = json_decode( $record->data_json, true );

        if ( is_array( $decoded ) ) {
            $record->data = $decoded;
        } else {
            $record->data = array();
        }

        return $record;
    }

    /**
     * Format an array of records.
     *
     * @param array $records Raw records.
     *
     * @return array
     */
    private function format_records_output( $records ) {
        $formatted = array();

        if ( empty( $records ) ) {
            return $formatted;
        }

        foreach ( $records as $record ) {
            $formatted[] = $this->format_record_output( $record );
        }

        return $formatted;
    }
}
