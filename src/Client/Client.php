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
    protected $last_error = '';

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
        // Omit port if standard for better proxy compatibility
        $port = (int) $this->port;
        if (($this->protocol === 'https' && $port === 443) || ($this->protocol === 'http' && $port === 80)) {
            return "{$this->protocol}://{$this->host}";
        }
        return "{$this->protocol}://{$this->host}:{$this->port}";
    }

    /**
     * Get Last Error.
     * 
     * @return string
     */
    public function get_last_error()
    {
        return $this->last_error;
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
        $this->last_error = ''; // Reset error
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
            $this->last_error = $response->get_error_message();
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            $body = wp_remote_retrieve_body($response);
            $msg = "HTTP $code";
            $json = json_decode($body, true);
            if ($json && isset($json['message'])) {
                $msg .= ': ' . $json['message'];
            }
            $this->last_error = $msg;
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

        if ($health === false) {
            return false;
        }

        if (isset($health['ok']) && $health['ok']) {
            return true;
        }

        $this->last_error = 'Health check failed. Response: ' . json_encode($health);
        return false;
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

    /**
     * Upsert a global synonym set (Typesense v0.30+).
     * 
     * @param string $id Synonym ID.
     * @param array $schema Synonym definition.
     * @return array|bool
     */
    public function upsert_synonym($id, $params, $collections = array('posts'))
    {
        /**
         * v0.30.0+ uses /synonym_sets (underscore) and REQUIRES an "items" array.
         * EACH item inside that array also REQUIRES its own internal "id".
         * To "link" these global rules to collections, we use the "collections" array.
         */
        $item = $params;
        if (!isset($item['id'])) {
            $item['id'] = $id . '-item';
        }

        $payload = array(
            'items' => array($item),
            'collections' => $collections
        );
        return $this->request("/synonym_sets/{$id}", 'PUT', $payload);
    }

    /**
     * Import documents (Bulk).
     * Uses the /import endpoint with JSONL.
     * 
     * @param string $collection Collection name.
     * @param array  $documents  Array of document arrays.
     * @param string $action     'create', 'upsert', 'update'.
     * @return array|bool
     */
    public function import($collection, $documents, $action = 'upsert')
    {
        if (empty($documents)) {
            return array('success' => true, 'num_items' => 0);
        }

        // Convert to JSONL
        $jsonl = '';
        foreach ($documents as $doc) {
            $jsonl .= json_encode($doc) . "\n";
        }

        $endpoint = "/collections/{$collection}/documents/import?action={$action}";

        $this->last_error = '';
        $url = $this->get_base_url() . $endpoint;

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'X-TYPESENSE-API-KEY' => $this->api_key,
                'Content-Type' => 'text/plain', // Important for JSONL? Or app/json works? Typesense docs say text/plain usually best for raw body
            ),
            'body' => $jsonl,
            'timeout' => 30, // Higher timeout for bulk
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            $this->last_error = "HTTP $code: " . $body;
            return false;
        }

        // Response is also JSONL (one result per line)
        // We can parse it to check for individual errors if we want strict mode
        // For now, assume success if 200 OK.
        return array('success' => true, 'raw_response' => $body);
    }

    /**
     * Patch a collection (Update schema/config).
     * 
     * @param string $name Collection name.
     * @param array $data Delta to apply.
     * @return array|bool
     */
    public function patch_collection($name, $data)
    {
        return $this->request("/collections/{$name}", 'PATCH', $data);
    }

    /**
     * Link a global synonym set to a collection (Typesense v0.30+).
     * 
     * @param string $collection Collection name.
     * @param string $id         Synonym Set ID.
     * @return array|bool
     */
    public function link_synonym_to_collection($collection, $id)
    {
        // NO LONGER RECOMMENDED for v0.30+ Global Rules; use patch_collection(['synonym_sets' => [...]]) instead.
        // Keeping for legacy but we will migrate RestController to patch_collection.
        $payload = array(
            'id' => $id,
            'synonym_set_id' => $id
        );
        return $this->request("/collections/{$collection}/synonyms/{$id}", 'PUT', $payload);
    }
}
