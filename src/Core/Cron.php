<?php

namespace SwiftSearch\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Cron
{
    /**
     * Initialize Cron.
     */
    public function init()
    {
        add_action('swift_search_daily_cleanup', array($this, 'cleanup_logs'));

        // Schedule if not already scheduled
        if (!wp_next_scheduled('swift_search_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'swift_search_daily_cleanup');
        }
    }

    /**
     * Cleanup old logs.
     * Prune queries with low hits that haven't been searched in 60 days.
     */
    public function cleanup_logs()
    {
        global $wpdb;
        $table_name = DB::get_table_name();

        // Delete queries updated > 60 days ago AND that are relatively rare (frequency < 5)
        // We keep popular queries forever (or until another policy matches)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}swift_search_logs WHERE updated_at < %s AND frequency < %d",
                gmdate('Y-m-d H:i:s', strtotime('-30 days')),
                5
            )
        );
    }
}
