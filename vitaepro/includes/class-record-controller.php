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

    /**
     * @var string
     */
    private $last_error = '';

    /**
     * @var int
     */
    private $last_insert_id = 0;

    public function __construct() {
        global $wpdb;

        $this->table_name       = $wpdb->prefix . 'vitaepro_records';
        $this->categories_table = $wpdb->prefix . 'vitaepro_categories';
    }

    /**
     * Return the last error message.
     *
     * @return string
     */
    public function get_last_error() {
        return $this->last_error;
    }

    /**
     * Return the last inserted record ID.
     *
     * @return int
     */
    public function get_last_insert_id() {
        return $this->last_insert_id;
    }

    /**
     * Create a new record.
     *
     * @param int   $category_id Category ID.
     * @param int   $user_id     User ID.
     * @param array $data_array  Record data.
     *
     * @return bool True on success, false on failure.
     */
    public function create_record( $category_id, $user_id, $data_array ) {
        global $wpdb;

        $this->reset_last_operation();

        $category_id = absint( $category_id );
        $user_id     = absint( $user_id );

        if ( $category_id <= 0 ) {
            $this->last_error = __( 'Categoría inválida.', 'vitaepro' );
            return false;
        }

        $category = $this->get_category( $category_id );

        if ( ! $category ) {
            $this->last_error = __( 'La categoría seleccionada no existe.', 'vitaepro' );
            return false;
        }

        $record_data = $this->prepare_record_data( $category, $data_array );
        $now         = current_time( 'mysql' );

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
            $this->last_error = __( 'No se pudo crear el registro.', 'vitaepro' );
            return false;
        }

        $this->last_insert_id = (int) $wpdb->insert_id;

        return true;
    }

    /**
     * Update an existing record.
     *
     * @param int   $record_id  Record ID.
     * @param array $data_array Record data.
     *
     * @return bool True on success, false on failure.
     */
    public function update_record( $record_id, $data_array ) {
        global $wpdb;

        $this->reset_last_operation();

        $record_id = absint( $record_id );

        if ( $record_id <= 0 ) {
            $this->last_error = __( 'ID de registro inválido.', 'vitaepro' );
            return false;
        }

        $record = $this->get_record( $record_id );

        if ( ! $record ) {
            $this->last_error = __( 'El registro solicitado no existe.', 'vitaepro' );
            return false;
        }

        $category = $this->get_category( (int) $record->category_id );

        if ( ! $category ) {
            $this->last_error = __( 'La categoría asociada al registro no existe.', 'vitaepro' );
            return false;
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
            $this->last_error = __( 'No se pudo actualizar el registro.', 'vitaepro' );
            return false;
        }

        return true;
    }

    /**
     * Delete a record.
     *
     * @param int $record_id Record ID.
     *
     * @return bool True on success, false on failure.
     */
    public function delete_record( $record_id ) {
        global $wpdb;

        $this->reset_last_operation();

        $record_id = absint( $record_id );

        if ( $record_id <= 0 ) {
            $this->last_error = __( 'ID de registro inválido.', 'vitaepro' );
            return false;
        }

        $deleted = $wpdb->delete(
            $this->table_name,
            array( 'id' => $record_id ),
            array( '%d' )
        );

        if ( false === $deleted ) {
            $this->last_error = __( 'No se pudo eliminar el registro.', 'vitaepro' );
            return false;
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

        $record = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $record_id
            )
        );

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
     * Reset last error and insert ID state.
     */
    private function reset_last_operation() {
        $this->last_error     = '';
        $this->last_insert_id = 0;
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
            $type      = isset( $definition['type'] ) ? sanitize_key( $definition['type'] ) : 'text';

            switch ( $type ) {
                case 'textarea':
                    $value = sanitize_textarea_field( wp_unslash( $raw_value ) );
                    break;
                case 'number':
                    $raw_value = is_array( $raw_value ) ? '' : $raw_value;
                    $raw_value = '' === $raw_value ? '' : wp_unslash( $raw_value );
                    $value     = '' !== $raw_value && is_numeric( $raw_value ) ? 0 + $raw_value : '';
                    break;
                case 'date':
                    $value = sanitize_text_field( wp_unslash( $raw_value ) );
                    break;
                case 'checkbox':
                    $value = ! empty( $raw_value ) ? 1 : 0;
                    break;
                case 'select':
                    $value   = '';
                    $options = isset( $definition['options'] ) && is_array( $definition['options'] ) ? $definition['options'] : array();
                    $raw     = sanitize_text_field( wp_unslash( $raw_value ) );

                    if ( ! empty( $options ) ) {
                        $normalized_options = array();

                        foreach ( $options as $option_key => $option_value ) {
                            if ( is_array( $option_value ) && isset( $option_value['value'] ) ) {
                                $normalized_options[ (string) $option_value['value'] ] = true;
                            } elseif ( is_string( $option_key ) ) {
                                $normalized_options[ (string) $option_key ] = true;
                                if ( is_string( $option_value ) ) {
                                    $normalized_options[ (string) $option_value ] = true;
                                }
                            } elseif ( is_string( $option_value ) ) {
                                $normalized_options[ (string) $option_value ] = true;
                            }
                        }

                        if ( isset( $normalized_options[ $raw ] ) ) {
                            $value = $raw;
                        }
                    } else {
                        $value = $raw;
                    }
                    break;
                case 'text':
                default:
                    $value = sanitize_text_field( wp_unslash( $raw_value ) );
                    break;
            }

            $prepared[ $column_key ] = $value;
        }

        if ( array_key_exists( 'fecha_inicio', $prepared ) && array_key_exists( 'fecha_fin', $prepared ) ) {
            $tiempo = array(
                'anios' => 0,
                'meses' => 0,
                'dias'  => 0,
            );

            if ( function_exists( 'vitaepro_calcular_tiempo' ) && ! empty( $prepared['fecha_inicio'] ) && ! empty( $prepared['fecha_fin'] ) ) {
                $calculado = vitaepro_calcular_tiempo( $prepared['fecha_inicio'], $prepared['fecha_fin'] );

                if ( is_array( $calculado ) ) {
                    foreach ( array_keys( $tiempo ) as $tiempo_key ) {
                        if ( isset( $calculado[ $tiempo_key ] ) && is_numeric( $calculado[ $tiempo_key ] ) ) {
                            $tiempo[ $tiempo_key ] = (int) $calculado[ $tiempo_key ];
                        }
                    }
                }
            }

            $prepared['total_anios'] = $tiempo['anios'];
            $prepared['total_meses'] = $tiempo['meses'];
            $prepared['total_dias']  = $tiempo['dias'];

            foreach ( array( 'anios', 'meses', 'dias' ) as $time_key ) {
                if ( array_key_exists( $time_key, $prepared ) ) {
                    $prepared[ $time_key ] = $tiempo[ $time_key ];
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
