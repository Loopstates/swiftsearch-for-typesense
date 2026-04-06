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
        add_action('wp_head', array($this, 'render_frontend_styles'), 100);
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
            $relevance = isset($settings['relevance']) ? $settings['relevance'] : array();
            $synonym_sets = array();
            if (!empty($relevance['synonyms']) && is_array($relevance['synonyms'])) {
                foreach ($relevance['synonyms'] as $index => $syn) {
                    $synonym_sets[] = "ss-synonym-{$index}";
                }
            }

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
                'pinned_items' => get_option('swift_search_pinned_items', array()),
                'weights' => isset($settings['weights']) ? $settings['weights'] : array(), // Pass Weights
                'synonym_sets' => $synonym_sets, // Pass Synonyms
                'apiUrl' => rest_url('swift-search/v1'),
                'nonce' => wp_create_nonce('wp_rest'),
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
        $settings = get_option('swift_search_settings', array());
        $exp = isset($settings['experience']) ? $settings['experience'] : array();

        // Dynamic defaults from Global Experience
        $default_limit = isset($exp['limit']) ? intval($exp['limit']) : 10;
        $default_thumb = (isset($exp['show_thumbnail']) && $exp['show_thumbnail'] === false) ? 'false' : 'true';
        $default_price = (isset($exp['show_price']) && $exp['show_price'] === false) ? 'false' : 'true';
        $default_excerpt = (isset($exp['show_excerpt']) && $exp['show_excerpt'] === true) ? 'true' : 'false';
        $default_instant = (isset($exp['instant_search']) && $exp['instant_search'] === false) ? 'false' : 'true';

        $a = shortcode_atts(array(
            'placeholder' => __('Search...', 'swift-search-typesense'),
            'limit' => $default_limit,
            'show_thumbnail' => $default_thumb,
            'show_price' => $default_price,
            'show_excerpt' => $default_excerpt,
            'instant_search' => $default_instant,
            'scope' => 'default', // 'posts,terms,users' or 'default'
            'post_types' => '', // comma separated 'product,post'
        ), $atts);

        wp_enqueue_style('swift-search-frontend');
        wp_enqueue_script('swift-search-frontend');

        ob_start();
        ?>
        <div id="swift-search-wrapper" class="ss-wrapper" data-limit="<?php echo esc_attr($a['limit']); ?>"
            data-thumb="<?php echo esc_attr($a['show_thumbnail']); ?>" data-price="<?php echo esc_attr($a['show_price']); ?>"
            data-excerpt="<?php echo esc_attr($a['show_excerpt']); ?>"
            data-instant="<?php echo esc_attr($a['instant_search']); ?>" data-scope="<?php echo esc_attr($a['scope']); ?>"
            data-post-types="<?php echo esc_attr($a['post_types']); ?>">
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

    /**
     * Render dynamic CSS in frontend head based on plugin settings.
     */
    public function render_frontend_styles() {
        $settings = get_option('swift_search_settings', array());
        $styling = isset($settings['styling']) ? $settings['styling'] : array();

        if (empty($styling)) {
            return;
        }

        $primary = !empty($styling['primary_color']) ? $styling['primary_color'] : '#ff0055';
        $text = !empty($styling['text_color']) ? $styling['text_color'] : '#1f2937';
        $bg = !empty($styling['card_bg']) ? $styling['card_bg'] : '#ffffff';
        $radius = isset($styling['border_radius']) ? (int)$styling['border_radius'] : 16;
        $custom_css = !empty($styling['custom_css']) ? $styling['custom_css'] : '';
        
        $primary_rgb = $this->hex2rgb($primary);
        
        echo "\n<!-- SwiftSearch Custom Styles -->\n";
        echo "<style id='swift-search-dynamic-css'>\n";
        echo "  .ss-wrapper {\n";
        echo "    --ss-primary: {$primary} !important;\n";
        echo "    --ss-primary-rgb: {$primary_rgb[0]}, {$primary_rgb[1]}, {$primary_rgb[2]} !important;\n";
        echo "    --ss-text-main: {$text} !important;\n";
        echo "    --ss-card-bg: {$bg} !important;\n";
        echo "    --ss-radius: {$radius}px !important;\n";
        echo "  }\n";
        if (!empty($custom_css)) {
            echo "  /* User Custom CSS Overrides */\n";
            echo "  " . wp_strip_all_tags($custom_css) . "\n";
        }
        echo "</style>\n";
    }

    /**
     * Helper: Convert Hex to RGB array.
     */
    private function hex2rgb($hex) {
        $hex = str_replace("#", "", $hex);
        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $r = is_numeric($r) ? $r : 0;
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $g = is_numeric($g) ? $g : 0;
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
            $b = is_numeric($b) ? $b : 0;
        } else {
            $r = hexdec(substr($hex,0,2));
            $r = is_numeric($r) ? $r : 0;
            $g = hexdec(substr($hex,2,2));
            $g = is_numeric($g) ? $g : 0;
            $b = hexdec(substr($hex,4,2));
            $b = is_numeric($b) ? $b : 0;
        }
        return array($r, $g, $b);
    }
}
