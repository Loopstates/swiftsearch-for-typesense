<?php
namespace SwiftSearch\Engine;

use SwiftSearch\Client\Client;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Indexer
 *
 * Handles indexing of content.
 * Supports Asynchronous Indexing and Schema Guardrails.
 */
class Indexer
{

    protected $client;
    protected $builder;
    protected $config_loader;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->config_loader = new ConfigLoader();
        // Builder instantiated on demand to get fresh config

        // Hook into WP lifecycle using Async handlers
        add_action('save_post', array($this, 'handle_save_hook'), 10, 3);
        add_action('delete_post', array($this, 'handle_delete_hook'));

        // Register the actual background process
        add_action('swift_search_async_index_post', array($this, 'index_post'));
        add_action('swift_search_async_delete_post', array($this, 'delete_post_from_index'));
    }

    /**
     * Hook: Schedule Indexing on Save.
     *
     * @param int $post_id
     */
    public function handle_save_hook($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('swift_search_async_index_post', array('post_id' => $post_id));
        } else {
            wp_schedule_single_event(time(), 'swift_search_async_index_post', array($post_id));
        }
    }

    /**
     * Hook: Schedule Deletion.
     *
     * @param int $post_id
     */
    public function handle_delete_hook($post_id)
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('swift_search_async_delete_post', array('post_id' => $post_id));
        } else {
            wp_schedule_single_event(time(), 'swift_search_async_delete_post', array($post_id));
        }
    }

    /**
     * WORKER: Index a single post.
     *
     * @param int $post_id
     */
    public function index_post($post_id)
    {
        // 1. Schema Safety Check
        $config = $this->config_loader->get_config();
        $current_hash = Schema::get_hash($config);
        $stored_hash = get_option('swift_search_schema_hash');

        if ($stored_hash && $current_hash !== $stored_hash) {
            // Mismatch detected (User changed plan or config changed)
            // Block update to prevent corruption.
            update_option('swift_search_schema_mismatch', true);
            return;
        }

        $post = get_post($post_id);
        if (!$post)
            return;

        // 2. Build and Index
        $this->builder = new DocumentBuilder($this->config_loader);

        $document = $this->builder->build($post);
        if (!$document)
            return;

        $this->client->request('/collections/posts/documents?action=upsert', 'POST', $document);
    }

    /**
     * Public alias for batch processing.
     */
    public function update_post($post_id, $post)
    {
        $this->index_post($post_id);
    }

    /**
     * WORKER: Delete a post from index.
     */
    public function delete_post_from_index($post_id)
    {
        $this->client->request('/collections/posts/documents/' . $post_id, 'DELETE');
    }

    /**
     * Create Collection (Init).
     */
    public function create_collection()
    {
        $config = $this->config_loader->get_config();
        $schema = Schema::get_schema($config);

        // Calculate and store hash for safety
        $hash = Schema::get_hash($config);
        update_option('swift_search_schema_hash', $hash);
        delete_option('swift_search_schema_mismatch'); // Clear any validation error

        return $this->client->request('/collections', 'POST', $schema);
    }

    /**
     * Delete Collection (Reset).
     */
    public function delete_collection()
    {
        return $this->client->request('/collections/posts', 'DELETE');
    }
}
