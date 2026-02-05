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

        // Hook: Terms
        add_action('created_term', array($this, 'handle_term_save_hook'), 10, 3);
        add_action('edited_term', array($this, 'handle_term_save_hook'), 10, 3);
        add_action('delete_term', array($this, 'handle_term_delete_hook'), 10, 3);

        // Hook: Users
        add_action('user_register', array($this, 'handle_user_save_hook'));
        add_action('profile_update', array($this, 'handle_user_save_hook'));
        add_action('delete_user', array($this, 'handle_user_delete_hook'));

        // Register the actual background process
        add_action('swift_search_async_index_post', array($this, 'index_post'));
        add_action('swift_search_async_delete_post', array($this, 'delete_post_from_index'));

        add_action('swift_search_async_index_term', array($this, 'index_term'));
        add_action('swift_search_async_delete_term', array($this, 'delete_term_from_index'));

        add_action('swift_search_async_index_user', array($this, 'index_user'));
        add_action('swift_search_async_batch_process', array($this, 'process_bulk_batch'), 10, 2);
    }

    /**
     * Start Bulk Indexing.
     * Calculates totals and triggers first batch.
     */
    /**
     * Start Bulk Indexing.
     * Calculates totals and triggers first batch.
     */
    public function start_bulk_index()
    {
        $config = $this->config_loader->get_config();

        // 1. Calculate Totals
        // For MVP we only count Posts. In future we can sequence Terms/Users.
        $post_types = isset($config['indexed_post_types']) ? $config['indexed_post_types'] : array('post', 'page', 'product');

        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query($args);
        $total = $query->found_posts;

        // 2. Init Status
        $status = array(
            'active' => true,
            'total' => $total,
            'processed' => 0,
            'last_sync_start' => time(),
            'message' => 'Initializing...'
        );
        update_option('swift_search_index_status', $status);
        delete_option('swift_search_sync_errors'); // Clear old errors on new run

        // 3. Schedule First Batch - ASYNC
        $bg_process = new BackgroundProcess();
        $bg_process->dispatch(array(
            'type' => 'batch_process',
            'offset' => 0,
            'limit' => 50
        ));

        return $total;
    }

    /**
     * Recursive Worker: Process Batch.
     */
    public function process_bulk_batch($offset, $limit = 50)
    {
        $config = $this->config_loader->get_config();
        $post_types = isset($config['indexed_post_types']) ? $config['indexed_post_types'] : array('post', 'page', 'product');

        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'ASC',
        );

        $query = new \WP_Query($args);
        $ids = $query->posts;

        if (empty($ids)) {
            // DONE
            update_option('swift_search_index_status', array(
                'active' => false,
                'total' => $query->found_posts,
                'processed' => $offset, // Adjusted
                'message' => 'Complete!',
                'last_updated' => time(),
                'last_sync_completed_at' => time()
            ));
            return;
        }

        // PROCESS BATCH
        $documents = array();
        $errors = get_option('swift_search_sync_errors', array());

        foreach ($ids as $id) {
            try {
                $doc = $this->index_post($id, true); // Pass true to return doc instead of sending
                if ($doc) {
                    $documents[] = $doc;
                } else {
                    // Potentially a draft or filtered out item, strictly not an 'error' but skipped.
                    // If we want to verify real failures, we'd need index_post to throw or return error info.
                }
            } catch (\Exception $e) {
                // Log failure
                $errors[] = array('id' => $id, 'error' => $e->getMessage());
            }
        }

        // Save errors if any new ones
        if (!empty($errors)) {
            // Compressed Grouping Logic
            $grouped_errors = get_option('swift_search_sync_errors', array());

            foreach ($errors as $err) {
                $msg = $err['error'];
                $id = $err['id'];

                // If this error type doesn't exist, init it
                if (!isset($grouped_errors[$msg])) {
                    $grouped_errors[$msg] = array();
                }

                // Append ID if not already there (avoid dupes)
                if (!in_array($id, $grouped_errors[$msg])) {
                    $grouped_errors[$msg][] = $id;
                }
            }

            update_option('swift_search_sync_errors', $grouped_errors);
        }

        // BULK IMPORT
        if (!empty($documents)) {
            $result = $this->client->import('posts', $documents);
            // Check for Bulk API level errors?
            // Client::import returns array('success' => true, 'raw_response' => ...) or false
            if ($result === false) {
                // The entire batch failed at API level
                $batch_error = array('batch_offset' => $offset, 'error' => $this->client->get_last_error());
                $errors[] = $batch_error;
                update_option('swift_search_sync_errors', $errors);
            }
        }

        // UPDATE STATUS
        $new_processed = $offset + count($ids);
        $status = get_option('swift_search_index_status', array());
        $status['processed'] = $new_processed;
        $status['last_updated'] = time();
        $status['message'] = "Indexed {$new_processed} / {$status['total']}";
        update_option('swift_search_index_status', $status);

        // SCHEDULE NEXT - ASYNC CHAIN
        if ($new_processed < $status['total']) {
            $bg_process = new BackgroundProcess();
            $bg_process->dispatch(array(
                'type' => 'batch_process',
                'offset' => $new_processed,
                'limit' => $limit
            ));
        } else {
            // DONE
            update_option('swift_search_index_status', array(
                'active' => false,
                'total' => $status['total'],
                'processed' => $status['total'],
                'message' => 'Complete!',
                'last_updated' => time(),
                'last_sync_completed_at' => time()
            ));
        }
    }

    // ... existing hooks ...

    /**
     * Hook: Schedule Indexing on Save.
     *
     * @param int $post_id
     */
    public function handle_save_hook($post_id, $post = null, $update = null)
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

    // --- Terms ---

    public function handle_term_save_hook($term_id, $tt_id = null, $taxonomy = null)
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('swift_search_async_index_term', array('term_id' => $term_id));
        } else {
            wp_schedule_single_event(time(), 'swift_search_async_index_term', array($term_id));
        }
    }

    public function handle_term_delete_hook($term_id, $tt_id = null, $taxonomy = null)
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('swift_search_async_delete_term', array('term_id' => $term_id));
        } else {
            wp_schedule_single_event(time(), 'swift_search_async_delete_term', array($term_id));
        }
    }

    public function index_term($term_id)
    {
        $this->ensure_schema_safety();
        $term = get_term($term_id);
        if (!$term)
            return;

        $indexer = new TermIndexer($this->config_loader);
        $document = $indexer->build($term);

        if ($document) {
            $this->client->request('/collections/terms/documents?action=upsert', 'POST', $document);
        }
    }

    public function delete_term_from_index($term_id)
    {
        $this->client->request('/collections/terms/documents/' . $term_id, 'DELETE');
    }

    // --- Users ---

    public function handle_user_save_hook($user_id, $old_user_data = null)
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('swift_search_async_index_user', array('user_id' => $user_id));
        } else {
            wp_schedule_single_event(time(), 'swift_search_async_index_user', array($user_id));
        }
    }

    public function handle_user_delete_hook($user_id)
    {
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('swift_search_async_delete_user', array('user_id' => $user_id));
        } else {
            wp_schedule_single_event(time(), 'swift_search_async_delete_user', array($user_id));
        }
    }

    public function index_user($user_id)
    {
        $this->ensure_schema_safety();
        $user = get_userdata($user_id);
        if (!$user)
            return;

        $indexer = new UserIndexer($this->config_loader);
        $document = $indexer->build($user);

        // If returns false (e.g. subscriber), we should probably delete it if it existed?
        // Or upsert only if valid. If user role changed Subscriber -> Author, upsert.
        // If Author -> Subscriber, build returns false. Ideally we should Delete.
        if ($document) {
            $this->client->request('/collections/users/documents?action=upsert', 'POST', $document);
        } else {
            // Cleanup: If they were downgraded to subscriber, remove them from index
            $this->delete_user_from_index($user_id);
        }
    }

    public function delete_user_from_index($user_id)
    {
        $this->client->request('/collections/users/documents/' . $user_id, 'DELETE');
    }

    /**
     * WORKER: Index a single post.
     *
     * @param int $post_id
     */
    /**
     * WORKER: Index a single post.
     *
     * @param int $post_id
     * @param bool $return_doc If true, returns document array instead of sending to API.
     * @return array|void
     */
    public function index_post($post_id, $return_doc = false)
    {
        $this->ensure_schema_safety();

        $post = get_post($post_id);
        if (!$post)
            return;

        // 2. Build and Index
        $this->builder = new DocumentBuilder($this->config_loader);

        $document = $this->builder->build($post);
        if (!$document) {
            // If Post -> Draft or Post Type Disabled, remove from index
            // ONLY if strictly processing single item. If batch, we just skip adding it.
            if (!$return_doc) {
                $this->delete_post_from_index($post_id);
            }
            return;
        }

        if ($return_doc) {
            return $document;
        }

        $this->client->request('/collections/posts/documents?action=upsert', 'POST', $document);
    }

    private function ensure_schema_safety()
    {
        // 1. Schema Safety Check
        $config = $this->config_loader->get_config();
        $current_hash = Schema::get_hash($config);
        $stored_hash = get_option('swift_search_schema_hash');

        if ($stored_hash && $current_hash !== $stored_hash) {
            update_option('swift_search_schema_mismatch', true);
            // return; // Throw Exception? Or just let it fail/warn? 
            // For now we continue but flag it. 
        }
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
        $terms_schema = Schema::get_terms_schema($config);
        $users_schema = Schema::get_users_schema($config);

        // Calculate and store hash for safety
        $hash = Schema::get_hash($config);
        update_option('swift_search_schema_hash', $hash);
        delete_option('swift_search_schema_mismatch'); // Clear any validation error

        // Create main collection
        $this->client->request('/collections', 'POST', $schema);

        // Create auxiliary collections
        $this->client->request('/collections', 'POST', $terms_schema);
        $this->client->request('/collections', 'POST', $users_schema);
    }

    /**
     * Delete Collection (Reset).
     */
    public function delete_collection()
    {
        $this->client->request('/collections/posts', 'DELETE');
        $this->client->request('/collections/terms', 'DELETE');
        $this->client->request('/collections/users', 'DELETE');
        return true;
    }
}
