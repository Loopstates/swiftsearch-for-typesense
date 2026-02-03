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
        $taxonomies = get_object_taxonomies($post);
        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post, $taxonomy);
            if (!empty($terms) && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');
                if ('category' === $taxonomy) {
                    $document['category'] = $term_names;
                } elseif ('post_tag' === $taxonomy) {
                    $document['tag'] = $term_names;
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

        return $document;
    }
}
