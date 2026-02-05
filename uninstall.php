<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1. Delete Tables
$table_logs = $wpdb->prefix . 'swift_search_logs';
$table_batch_logs = $wpdb->prefix . 'swift_search_batch_logs';

$wpdb->query("DROP TABLE IF EXISTS $table_logs");
$wpdb->query("DROP TABLE IF EXISTS $table_batch_logs");

// 2. Delete Options
delete_option('swift_search_settings');
delete_option('swift_search_index_status');
delete_option('swift_search_sync_errors');
delete_option('swift_search_db_version');
delete_option('swift_search_debug_injected_v2');

// 3. Clear Async Actions (optional but good practice)
if (class_exists('ActionScheduler_QueueRunner')) {
    \ActionScheduler_QueueRunner::instance()->unhook_dispatch_async_request();
}
// Note: We don't delete Typesense Cloud content here to avoid accidental data loss. User can delete index from UI before uninstall.
