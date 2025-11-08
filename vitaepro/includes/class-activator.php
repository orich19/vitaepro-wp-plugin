<?php
/**
 * Handles plugin activation tasks such as creating custom database tables.
 *
 * @package VitaePro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Activator class responsible for creating plugin database tables.
 */
class VitaePro_Activator {
    /**
     * Runs on plugin activation.
     *
     * @return void
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $categories_table = $wpdb->prefix . 'vitaepro_categories';
        $records_table    = $wpdb->prefix . 'vitaepro_records';

        $categories_sql = "CREATE TABLE {$categories_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            table_type varchar(191) NOT NULL,
            description text NULL,
            schema_json longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY table_type (table_type)
        ) ENGINE=InnoDB {$charset_collate};";

        $records_sql = "CREATE TABLE {$records_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            category_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            data_json longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY category_id (category_id),
            KEY user_id (user_id)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $categories_sql );
        dbDelta( $records_sql );

        require_once plugin_dir_path(__FILE__) . 'class-vitaepro-pdf.php';

        if ( class_exists( 'VitaePro_PDF' ) ) {
            VitaePro_PDF::register_rewrite_rules();
        }

        flush_rewrite_rules();
    }
}
