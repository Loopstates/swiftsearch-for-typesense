<?php
namespace SwiftSearch\Engine;

/**
 * Class ConfigLoader
 *
 * Defines the Search Configuration locally.
 * "Pro" features are enabled strictly via Freemius SDK check.
 * ABSOLUTELY NO EXTERNAL SERVER CALLS.
 */
class ConfigLoader
{

    /**
     * Get the configuration (Locally defined, gated by Freemius).
     *
     * @return array
     */
    public function get_config()
    {
        // Check if Freemius Premium is active
        if (function_exists('swift_search_fs') && swift_search_fs()->can_use_premium_code()) {
            return $this->get_pro_config();
        }

        return $this->get_free_config();
    }

    /**
     * Pro Configuration (Smart Defaults).
     * Contains features only available to paid users.
     *
     * @return array
     */
    private function get_pro_config()
    {
        return array(
            'ranking_rules' => array(
                'words',
                'typo',
                'proximity',
                'attribute',
                'exactness',
                'promoted_products(stock_status: asc)' // Pro feature: Stock boosting
            ),
            'synonyms' => array(
                // Example of Pro-exclusive built-in value
                array('root' => 'laptop', 'synonyms' => array('notebook', 'macbook')),
                array('root' => 'shirt', 'synonyms' => array('t-shirt', 'tee', 'top'))
            ),
            'weights' => array(
                'post_title' => 8,       // Boosted title for Pro
                'post_content' => 2,
                'sku' => 4,              // SKU support
                'category' => 2,
                'tag' => 2
            ),
            'default_sorting_field' => 'published_at',
            'enable_facets' => true // Enable faceted navigation
        );
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
