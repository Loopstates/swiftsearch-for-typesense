<?php
namespace SwiftSearch\Engine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BackgroundIndexer
 *
 * Handles asynchronous indexing using Action Scheduler (preferred) or WP-Cron.
 * Ensures heavy tasks like full re-indexing don't time out the browser.
 */
class BackgroundIndexer
{
    const BATCH_SIZE = 50;
    const ACTION_HOOK = 'swift_search_process_batch';
    const STATUS_OPTION = 'swift_search_index_status';

    public function __construct()
    {
        add_action(self::ACTION_HOOK, array($this, 'process_batch'));
    }

    /**
     * Start a full re-index in the background.
     */
    public function start_full_reindex()
    {
        // Reset status
        update_option(self::STATUS_OPTION, array(
            'status' => 'running',
            'processed' => 0,
            'total' => $this->get_total_indexable_posts(),
            'start_time' => time(),
        ));

        // Schedule first batch
        $this->schedule_batch(0);
    }

    /**
     * Process a single batch of posts.
     *
     * @param int $offset
     */
    public function process_batch($offset)
    {
        $indexer = new Indexer(); // Assuming Indexer handles single post logic

        $args = array(
            'post_type' => 'any', // Or configured types
            'post_status' => 'publish',
            'posts_per_page' => self::BATCH_SIZE,
            'offset' => $offset,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        );

        $query = new \WP_Query($args);
        $posts = $query->posts;

        if (empty($posts)) {
            $this->complete_process();
            return;
        }

        foreach ($posts as $post_id) {
            // Index the post securely
            // We assume Indexer::update_post handles checking valid types, etc.
            $indexer->update_post($post_id, get_post($post_id));
        }

        // Update progress
        $status = get_option(self::STATUS_OPTION, array());
        $status['processed'] = $offset + count($posts);
        update_option(self::STATUS_OPTION, $status);

        // Schedule next batch
        $this->schedule_batch($offset + self::BATCH_SIZE);
    }

    private function schedule_batch($offset)
    {
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time(), self::ACTION_HOOK, array('offset' => $offset), 'swift-search');
        } else {
            wp_schedule_single_event(time(), self::ACTION_HOOK, array($offset));
        }
    }

    private function complete_process()
    {
        $status = get_option(self::STATUS_OPTION, array());
        $status['status'] = 'complete';
        $status['completed_time'] = time();
        update_option(self::STATUS_OPTION, $status);
    }

    private function get_total_indexable_posts()
    {
        $count_posts = wp_count_posts('post');
        $count_pages = wp_count_posts('page');
        $total = $count_posts->publish + $count_pages->publish;

        if (class_exists('WooCommerce')) {
            $count_products = wp_count_posts('product');
            $total += $count_products->publish;
        }

        return $total;
    }
}
