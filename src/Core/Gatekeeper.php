<?php

namespace SwiftSearch\Core;

if (!defined('ABSPATH')) {
    exit;
}

use SwiftSearch\Client\Client;

/**
 * Class Gatekeeper
 * 
 * Central Authority for Validation and Security.
 * "None shall pass without the Gatekeeper's approval."
 */
class Gatekeeper
{
    private static $can_use_pro_cache = null;

    /**
     * Check if Typesense is properly connected.
     * 
     * @param bool $ping_health If true, performs a live HTTP check (expensive).
     * @return bool
     */
    public static function is_connected($ping_health = false)
    {
        $settings = get_option('swift_search_settings');

        // 1. Structural Check
        if (empty($settings['api_key']) || empty($settings['host'])) {
            return false;
        }

        // 2. Health Check (Optional, Cached)
        if ($ping_health) {
            $transient = get_transient('swift_search_health_status');
            if ($transient !== false) {
                return $transient === 'ok';
            }

            // Live Check
            $client = new Client($settings);
            if ($client->test_connection()) {
                set_transient('swift_search_health_status', 'ok', 5 * MINUTE_IN_SECONDS);
                return true;
            } else {
                set_transient('swift_search_health_status', 'fail', 1 * MINUTE_IN_SECONDS);
                return false;
            }
        }

        // Default: Assume connected if config exists (fast check for UI rendering)
        return true;
    }

    /**
     * Check if we are allowed to Index content.
     * 
     * @param string $post_type Optional post type to check limits against.
     * @return bool
     */
    public static function can_index($post_type = null)
    {
        // 1. Must be Connected
        if (!self::is_connected()) {
            return false;
        }

        // 2. Indexing Guardrails (e.g. Licensing / Limits)
        // Future: Check if User has exceeded Free Tier Limit (e.g. 1000 posts)
        // $plan = self::get_plan();
        // if ($plan->is_free && self::get_total_posts() > 1000) return false;

        return true;
    }

    /**
     * Check if Pro features are active.
     * 
     * @param string $feature e.g. 'analytics', 'synonyms', 'pinning'
     * @return bool
     */
    public static function can_use_features($feature = 'pro')
    {
        if (null !== self::$can_use_pro_cache) {
            return self::$can_use_pro_cache;
        }

        // Use Freemius SDK to validate license/plan
        if (function_exists('swift_search_fs')) {
            self::$can_use_pro_cache = (bool) swift_search_fs()->can_use_premium_code();
            return self::$can_use_pro_cache;
        }

        return false;
    }

    /**
     * Check if User is Admin (Capability Check).
     * 
     * @return bool
     */
    public static function is_admin()
    {
        return current_user_can('manage_options');
    }
}
