<?php
/**
 * Plugin Name: SwiftSearch - Typesense Search for WordPress
 * Plugin URI:  https://loopstates.com/products/swift-search-typesense/
 * Description: Extremely fast, client-side search for WordPress & WooCommerce powered by Typesense.
 * Version:           1.0.5
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
define('SWIFT_SEARCH_VERSION', '1.0.5');
define('SWIFT_SEARCH_FILE', __FILE__);
define('SWIFT_SEARCH_PATH', plugin_dir_path(__FILE__));
define('SWIFT_SEARCH_URL', plugin_dir_url(__FILE__));

// Require Autoloader
require_once SWIFT_SEARCH_PATH . 'src/Core/Autoloader.php';

// Initialize Autoloader
\SwiftSearch\Core\Autoloader::register();

// Require Vendor Autoloader (if exists) via a helper, or directly if standard
if (file_exists(SWIFT_SEARCH_PATH . 'vendor/autoload.php')) {
	require_once SWIFT_SEARCH_PATH . 'vendor/autoload.php';
}

// Initialize Freemius
function swift_search_fs()
{
	global $swift_search_fs;

	if (!isset($swift_search_fs)) {
		// Include Freemius SDK.
		require_once dirname(__FILE__) . '/vendor/freemius/wordpress-sdk/start.php';

		$swift_search_fs = fs_dynamic_init(array(
			'id' => '22923',
			'slug' => 'swift-search-typesense',
			'type' => 'plugin',
			'public_key' => 'pk_21f3e36876df9e81ac3203b9f16f5',
			'is_premium' => true,
			'has_addons' => false,
			'has_paid_plans' => true,
			'menu' => array(
				'slug' => 'swift-search',
				'first-path' => 'admin.php?page=swift-search',
				'support' => false,
			),
			'is_live' => true,
		));
	}

	return $swift_search_fs;
}

// Activation Hook
register_activation_hook(__FILE__, array('\SwiftSearch\Core\DB', 'install'));

// Init Plugin
function swift_search_init()
{
	// Hook to Freemius here
	swift_search_fs();

	// Load the main plugin instance
	\SwiftSearch\Core\Plugin::instance();
}
add_action('plugins_loaded', 'swift_search_init');
