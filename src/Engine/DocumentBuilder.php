<?php

namespace SwiftSearch\Engine;

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

        // 1. Strict Whitelist Check
        $settings = get_option('swift_search_settings');
        $allowed_types = isset($settings['indexed_post_types']) ? $settings['indexed_post_types'] : array('post', 'page', 'product');

        if (!in_array($post->post_type, $allowed_types)) {
            // Debugging Sync: Log why it skipped
            error_log("SwiftSearch Debug: Skipped Post {$post_id} (Type: {$post->post_type}). Allowed: " . json_encode($allowed_types));
            return false;
        }

        // Prepare document
        $document = array(
            'id' => (string) $post->ID,
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => strip_tags(strip_shortcodes($post->post_content)),
            'post_excerpt' => get_the_excerpt($post),
            'post_type' => $post->post_type,
            'permalink' => get_permalink($post),
            'published_at' => get_post_timestamp($post),
            'author_name' => get_the_author_meta('display_name', $post->post_author),
        );

        // Thumbnail
        if (has_post_thumbnail($post)) {
            $document['thumbnail_url'] = get_the_post_thumbnail_url($post, 'medium');
        }

        // Taxonomies
        $allowed_taxonomies = isset($settings['indexed_taxonomies']) ? $settings['indexed_taxonomies'] : array('category', 'post_tag');

        foreach ($allowed_taxonomies as $taxonomy) {
            $terms = get_the_terms($post, $taxonomy);
            if (!empty($terms) && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');

                if ('category' === $taxonomy) {
                    $document['category'] = $term_names;
                } elseif ('post_tag' === $taxonomy) {
                    $document['tag'] = $term_names;
                } else {
                    // Dynamic Taxonomy
                    $document['tax_' . $taxonomy] = $term_names;
                }
            }
        }

        // WooCommerce
        if ('product' === $post->post_type && function_exists('wc_get_product')) {
            $product = wc_get_product($post_id);
            if ($product) {
                $document['price'] = (float) $product->get_price();
                $document['sku'] = $product->get_sku();
                $document['in_stock'] = $product->is_in_stock();
            }
        }

        // Custom Fields Retrieval
        if (isset($settings['custom_fields']) && is_array($settings['custom_fields'])) {
            $pt = $post->post_type;
            if (isset($settings['custom_fields'][$pt])) {
                foreach ($settings['custom_fields'][$pt] as $field) {
                    if (empty($field['key']) || empty($field['name']))
                        continue;

                    $meta_val = get_post_meta($post_id, $field['key'], true);

                    // Cast Types
                    if ($field['type'] === 'int32') {
                        $document[$field['name']] = (int) $meta_val;
                    } elseif ($field['type'] === 'float') {
                        $document[$field['name']] = (float) $meta_val;
                    } elseif ($field['type'] === 'bool') {
                        $document[$field['name']] = (bool) $meta_val;
                    } elseif ($field['type'] === 'string[]') {
                        // Handle array or comma-separated string
                        if (is_array($meta_val)) {
                            $document[$field['name']] = array_values($meta_val);
                        } else {
                            // Assume comma separated if string
                            $document[$field['name']] = array_map('trim', explode(',', (string) $meta_val));
                        }
                    } else {
                        // Default String
                        $document[$field['name']] = (string) $meta_val;
                    }
                }
            }
        }

        return $document;
    }
}
