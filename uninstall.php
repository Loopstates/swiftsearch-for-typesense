<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete Settings
delete_option('swift_search_settings');

// We might want to remove the Typesense collection too?
// For now, let's keep it safe and manual, or we could add a checkbox option in settings later.
// However, if the user "Deletes" the plugin, they usually expect data gone.
// Since we don't have the autoloader easily here without bootstrapping, we might skip the API call.
// A simpler way is to rely on the user having hit "Reset Index" in the admin before uninstalling if they wanted it gone from TS.
// This script just cleans WP DB.
