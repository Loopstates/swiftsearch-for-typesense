<?php
namespace SwiftSearch\Engine;

/**
 * Class ConfigLoader
 *
 * Defines the Search Configuration locally.
 * All features are enabled by default.
 * ABSOLUTELY NO EXTERNAL SERVER CALLS.
 */
class ConfigLoader
{

    /**
     * Get the configuration (Locally defined).
     *
     * @return array
     */
    public function get_config()
    {
        return $this->get_pro_config();
    }


    /**
     * Get default post types to index.
     *
     * @return array
     */
    public static function get_default_post_types()
    {
        $types = array('post', 'page');
        if (class_exists('WooCommerce')) {
            $types[] = 'product';
        }
        return apply_filters('swift_search_default_post_types', $types);
    }

    /**
     * Get default taxonomies to index.
     *
     * @return array
     */
    public static function get_default_taxonomies()
    {
        $taxonomies = array('category', 'post_tag');
        if (class_exists('WooCommerce')) {
            $taxonomies[] = 'product_cat';
        }
        return apply_filters('swift_search_default_taxonomies', $taxonomies);
    }

    /**
     * Pro Configuration (Smart Defaults).
     * Contains features only available to paid users.
     *
     * @return array
     */
    private function get_pro_config()
    {
        $saved = get_option('swift_search_settings', array());

        $default_post_types = self::get_default_post_types();
        $default_taxonomies = self::get_default_taxonomies();

        // Defaults
        $defaults = array(
            'ranking_rules' => array('words', 'typo', 'proximity', 'attribute', 'exactness', 'promoted_products(stock_status: asc)'),
            'synonyms' => array(
                array('root' => 'laptop', 'synonyms' => array('notebook', 'macbook'))
            ),
            'weights' => array(
                'post_title' => 8,
                'post_content' => 2,
                'sku' => 4,
                'category' => 2,
                'tag' => 2
            ),
            'default_sorting_field' => 'published_at',
            'enable_facets' => true,
            'indexed_post_types' => $default_post_types,
            'indexed_taxonomies' => $default_taxonomies,
        );

        // Merge User Settings
        if (!empty($saved['weights'])) {
            $defaults['weights'] = $saved['weights'];
        }

        if (isset($saved['synonyms']) && is_array($saved['synonyms'])) {
            $defaults['synonyms'] = $saved['synonyms'];
        }

        // --- NEW: Merge Facets & Content Config ---
        if (isset($saved['facets_config']) && is_array($saved['facets_config'])) {
            $defaults['facets_config'] = $saved['facets_config'];
        }

        if (isset($saved['indexed_post_types'])) {
            $defaults['indexed_post_types'] = (array)$saved['indexed_post_types'];
        }

        if (isset($saved['indexed_taxonomies'])) {
            $defaults['indexed_taxonomies'] = (array)$saved['indexed_taxonomies'];
        }

        if (isset($saved['experience'])) {
            $defaults['experience'] = (array)$saved['experience'];
        }

        if (isset($saved['custom_fields'])) {
            $defaults['custom_fields'] = (array)$saved['custom_fields'];
        }

        // Future: Ranking Rules could also be merged here

        return $defaults;
    }

    /**
     * Free Configuration (Basic Defaults).
     *
     * @return array
     */
    private function get_free_config()
    {
        return array(
            'ranking_rules' => array('words', 'typo', 'proximity', 'attribute', 'exactness'),
            'synonyms' => array(), // No synonyms for free
            'weights' => array(
                'post_title' => 4,
                'post_content' => 1,
                // SKU is less prioritized or ignored in free tuning
            ),
            'default_sorting_field' => 'published_at',
            'enable_facets' => false
        );
    }
}
