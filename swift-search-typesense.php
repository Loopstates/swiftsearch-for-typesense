<?php
/**
 * Plugin Name: SwiftSearch for Typesense
 * Plugin URI:  https://loopstates.com/products/swift-search-typesense/
 * Description: Blazing-fast, typo-tolerant instant search for WordPress, WooCommerce, Custom Post Types (CPTs), and custom taxonomies. Features zero-middleware direct-to-node queries, sidebar facets configurator, synonyms, result weighting, search analytics, and merchandising/result pinning.
 * Version:           1.4.7
 * Tested up to:      7.0
 * Requires PHP:      8.0.0
 * Author:      Loopstates
 * Author URI:  https://loopstates.com
 * License:     GPL-2.0+
 * Text Domain: swift-search-typesense
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
	exit;
}

// Define Constants
define('SWIFT_SEARCH_VERSION', '1.4.7');
define('SWIFT_SEARCH_DB_VERSION', '1.1.0');
define('SWIFT_SEARCH_FILE', __FILE__);
define('SWIFT_SEARCH_PATH', plugin_dir_path(__FILE__));
define('SWIFT_SEARCH_URL', plugin_dir_url(__FILE__));

// Require Autoloader
require_once untrailingslashit(SWIFT_SEARCH_PATH) . '/src/Core/Autoloader.php';

// Initialize Autoloader
\SwiftSearch\Core\Autoloader::register();

// Require Vendor Autoloader (if exists) via a helper, or directly if standard
if (file_exists(untrailingslashit(SWIFT_SEARCH_PATH) . '/vendor/autoload.php')) {
	require_once untrailingslashit(SWIFT_SEARCH_PATH) . '/vendor/autoload.php';
}

// Activation Hook
register_activation_hook(__FILE__, array(\SwiftSearch\Core\DB::class, 'install'));

// Init Plugin
function swift_search_init()
{
	// Load the main plugin instance
	\SwiftSearch\Core\Plugin::instance();
}
add_action('plugins_loaded', 'swift_search_init');



