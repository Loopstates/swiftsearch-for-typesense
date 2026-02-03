<?php

namespace SwiftSearch\Core;

use SwiftSearch\Admin\AdminController;
use SwiftSearch\Frontend\SearchController;
use SwiftSearch\Engine\EngineController;
use SwiftSearch\Integrations\IntegrationManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 *
 * Main plugin container.
 */
class Plugin
{

    /**
     * Instance of this class.
     *
     * @var Plugin
     */
    protected static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @return Plugin
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Plugin constructor.
     */
    private function __construct()
    {
        $this->define_hooks();
        $this->load_modules();
    }

    /**
     * Define basic hooks.
     */
    private function define_hooks()
    {
        // Localization
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Load plugin modules.
     */
    private function load_modules()
    {
        // Admin Area
        if (is_admin()) {
            new AdminController();
        }

        // API
        new \SwiftSearch\Api\RestController();

        // Core Engine (Indexing, Sync, etc.)
        new EngineController();

        // Frontend Search
        if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            new SearchController();
        }

        // Integrations
        new IntegrationManager();
    }

    /**
     * Load text domain.
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'swift-search-typesense',
            false,
            dirname(plugin_basename(SWIFT_SEARCH_FILE)) . '/languages'
        );
    }
}
