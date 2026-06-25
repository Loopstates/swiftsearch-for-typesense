<?php

namespace SwiftSearch\Engine;

use SwiftSearch\Core\Gatekeeper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DocumentBuilder
 *
 * Transforms a WP_Post into a Typesense document array.
 */
class DocumentBuilder
{

    /**
     * Build document from post ID.
     *
     * @param int $post_id Post ID.
     * @return array|false Document array or false if ignored.
     */
    public function build($post_id)
    {
        $post = get_post($post_id);

        if (!$post || 'publish' !== $post->post_status) {
            return false;
        }

        // Allow third-party exclusion of post
        if (!apply_filters('swift_search_should_index_post', true, $post_id, $post)) {
            return false;
        }

        // 1. Strict Whitelist Check
        $settings = get_option('swift_search_settings', array());
        $allowed_types = isset($settings['indexed_post_types']) ? (array) $settings['indexed_post_types'] : array('product');

        if (!in_array($post->post_type, $allowed_types)) {
            return false;
        }

        // Prepare document
        $document = array(
            'id' => (string) $post->ID,
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => wp_strip_all_tags(strip_shortcodes($post->post_content)),
            'post_excerpt' => wp_strip_all_tags(get_the_excerpt($post)),
            'post_type' => $post->post_type,
            'permalink' => get_permalink($post),
            'published_at' => get_post_timestamp($post),
            'author_name' => get_the_author_meta('display_name', $post->post_author),
        );

        // Thumbnail
        if (has_post_thumbnail($post)) {
            $document['thumbnail_url'] = get_the_post_thumbnail_url($post, 'medium');
        }

        // WooCommerce Price (Core Field)
        if (function_exists('wc_get_product') && 'product' === $post->post_type) {
            $product = wc_get_product($post);
            if ($product) {
                $price = $product->get_price();
                if (is_numeric($price)) {
                    $document['price'] = (float) $price;
                }
            }
        }

        // 2. Taxonomies & Universal Facets
        // This unified block replaces the old hardcoded taxonomy and WooCommerce blocks.
        if (isset($settings['facets_config']) && is_array($settings['facets_config']) && Gatekeeper::can_use_features()) {
            foreach ($settings['facets_config'] as $f) {
                if (empty($f['enabled'])) continue;

                $source = !empty($f['source']) ? $f['source'] : '';
                $type = !empty($f['type']) ? $f['type'] : 'taxonomy'; // taxonomy or meta
                
                // Resolve Target Name
                $target = !empty($f['target']) ? $f['target'] : null;
                if (!$target) {
                    if ($type === 'taxonomy') {
                        if ($source === 'category') $target = 'category';
                        elseif ($source === 'post_tag') $target = 'tag';
                        else $target = 'tax_' . $source;
                    } elseif ($source === '_sku') {
                        $target = 'sku';
                    } elseif ($source === '_price') {
                        $target = 'price';
                    } elseif ($source === '_stock_status') {
                        $target = 'in_stock';
                    } else {
                        $target = $source;
                    }
                }

                if (isset($document[$target])) continue; // Skip if already set by core

                if ($type === 'taxonomy') {
                    $terms = get_the_terms($post_id, $source);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $term_names = wp_list_pluck($terms, 'name');
                        $document[$target] = array_values(array_unique($term_names));
                    }
                } else {
                    // Meta / Custom Field
                    $meta_val = get_post_meta($post_id, $source, true);
                    
                    // Handle WooCommerce Special Methods for better data accuracy
                    if (function_exists('wc_get_product') && ('_sku' === $source || '_price' === $source || '_stock_status' === $source)) {
                        $product = wc_get_product($post_id);
                        if ($product) {
                            if ($source === '_sku') $meta_val = $product->get_sku();
                            elseif ($source === '_price') $meta_val = $product->get_price();
                            elseif ($source === '_stock_status') $meta_val = $product->is_in_stock();
                        }
                    }

                    // Cast Types based on config
                    $data_type = !empty($f['data_type']) ? $f['data_type'] : 'string';
                    
                    if ($data_type === 'int32' || $data_type === 'int64') {
                        $document[$target] = (int) $meta_val;
                    } elseif ($data_type === 'float') {
                        $document[$target] = (float) $meta_val;
                    } elseif ($data_type === 'bool') {
                        $document[$target] = filter_var($meta_val, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($data_type === 'string[]') {
                        if (is_array($meta_val)) {
                            $document[$target] = array_values($meta_val);
                        } else {
                            $document[$target] = array_map('trim', explode(',', (string) $meta_val));
                        }
                    } else {
                        $document[$target] = (string) $meta_val;
                    }
                }
            }
        }

        // 3. Additional Custom Fields (Legacy/Pro Non-Facet fields)
        if (isset($settings['custom_fields']) && is_array($settings['custom_fields']) && Gatekeeper::can_use_features()) {
            $pt = $post->post_type;
            if (isset($settings['custom_fields'][$pt])) {
                foreach ($settings['custom_fields'][$pt] as $field) {
                    if (empty($field['key']) || empty($field['name']))
                        continue;

                    if (isset($document[$field['name']])) continue; // Already handled by facets

                    $meta_val = get_post_meta($post_id, $field['key'], true);

                    // Cast Types
                    if ($field['type'] === 'int32') {
                        $document[$field['name']] = (int) $meta_val;
                    } elseif ($field['type'] === 'float') {
                        $document[$field['name']] = (float) $meta_val;
                    } elseif ($field['type'] === 'bool') {
                        $document[$field['name']] = (bool) $meta_val;
                    } elseif ($field['type'] === 'string[]') {
                        if (is_array($meta_val)) {
                            $document[$field['name']] = array_values($meta_val);
                        } else {
                            $document[$field['name']] = array_map('trim', explode(',', (string) $meta_val));
                        }
                    } else {
                        $document[$field['name']] = (string) $meta_val;
                    }
                }
            }
        }

        return apply_filters('swift_search_post_document', $document, $post_id, $post);
    }
}
