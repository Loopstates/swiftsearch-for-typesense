<?php

namespace SwiftSearch\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SearchController
 *
 * Handles frontend search functionality.
 */
class SearchController
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_shortcode('swift_search', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('get_search_form', array($this, 'override_search_form'));
    }

    /**
     * Enqueue assets.
     */
    public function enqueue_assets()
    {
        wp_register_style(
            'swift-search-frontend',
            SWIFT_SEARCH_URL . 'assets/css/search.css',
            array(),
            SWIFT_SEARCH_VERSION
        );

        wp_register_script(
            'swift-search-frontend',
            SWIFT_SEARCH_URL . 'assets/js/search.js',
            array(),
            SWIFT_SEARCH_VERSION,
            true
        );

        // Pass Config
        $settings = get_option('swift_search_settings', array());
        if (!empty($settings['search_key'])) {
            wp_localize_script('swift-search-frontend', 'swiftSearchVars', array(
                'host' => $settings['host'],
                'port' => $settings['port'],
                'protocol' => $settings['protocol'],
                'apiKey' => $settings['search_key'],
                'collection' => 'posts',
                'indexed_taxonomies' => isset($settings['indexed_taxonomies']) ? $settings['indexed_taxonomies'] : array(),
                'indexed_users' => isset($settings['indexed_users']) ? (bool) $settings['indexed_users'] : false,
                'experience' => isset($settings['experience']) ? $settings['experience'] : array(),
                'facets_config' => isset($settings['facets_config']) ? $settings['facets_config'] : array(),
                'custom_fields' => isset($settings['custom_fields']) ? $settings['custom_fields'] : array(),
                'apiUrl' => rest_url('swift-search/v1'),
            ));
        }
    }

    /**
     * Override default WordPress search form.
     *
     * @param string $form The existing search form HTML.
     * @return string The modified search form HTML.
     */
    public function override_search_form($form)
    {
        // Check if override is enabled
        $override = get_option('swift_search_override_default', false);
        if (!$override) {
            return $form;
        }

        // Return our shortcode output
        return $this->render_shortcode(array());
    }

    /**
     * Render Shortcode.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function render_shortcode($atts)
    {
        $a = shortcode_atts(array(
            'placeholder' => __('Search...', 'swift-search-typesense'),
            'limit' => 10,
            'show_thumbnail' => 'true',
            'show_price' => 'true',
            'show_excerpt' => 'false',
            'instant_search' => 'default', // 'true', 'false', or 'default' (fallback to global)
            'scope' => 'default', // 'posts,terms,users' or 'default'
        ), $atts);

        wp_enqueue_style('swift-search-frontend');
        wp_enqueue_script('swift-search-frontend');

        ob_start();
        ?>
        <div id="swift-search-wrapper" class="ss-wrapper" data-limit="<?php echo esc_attr($a['limit']); ?>"
            data-thumb="<?php echo esc_attr($a['show_thumbnail']); ?>" data-price="<?php echo esc_attr($a['show_price']); ?>"
            data-excerpt="<?php echo esc_attr($a['show_excerpt']); ?>"
            data-instant="<?php echo esc_attr($a['instant_search']); ?>" data-scope="<?php echo esc_attr($a['scope']); ?>">
            <div class="ss-search-box">
                <input type="text" id="ss-search-input" placeholder="<?php echo esc_attr($a['placeholder']); ?>"
                    autocomplete="off">
                <span class="ss-loader" style="display:none;"></span>
            </div>
            <div class="ss-results-container" style="display:none;">
                <div class="ss-facets" id="ss-facets"></div>
                <div class="ss-hits" id="ss-hits"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
