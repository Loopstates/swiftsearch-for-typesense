<?php
namespace SwiftSearch\Admin;

use SwiftSearch\Core\Gatekeeper;

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
            __('SwiftSearch', 'swiftsearch-for-typesense'),
            __('SwiftSearch', 'swiftsearch-for-typesense'),
            'manage_options',
            'swift-search',
            array($this, 'render_app'),
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjxwYXRoIGQ9Ik0xMyAyTDMgMTRoOWwtMSAxMiAxMC0xMmgtOWwxLTEyeiIvPjwvc3ZnPg==',
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
        if ($hook !== 'toplevel_page_swift-search') {
            return;
        }

        // Chart.js for Analytics
        wp_enqueue_script(
            'chart-js',
            SWIFT_SEARCH_URL . 'assets/vendor/chart.js',
            array(),
            '4.4.1',
            true
        );

        // Main Admin CSS
        wp_enqueue_style(
            'swift-search-admin',
            SWIFT_SEARCH_URL . 'assets/css/admin.css',
            array(),
            SWIFT_SEARCH_VERSION . '.' . time()
        );

        // Admin JS
        wp_enqueue_script(
            'swift-search-admin',
            SWIFT_SEARCH_URL . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            SWIFT_SEARCH_VERSION . '.' . time(),
            true
        );

        // Configuration State
        $is_paying = true;
        $upgrade_url = '#';

        // Check Schema Mismatch
        $schema_mismatch = get_option('swift_search_schema_mismatch', false);

        // Index Status
        $index_status = get_option('swift_search_index_status', array());

        // Localize Script for JS Data
        // Localize Script for JS Data
        $settings = get_option('swift_search_settings', array());

        wp_localize_script('swift-search-admin', 'swiftSearchConfig', array(
            'isConnected' => Gatekeeper::is_connected(),
            'canIndex' => Gatekeeper::can_index(),
            'apiUrl' => rest_url('swift-search/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'cookies' => $_COOKIE,
            'sslverify' => apply_filters('swift_search_https_local_ssl_verify', false),
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
                'synonym_collections' => isset($settings['synonym_collections']) ? $settings['synonym_collections'] : array('posts'),
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
            'facets_config' => isset($settings['facets_config']) ? $settings['facets_config'] : array(),
            'styling' => isset($settings['styling']) ? $settings['styling'] : array(),
            'available_post_types' => $this->get_public_post_types(),
            'available_taxonomies' => $this->get_public_taxonomies(),
            'available_collections' => $this->get_active_collections(), 
            'texts' => array(
                'connecting' => __('Connecting to Typesense...', 'swiftsearch-for-typesense'),
                'success' => __('Connected Successfully!', 'swiftsearch-for-typesense'),
                'error' => __('Connection Failed.', 'swiftsearch-for-typesense'),
                'mismatch' => __('⚠️ Schema Out of Sync: Please perform a Full Re-Index now to enable your new fields & facets.', 'swiftsearch-for-typesense'),
                'apiKeyWarning' => __('Security Warning: This looks like an Admin Key. For Frontend Search, please use a Search-Only API Key.', 'swiftsearch-for-typesense'),
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
                'description' => empty($pt->description) ? '' : substr(wp_strip_all_tags((string) $pt->description), 0, 100),
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

            // Realistic filtering: only show taxonomies that have actual terms in the DB
            $term_count = wp_count_terms($tax->name);
            if (!$term_count || (int)$term_count === 0) {
                continue;
            }

            $data[] = array(
                'name' => (string) $tax->name,
                'label' => (string) $tax->label,
                'description' => empty($tax->description) ? '' : substr(wp_strip_all_tags((string) $tax->description), 0, 100),
            );
        }

        return $data;
    }

    /**
     * Get active collections from Typesense (with caching).
     *
     * @return array List of collection names.
     */
    private function get_active_collections()
    {
        $cache_key = 'swift_search_ts_collections';
        $collections = get_transient($cache_key);

        if ($collections !== false) {
            return (array) $collections;
        }

        $settings = get_option('swift_search_settings');
        if (empty($settings['api_key'])) {
            return array();
        }

        $client = new \SwiftSearch\Client\Client($settings);
        $data = $client->request('/collections', 'GET');

        $active = array();
        if (is_array($data)) {
            foreach ($data as $col) {
                if (isset($col['name'])) {
                    $active[] = $col['name'];
                }
            }
        }

        // Cache for 10 mins
        set_transient($cache_key, $active, 10 * MINUTE_IN_SECONDS);

        return $active;
    }

    /**
     * Render the main Single Page Application (SPA) wrapper.
     */
    public function render_app()
    {
        // Prepare Data for View
        $data = array(
            'available_post_types' => $this->get_public_post_types(),
        );

        require_once SWIFT_SEARCH_PATH . 'templates/admin/app.php';
    }

}
