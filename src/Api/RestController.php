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
            return new \WP_REST_Response(array('success' => false, 'message' => 'Could not connect to Typesense server. Check your credentials.'), 400);
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

        $args = array(
            'post_type' => array('post', 'page', 'product'),
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
}
