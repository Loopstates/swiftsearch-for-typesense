<?php

namespace SwiftSearch\Client;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Client
 *
 * Lightweight Typesense Client using wp_remote_request.
 * Avoids Guzzle dependency issues.
 */
class Client
{

    protected $host;
    protected $port;
    protected $protocol;
    protected $api_key;
    protected $search_key;

    /**
     * Constructor.
     *
     * @param array $config Configuration array.
     */
    public function __construct($config = null)
    {
        if (!$config) {
            $config = get_option('swift_search_settings', array());
        }

        $this->host = $config['host'] ?? '';
        $this->port = $config['port'] ?? '443';
        $this->protocol = $config['protocol'] ?? 'https';
        $this->api_key = $config['api_key'] ?? '';
        $this->search_key = $config['search_key'] ?? '';
    }

    /**
     * Get Base URL.
     *
     * @return string
     */
    protected function get_base_url()
    {
        return "{$this->protocol}://{$this->host}:{$this->port}";
    }

    /**
     * Make a request to Typesense.
     *
     * @param string $endpoint Endpoint path.
     * @param string $method   HTTP Method.
     * @param array  $body     Request body.
     * @return array|bool Response body or false on failure.
     */
    public function request($endpoint, $method = 'GET', $body = array())
    {
        $url = $this->get_base_url() . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'X-TYPESENSE-API-KEY' => $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 10,
        );

        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            // Log error
            error_log('SwiftSearch Typesense Error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Test connection.
     *
     * @return bool
     */
    public function test_connection()
    {
        // Health check endpoint
        $health = $this->request('/health', 'GET');
        return ($health && isset($health['ok']) && $health['ok']);
    }

    /**
     * Get stats (Collection info).
     * 
     * @return array
     */
    public function get_stats()
    {
        // Just counting documents in all collections for a basic stat
        $collections = $this->request('/collections', 'GET');
        if (!is_array($collections)) {
            return array('num_documents' => 0);
        }

        $count = 0;
        foreach ($collections as $col) {
            $count += $col['num_documents'] ?? 0;
        }

        return array('num_documents' => $count);
    }

    /**
     * Upsert an override rule.
     * 
     * @param string $id Rule ID.
     * @param array $rule Rule definition.
     * @return array|bool
     */
    public function upsert_override($id, $rule)
    {
        return $this->request('/collections/posts/overrides/' . $id, 'PUT', $rule);
    }
}
