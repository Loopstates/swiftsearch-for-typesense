<?php

namespace SwiftSearch\Api;

use SwiftSearch\Client\Client;
use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RestController
 *
 * Handles REST API requests.
 */
class RestController extends WP_REST_Controller
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'swift-search/v1';
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/connect', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_connect'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route($this->namespace, '/disconnect', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_disconnect'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route($this->namespace, '/status', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route($this->namespace, '/sync/batch', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_batch_sync'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route($this->namespace, '/reset', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_reset'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route($this->namespace, '/settings', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_settings'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route($this->namespace, '/log', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_log'),
            'permission_callback' => '__return_true', // Public
        ));

        register_rest_route($this->namespace, '/analytics', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_analytics'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Pinning Endpoints
        register_rest_route($this->namespace, '/pinning/search', array(
            'methods' => WP_REST_Server::CREATABLE, // POST
            'callback' => array($this, 'handle_pinning_search'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route($this->namespace, '/pinning/items', array(
            'methods' => array(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
            'callback' => array($this, 'dispatch_pinning_items'), // wrapper to separate GET/POST
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    public function dispatch_pinning_items($request)
    {
        if ($request->get_method() === 'GET') {
            return $this->get_pinned_items();
        } else {
            return $this->handle_pinning_save($request);
        }
    }

    /**
     * Check permissions.
     *
     * @return bool
     */
    public function check_permission()
    {
        return current_user_can('manage_options');
    }

    /**
     * Handle connect request.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function handle_connect($request)
    {
        $params = $request->get_params();

        $host = sanitize_text_field($params['host'] ?? '');
        $port = sanitize_text_field($params['port'] ?? '443');
        $protocol = sanitize_text_field($params['protocol'] ?? 'https');
        $api_key = sanitize_text_field($params['api_key'] ?? '');
        $search_key = sanitize_text_field($params['search_key'] ?? '');

        if (empty($host) || empty($api_key)) {
            return new \WP_REST_Response(array('success' => false, 'message' => 'Missing credentials.'), 400);
        }

        $config = array(
            'host' => $host,
            'port' => $port,
            'protocol' => $protocol,
            'api_key' => $api_key,
            'search_key' => $search_key,
        );

        // Initialize Client with temporary config
        $client = new Client($config);

        // Test Connection
        $is_valid = $client->test_connection();

        if ($is_valid) {
            // Save to Options
            update_option('swift_search_settings', $config);

            // Init Collection
            $indexer = new \SwiftSearch\Engine\Indexer();
            try {
                $indexer->create_collection();
            } catch (\Exception $e) {
            } // Ignore if exists

            // Get Stats
            $stats = $client->get_stats();

            return new \WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'message' => 'Connected!',
                    'doc_count' => $stats['num_documents'] ?? 0
                )
            ), 200);
        } else {
            $error = $client->get_last_error();
            $msg = 'Connection failed. Please check your credentials.';
            if (!empty($error)) {
                $msg .= ' Details: ' . $error;
            }

            return new \WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => $msg
                )
            ), 200);
        }
    }

    /**
     * Get status.
     *
     * @return \WP_REST_Response
     */
    public function get_status()
    {
        $config = get_option('swift_search_settings', array());

        if (empty($config['api_key'])) {
            return new \WP_REST_Response(array('success' => true, 'data' => array('connected' => false)), 200);
        }

        $client = new Client($config);
        $is_valid = $client->test_connection();
        $stats = $is_valid ? $client->get_stats() : array();

        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'connected' => $is_valid,
                'doc_count' => $stats['num_documents'] ?? 0
            )
        ), 200);
    }

    /**
     * Handle Batch Sync.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_batch_sync($request)
    {
        $page = (int) $request->get_param('page');
        $page = $page > 0 ? $page : 1;
        $per_page = 20;

        $config = get_option('swift_search_settings', array());
        $post_types = isset($config['indexed_post_types']) ? $config['indexed_post_types'] : array('post', 'page', 'product');

        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        );

        $query = new \WP_Query($args);
        $ids = $query->posts;

        $indexer = new \SwiftSearch\Engine\Indexer();
        $processed = 0;

        foreach ($ids as $id) {
            $indexer->index_post($id);
            $processed++;
        }

        $total_pages = $query->max_num_pages;

        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'processed' => $processed,
                'page' => $page,
                'total_pages' => $total_pages,
                'complete' => $page >= $total_pages,
            ),
        ), 200);
    }

    /**
     * Handle Settings Update.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_settings($request)
    {
        $params = $request->get_params();

        // Handle Override Toggle
        if (isset($params['override_default'])) {
            $val = filter_var($params['override_default'], FILTER_VALIDATE_BOOLEAN);
            update_option('swift_search_override_default', $val);
        }

        // Handle Post Types
        if (isset($params['post_types'])) {
            $current_settings = get_option('swift_search_settings', array());
            $types = is_array($params['post_types']) ? array_map('sanitize_text_field', $params['post_types']) : array();
            $current_settings['indexed_post_types'] = $types;
            update_option('swift_search_settings', $current_settings);
        }

        // Handle Relevance Settings (Pro)
        if (isset($params['relevance_settings'])) {
            // Retrieve current settings to merge
            $current_settings = get_option('swift_search_settings', array());

            $new_relevance = $params['relevance_settings'];

            // 1. Update Weights
            if (isset($new_relevance['weights'])) {
                foreach ($new_relevance['weights'] as $key => $weight) {
                    $current_settings['weights'][sanitize_key($key)] = absint($weight);
                }
            }

            // 2. Update Synonyms
            if (isset($new_relevance['synonyms']) && is_array($new_relevance['synonyms'])) {
                $clean_synonyms = array();
                foreach ($new_relevance['synonyms'] as $group) {
                    if (!empty($group['root']) && !empty($group['synonyms']) && is_array($group['synonyms'])) {
                        $clean_synonyms[] = array(
                            'root' => sanitize_text_field($group['root']),
                            'synonyms' => array_map('sanitize_text_field', $group['synonyms'])
                        );
                    }
                }
                $current_settings['synonyms'] = $clean_synonyms;
            }

            update_option('swift_search_settings', $current_settings);
        }

        // Handle Experience Settings (Free Features)
        if (isset($params['experience_settings'])) {
            $current_settings = get_option('swift_search_settings', array());
            $new_exp = $params['experience_settings'];

            $experience = array(
                'typo_tolerance' => isset($new_exp['typo_tolerance']) ? filter_var($new_exp['typo_tolerance'], FILTER_VALIDATE_BOOLEAN) : true,
                'sort_enabled' => isset($new_exp['sort_enabled']) ? filter_var($new_exp['sort_enabled'], FILTER_VALIDATE_BOOLEAN) : false,
                'mobile_btn' => isset($new_exp['mobile_btn']) ? filter_var($new_exp['mobile_btn'], FILTER_VALIDATE_BOOLEAN) : false,
            );

            $current_settings['experience'] = $experience;
            update_option('swift_search_settings', $current_settings);
        }

        return new \WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Handle Index Reset.
     *
     * @return \WP_REST_Response
     */
    public function handle_reset()
    {
        $indexer = new \SwiftSearch\Engine\Indexer();
        $result = $indexer->delete_collection();

        try {
            $indexer->create_collection();
        } catch (\Exception $e) {
        }

        return new \WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Handle Search Log.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_log($request)
    {
        $params = $request->get_params();
        $query = sanitize_text_field($params['query'] ?? '');
        $hits = intval($params['hits'] ?? 0);

        if (empty($query)) {
            return new \WP_REST_Response(array('success' => false), 400);
        }

        global $wpdb;
        $table_name = \SwiftSearch\Core\DB::get_table_name();

        $wpdb->insert(
            $table_name,
            array(
                'query' => $query,
                'hits' => $hits,
            ),
            array('%s', '%d')
        );

        return new \WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Get Analytics Data.
     * 
     * @return \WP_REST_Response
     */
    public function get_analytics()
    {
        global $wpdb;
        $table_name = \SwiftSearch\Core\DB::get_table_name();

        // Top Searches
        $top_queries = $wpdb->get_results("
            SELECT query, COUNT(*) as count, AVG(hits) as avg_hits 
            FROM $table_name 
            WHERE hits > 0 
            GROUP BY query 
            ORDER BY count DESC 
            LIMIT 10
        ");

        // Zero Result Queries
        $no_results = $wpdb->get_results("
            SELECT query, COUNT(*) as count 
            FROM $table_name 
            WHERE hits = 0 
            GROUP BY query 
            ORDER BY count DESC 
            LIMIT 10
        ");

        return new \WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'top_queries' => $top_queries,
                'no_results' => $no_results
            )
        ), 200);
    }

    // --- Pinning Features (Pro) ---

    /**
     * Handle Product Search for Pinning (Autocomplete).
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_pinning_search($request)
    {
        $term = sanitize_text_field($request->get_param('term'));
        if (empty($term))
            return new \WP_REST_Response(array('success' => true, 'data' => array()), 200);

        $args = array(
            'post_type' => array('product', 'post', 'page'),
            'post_status' => 'publish',
            's' => $term,
            'posts_per_page' => 10,
        );

        $query = new \WP_Query($args);
        $results = array();

        foreach ($query->posts as $post) {
            $results[] = array(
                'id' => (string) $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type
            );
        }

        return new \WP_REST_Response(array('success' => true, 'data' => $results), 200);
    }

    /**
     * Handle Saving Pinned Items.
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_pinning_save($request)
    {
        $items = $request->get_param('items');
        if (!is_array($items))
            $items = array();

        // 1. Save to WP
        update_option('swift_search_pinned_items', $items);

        // 2. Sync to Typesense (Curation Rule)
        $config = get_option('swift_search_settings', array());
        if (!empty($config['api_key'])) {
            $client = new Client($config);

            // Construct Rule: Push these IDs to the top for ALL queries
            $rule = array(
                "rule" => array(
                    "query" => "*" // Apply to all queries
                ),
                "includes" => array()
            );

            foreach ($items as $index => $item) {
                $rule['includes'][] = array(
                    "id" => (string) $item['id'],
                    "position" => $index + 1
                );
            }

            // If empty, we might want to delete the rule, but upsert with empty includes is fine (it just does nothing)
            // Or better, if empty, we send a rule that does nothing? 
            // Typesense doesn't like empty includes. 
            if (empty($items)) {
                $client->request('/collections/posts/overrides/swift_search_global_pins', 'DELETE');
            } else {
                $client->upsert_override('swift_search_global_pins', $rule);
            }
        }

        return new \WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Get Pinned Items.
     * 
     * @return \WP_REST_Response
     */
    public function get_pinned_items()
    {
        $items = get_option('swift_search_pinned_items', array());
        return new \WP_REST_Response(array('success' => true, 'data' => $items), 200);
    }
}
