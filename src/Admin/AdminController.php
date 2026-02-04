<?php
namespace SwiftSearch\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminController
 *
 * Handles backend UI and menus.
 */
class AdminController
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Register the admin menu.
     */
    public function register_menu()
    {
        add_menu_page(
            __('SwiftSearch', 'swift-search-typesense'),
            __('SwiftSearch', 'swift-search-typesense'),
            'manage_options',
            'swift-search',
            array($this, 'render_app'),
            'dashicons-lightning',
            30
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets($hook)
    {
        if ('toplevel_page_swift-search' !== $hook) {
            return;
        }

        // Main Admin CSS
        wp_enqueue_style(
            'swift-search-admin',
            SWIFT_SEARCH_URL . 'assets/css/admin.css',
            array(),
            SWIFT_SEARCH_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'swift-search-admin',
            SWIFT_SEARCH_URL . 'assets/js/admin.js',
            array('jquery'),
            SWIFT_SEARCH_VERSION,
            true
        );

        // Freemius State
        $is_paying = function_exists('swift_search_fs') ? swift_search_fs()->can_use_premium_code() : false;
        $upgrade_url = function_exists('swift_search_fs') ? swift_search_fs()->get_upgrade_url() : '#';

        // Check Schema Mismatch
        $schema_mismatch = get_option('swift_search_schema_mismatch', false);

        // Index Status
        $index_status = get_option('swift_search_index_status', array());

        // Localize Script for JS Data
        // Localize Script for JS Data
        $settings = get_option('swift_search_settings', array());

        wp_localize_script('swift-search-admin', 'swiftSearchConfig', array(
            'apiUrl' => rest_url('swift-search/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'plan' => array(
                'isPaying' => $is_paying,
                'upgradeUrl' => $upgrade_url,
            ),
            'status' => array(
                'schemaMismatch' => (bool) $schema_mismatch,
                'backgroundIndex' => $index_status,
                'overrideDefault' => (bool) get_option('swift_search_override_default', false),
            ),
            'relevance' => array(
                'weights' => isset($settings['weights']) ? $settings['weights'] : array(),
                'synonyms' => isset($settings['synonyms']) ? $settings['synonyms'] : array(),
            ),
            'experience' => isset($settings['experience']) ? $settings['experience'] : array(),
            'credentials' => array(
                'host' => isset($settings['host']) ? $settings['host'] : '',
                'port' => isset($settings['port']) ? $settings['port'] : '443',
                'protocol' => isset($settings['protocol']) ? $settings['protocol'] : 'https',
                'api_key' => isset($settings['api_key']) ? $settings['api_key'] : '',
                'search_key' => isset($settings['search_key']) ? $settings['search_key'] : '',
            ),
            'indexed_post_types' => isset($settings['indexed_post_types']) ? $settings['indexed_post_types'] : array('post', 'page', 'product'),
            'indexed_taxonomies' => isset($settings['indexed_taxonomies']) ? $settings['indexed_taxonomies'] : array('category', 'post_tag', 'product_cat'),
            'indexed_users' => isset($settings['indexed_users']) ? (bool) $settings['indexed_users'] : false,
            'custom_fields' => isset($settings['custom_fields']) ? $settings['custom_fields'] : array(),
            'facets_config' => isset($settings['facets_config']) ? $settings['facets_config'] : array(), // New
            'available_post_types' => $this->get_public_post_types(),
            'available_taxonomies' => $this->get_public_taxonomies(),
            'texts' => array(
                'connecting' => __('Connecting to Typesense...', 'swift-search-typesense'),
                'success' => __('Connected Successfully!', 'swift-search-typesense'),
                'error' => __('Connection Failed.', 'swift-search-typesense'),
                'mismatch' => __('Critical: Schema Version Mismatch. Please Re-Index immediately to restore functionality.', 'swift-search-typesense'),
                'apiKeyWarning' => __('Security Warning: This looks like an Admin Key. For Frontend Search, please use a Search-Only API Key.', 'swift-search-typesense'),
            ),
        ));
    }

    /**
     * Get list of public post types.
     *
     * @return array List of post types with labels.
     */
    private function get_public_post_types()
    {
        // Reverted to Public Only as per user request
        $args = array(
            'public' => true,
        );
        $output = 'objects';
        $post_types = get_post_types($args, $output);

        $data = array();

        // Always useful types
        $exclude = array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation');

        foreach ($post_types as $pt) {
            if (in_array($pt->name, $exclude)) {
                continue;
            }
            $data[] = array(
                'name' => (string) $pt->name,
                'label' => (string) $pt->label,
                'description' => empty($pt->description) ? '' : substr(strip_tags((string) $pt->description), 0, 100),
            );
        }

        return $data;
    }

    /**
     * Get list of public taxonomies.
     *
     * @return array List of taxonomies.
     */
    private function get_public_taxonomies()
    {
        $args = array(
            'public' => true,
        );
        $output = 'objects';
        $taxonomies = get_taxonomies($args, $output);

        $data = array();
        $exclude = array('nav_menu', 'link_category', 'post_format', 'wp_theme_styles');

        foreach ($taxonomies as $tax) {
            if (in_array($tax->name, $exclude)) {
                continue;
            }
            $data[] = array(
                'name' => (string) $tax->name,
                'label' => (string) $tax->label,
                'description' => empty($tax->description) ? '' : substr(strip_tags((string) $tax->description), 0, 100),
            );
        }

        return $data;
    }

    /**
     * Render the main Single Page Application (SPA) wrapper.
     */
    public function render_app()
    {
        // Enforce Freemius Registration
        if (function_exists('swift_search_fs')) {
            if (!swift_search_fs()->is_registered()) {
                // Redirect logic or Show SDK Connect Page
                swift_search_fs()->_connect_page_render();
                return;
            }
        }

        require_once SWIFT_SEARCH_PATH . 'templates/admin/app.php';
    }
}
