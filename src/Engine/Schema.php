<?php
namespace SwiftSearch\Engine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Schema
 * 
 * Defines the Typesense Collection Schema.
 */
class Schema
{
    const VERSION = '1.0.1';

    /**
     * Get the schema definition for the main collection.
     *
     * @param array $config Configuration object.
     * @return array
     */
    public static function get_schema($config = array())
    {
        $fields = array(
            array('name' => 'post_id', 'type' => 'int64'),
            array('name' => 'post_title', 'type' => 'string'),
            array('name' => 'post_content', 'type' => 'string'),
            array('name' => 'post_excerpt', 'type' => 'string'),
            array('name' => 'post_type', 'type' => 'string', 'facet' => true),
            array('name' => 'permalink', 'type' => 'string'),
            array('name' => 'thumbnail_url', 'type' => 'string', 'optional' => true),
            array('name' => 'category', 'type' => 'string[]', 'facet' => isset($config['enable_facets']) ? $config['enable_facets'] : true, 'optional' => true),
            array('name' => 'tag', 'type' => 'string[]', 'facet' => true, 'optional' => true),
            array('name' => 'published_at', 'type' => 'int64', 'sort' => true),
            array('name' => 'author_name', 'type' => 'string', 'facet' => true, 'optional' => true),
        );

        // WooCommerce Fields
        if (class_exists('WooCommerce')) {
            $fields[] = array('name' => 'price', 'type' => 'float', 'facet' => true, 'optional' => true);
            $fields[] = array('name' => 'sku', 'type' => 'string', 'optional' => true);
            $fields[] = array('name' => 'in_stock', 'type' => 'bool', 'facet' => true, 'optional' => true);
        }

        // Smart Config Overrides
        $default_sort = isset($config['default_sorting_field']) ? $config['default_sorting_field'] : 'published_at';

        return array(
            'name' => 'posts',
            'fields' => $fields,
            'default_sorting_field' => $default_sort,
        );
    }

    /**
     * Get the schema definition for the terms collection.
     */
    public static function get_terms_schema($config = array())
    {
        return array(
            'name' => 'terms',
            'fields' => array(
                array('name' => 'term_id', 'type' => 'int64'),
                array('name' => 'name', 'type' => 'string'), // Facet? Maybe not needed for name
                array('name' => 'slug', 'type' => 'string'),
                array('name' => 'taxonomy', 'type' => 'string', 'facet' => true),
                array('name' => 'url', 'type' => 'string'),
                array('name' => 'count', 'type' => 'int32'),
            ),
            'default_sorting_field' => 'count', // Relevance? Browsing usually by popular
        );
    }

    /**
     * Get the schema definition for the users collection.
     */
    public static function get_users_schema($config = array())
    {
        return array(
            'name' => 'users',
            'fields' => array(
                array('name' => 'user_id', 'type' => 'int64'),
                array('name' => 'display_name', 'type' => 'string'),
                array('name' => 'user_login', 'type' => 'string'),
                array('name' => 'avatar_url', 'type' => 'string', 'optional' => true),
                array('name' => 'url', 'type' => 'string'),
            ),
            // No default sort, relevance is key
        );
    }

    /**
     * Generate a deterministic hash of the current schema definition.
     * Used to detect if the remote/local settings have changed requiring reindex.
     *
     * @param array $config
     * @return string
     */
    public static function get_hash($config)
    {
        $posts = self::get_schema($config);
        $terms = self::get_terms_schema($config);
        $users = self::get_users_schema($config);

        $composite = array('posts' => $posts, 'terms' => $terms, 'users' => $users);

        // Serialize and hash to get unique fingerprint
        return md5(serialize($composite));
    }
}
