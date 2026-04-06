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
    const VERSION = '1.2.3';

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

        // 1. Facets from Config (Universal Bridge)
        // This replaces the old hardcoded WooCommerce and tax_ prefix logic.
        if (isset($config['facets_config']) && is_array($config['facets_config'])) {
            $existing_names = array_column($fields, 'name');
            foreach ($config['facets_config'] as $f) {
                // Basic validation
                if (empty($f['enabled'])) continue;
                
                // Resolve target name. In new system, we expect 'target'. 
                // For backward compatibility, we fallback to logic.
                $target = !empty($f['target']) ? $f['target'] : null;
                if (!$target) {
                    if ($f['type'] === 'taxonomy') {
                        if ($f['source'] === 'category') $target = 'category';
                        elseif ($f['source'] === 'post_tag') $target = 'tag';
                        else $target = 'tax_' . $f['source'];
                    } elseif ($f['source'] === '_sku') {
                        $target = 'sku';
                    } else {
                        $target = $f['source'];
                    }
                }

                if (in_array($target, $existing_names)) continue;

                // Resolve Type (Facets are usually string[] for taxonomies, but flexible for meta)
                $type = !empty($f['data_type']) ? $f['data_type'] : 'string';
                if ($f['type'] === 'taxonomy') $type = 'string[]';

                $fields[] = array(
                    'name' => $target,
                    'type' => $type,
                    'facet' => true,
                    'optional' => true
                );
                $existing_names[] = $target;
            }
        }

        // 2. Custom Fields (Pro)
        if (isset($config['custom_fields']) && is_array($config['custom_fields'])) {
            $existing_names = array_column($fields, 'name');
            foreach ($config['custom_fields'] as $pt => $cf_list) {
                if (!is_array($cf_list))
                    continue;
                foreach ($cf_list as $cf) {
                    if (empty($cf['name']) || empty($cf['type']))
                        continue;

                    // Deduplicate
                    if (in_array($cf['name'], $existing_names))
                        continue;

                    $fields[] = array(
                        'name' => $cf['name'],
                        'type' => $cf['type'],
                        'facet' => isset($cf['facet']) && $cf['facet'],
                        'optional' => true,
                    );
                    $existing_names[] = $cf['name'];
                }
            }
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
