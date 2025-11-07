<?php
/**
 * VitaePro Table Types definition.
 *
 * @package VitaePro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class VitaePro_Table_Types
 *
 * Provides access to the table type definitions used throughout the plugin.
 */
class VitaePro_Table_Types {

    /**
     * Return all registered table types.
     *
     * Each table type definition contains a human readable label and a list of
     * column definitions used to generate dynamic forms as well as validate and
     * persist record data.
     *
     * @return array<string, array{label:string, columns:array<string, array{label:string, type:string, description?:string}>}>
     */
    public static function get_table_types() {
        return array(
            'formacion_academica'     => array(
                'label'   => __( 'Formación Académica', 'vitaepro' ),
                'columns' => array(
                    'grado_academico' => array(
                        'label'       => __( 'Grado Académico', 'vitaepro' ),
                        'type'        => 'text',
                        'description' => __( 'Ej. Licenciatura, Maestría.', 'vitaepro' ),
                    ),
                    'titulo'           => array(
                        'label' => __( 'Título', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'universidad'      => array(
                        'label' => __( 'Universidad', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'nro_emision'      => array(
                        'label' => __( 'Número de Emisión', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'fecha_emision'    => array(
                        'label' => __( 'Fecha de Emisión', 'vitaepro' ),
                        'type'  => 'date',
                    ),
                ),
            ),
            'formacion_complementaria' => array(
                'label'   => __( 'Formación Complementaria', 'vitaepro' ),
                'columns' => array(
                    'tipo'          => array(
                        'label' => __( 'Tipo', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'nombre_curso'  => array(
                        'label' => __( 'Nombre del Curso', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'institucion'   => array(
                        'label' => __( 'Institución', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'fecha_inicio'  => array(
                        'label' => __( 'Fecha de Inicio', 'vitaepro' ),
                        'type'  => 'date',
                    ),
                    'fecha_fin'     => array(
                        'label' => __( 'Fecha de Fin', 'vitaepro' ),
                        'type'  => 'date',
                    ),
                    'horas'         => array(
                        'label' => __( 'Horas', 'vitaepro' ),
                        'type'  => 'number',
                    ),
                ),
            ),
            'experiencia_docente'      => array(
                'label'   => __( 'Experiencia Docente', 'vitaepro' ),
                'columns' => array(
                    'universidad'   => array(
                        'label' => __( 'Universidad', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'area'          => array(
                        'label' => __( 'Área', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'asignatura'    => array(
                        'label' => __( 'Asignatura', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'carga_horaria' => array(
                        'label' => __( 'Carga Horaria', 'vitaepro' ),
                        'type'  => 'number',
                    ),
                    'fecha_inicio'  => array(
                        'label' => __( 'Fecha de Inicio', 'vitaepro' ),
                        'type'  => 'date',
                    ),
                    'fecha_fin'     => array(
                        'label' => __( 'Fecha de Fin', 'vitaepro' ),
                        'type'  => 'date',
                    ),
                ),
            ),
            'experiencia_laboral'      => array(
                'label'   => __( 'Experiencia Laboral', 'vitaepro' ),
                'columns' => array(
                    'institucion'  => array(
                        'label' => __( 'Institución', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'tipo'         => array(
                        'label' => __( 'Tipo', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'cargo'        => array(
                        'label' => __( 'Cargo', 'vitaepro' ),
                        'type'  => 'text',
                    ),
                    'fecha_inicio' => array(
                        'label' => __( 'Fecha de Inicio', 'vitaepro' ),
                        'type'  => 'date',
                    ),
                    'fecha_fin'    => array(
                        'label' => __( 'Fecha de Fin', 'vitaepro' ),
                        'type'  => 'date',
                    ),
                    'anios'        => array(
                        'label'       => __( 'Años', 'vitaepro' ),
                        'type'        => 'number',
                        'description' => __( 'Se calculará automáticamente.', 'vitaepro' ),
                    ),
                    'meses'        => array(
                        'label'       => __( 'Meses', 'vitaepro' ),
                        'type'        => 'number',
                        'description' => __( 'Se calculará automáticamente.', 'vitaepro' ),
                    ),
                    'dias'         => array(
                        'label'       => __( 'Días', 'vitaepro' ),
                        'type'        => 'number',
                        'description' => __( 'Se calculará automáticamente.', 'vitaepro' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Get a single table type definition by slug.
     *
     * @param string $slug Table type slug.
     *
     * @return array|null
     */
    public static function get_table_type( $slug ) {
        $types = self::get_table_types();

        if ( isset( $types[ $slug ] ) ) {
            return $types[ $slug ];
        }

        return null;
    }

    /**
     * Retrieve the column configuration for a table type.
     *
     * @param string $slug Table type slug.
     *
     * @return array<string, array>|null
     */
    public static function get_columns_for_type( $slug ) {
        $type = self::get_table_type( $slug );

        if ( $type && isset( $type['columns'] ) ) {
            return $type['columns'];
        }

        return null;
    }
}
