<?php

namespace SwiftSearch\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DB
 *
 * Handles database operations and schema management.
 */
class DB
{
    /**
     * Table name for logs.
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'swift_search_logs';
    }

    /**
     * Install database tables.
     */
    public static function install()
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            query varchar(191) NOT NULL,
            frequency bigint(20) NOT NULL DEFAULT 1,
            result_count int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY query (query)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
