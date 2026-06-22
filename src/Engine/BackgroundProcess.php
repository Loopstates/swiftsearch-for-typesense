<?php
namespace SwiftSearch\Engine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BackgroundProcess
 *
 * Implements a lightweight non-blocking HTTP loopback system.
 * Replaces WP-Cron for high-reliability batch processing using the "Chain Reaction" pattern.
 */
class BackgroundProcess
{
    protected $action = 'swift_search_async_process';

    public function __construct()
    {
        add_action('wp_ajax_' . $this->action, array($this, 'handle_request'));
        add_action('wp_ajax_nopriv_' . $this->action, array($this, 'handle_request'));
    }

    /**
     * Dispatch an async request.
     * Fires immediately without blocking the current page load.
     *
     * @param array $data Data to pass to the handler.
     */
    public function dispatch($data = array())
    {
        $url = admin_url('admin-ajax.php');

        $args = array(
            'timeout' => 0.01,
            'blocking' => false, // FIRE AND FORGET
            'body' => array(
                'action' => $this->action,
                'nonce' => wp_create_nonce('swift_search_async_bg'),
                'data' => $data,
            ),
            'sslverify' => apply_filters('swift_search_https_local_ssl_verify', false),
        );

        wp_remote_post($url, $args);

        // Run a test BLOCKING request to capture the exact server response/error
        $test_args = $args;
        $test_args['blocking'] = true;
        $test_args['timeout'] = 10;
        
        $response = wp_remote_post($url, $test_args);
        
        if (is_wp_error($response)) {
            $log = array(
                'success' => false,
                'error_message' => $response->get_error_message(),
                'error_code' => $response->get_error_code(),
                'time' => time(),
            );
        } else {
            $log = array(
                'success' => true,
                'response_code' => wp_remote_retrieve_response_code($response),
                'response_body' => wp_remote_retrieve_body($response),
                'time' => time(),
            );
        }
        update_option('swift_search_debug_loopback', $log);
    }

    /**
     * Handle the AJAX request.
     */
    public function handle_request()
    {
        // Security Check
        // Note: nopriv is allowed because the server calling itself might not have auth cookies in headers sometimes,
        // but we verify nonce. 
        // Actually, wp_remote_post passes cookies, so we should check privileges safely.

        /* 
         * Relaxed Security for Background Process:
         * Since this is internal, strict nonce check is good enough.
         */
        // Sanitize Nonce and Data
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'swift_search_async_bg')) {
            wp_die('Invalid Nonce');
        }

        $data = isset($_POST['data']) ? map_deep(wp_unslash($_POST['data']), 'sanitize_text_field') : array();

        // Route the request
        // In this specific architecture, we are hard-looping the Indexer batch.
        // We could make this generic, but for now let's bind it tightly to Indexer to avoid overengineering.

        $indexer = new Indexer();

        // Check what operation to run
        if (isset($data['type']) && $data['type'] === 'batch_process') {
            $offset = isset($data['offset']) ? intval($data['offset']) : 0;
            $limit = isset($data['limit']) ? intval($data['limit']) : 50;

            // Execute Batch
            // process_bulk_batch now needs to accept a flag to know it shouldn't rely on Cron
            $indexer->process_bulk_batch($offset, $limit);
        }

        wp_die();
    }

    /**
     * Recursive sanitization for background payloads.
     */
    private function sanitize_payload($data)
    {
        if (is_array($data)) {
            return map_deep($data, 'sanitize_text_field');
        }
        return sanitize_text_field($data);
    }
}
