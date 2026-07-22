<?php
namespace SwiftSearch\CLI;

use SwiftSearch\Engine\Indexer;
use SwiftSearch\Engine\ConfigLoader;
use SwiftSearch\Client\Client;
use WP_CLI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CLI Commands for SwiftSearch for Typesense.
 */
class IndexCommand
{
    /**
     * Run full indexing of all selected post types.
     *
     * ## OPTIONS
     *
     * [--batch-size=<size>]
     * : Number of items to index in each batch. Default is 250.
     *
     * ## EXAMPLES
     *
     *     wp swift-search index --batch-size=500
     *
     * @alias index
     */
    public function index($args, $assoc_args)
    {
        $batch_size = isset($assoc_args['batch-size']) ? intval($assoc_args['batch-size']) : 250;
        if ($batch_size <= 0) {
            $batch_size = 250;
        }

        $config_loader = new ConfigLoader();
        $config = $config_loader->get_config();

        $post_types = isset($config['indexed_post_types']) ? (array) $config['indexed_post_types'] : array('product');

        WP_CLI::line('Querying post IDs to index...');
        WP_CLI::line('Selected Post Types: ' . implode(', ', $post_types));

        $query_args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'fields' => 'ids',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query($query_args);
        $post_ids = $query->posts;
        $total = count($post_ids);

        if ($total === 0) {
            WP_CLI::success('No posts found to index.');
            return;
        }

        WP_CLI::line(sprintf('Found %d posts to index. Starting bulk sync in batches of %d...', $total, $batch_size));

        // Create collection if it doesn't exist
        $indexer = new Indexer();
        WP_CLI::line('Ensuring collections exist in Typesense...');
        $indexer->create_collection();

        $progress = \WP_CLI\Utils\make_progress_bar('Indexing progress', $total);
        
        $offset = 0;
        $client = new Client();

        // Update option status to active so admin UI knows sync is running
        update_option('swift_search_index_status', array(
            'active' => true,
            'total' => $total,
            'processed' => 0,
            'last_sync_start' => time(),
            'message' => 'CLI Sync in progress...'
        ));

        while ($offset < $total) {
            $batch_ids = array_slice($post_ids, $offset, $batch_size);
            $documents = array();

            foreach ($batch_ids as $id) {
                try {
                    $doc = $indexer->index_post($id, true);
                    if ($doc) {
                        $documents[] = $doc;
                    }
                } catch (\Exception $e) {
                    WP_CLI::warning(sprintf('Failed to build document for ID %d: %s', $id, $e->getMessage()));
                }
                $progress->tick();
            }

            if (!empty($documents)) {
                $result = $client->import('posts', $documents);
                if ($result === false) {
                    WP_CLI::warning('Typesense API batch import error: ' . $client->get_last_error());
                }
            }

            $offset += count($batch_ids);
            
            // Keep admin status option updated
            update_option('swift_search_index_status', array(
                'active' => true,
                'total' => $total,
                'processed' => min($offset, $total),
                'last_sync_start' => time(),
                'message' => sprintf('CLI Indexed %d / %d', min($offset, $total), $total)
            ));
        }

        $progress->finish();

        // Update option status to complete
        update_option('swift_search_index_status', array(
            'active' => false,
            'total' => $total,
            'processed' => $total,
            'message' => 'Complete!',
            'last_updated' => time(),
            'last_sync_completed_at' => time()
        ));

        WP_CLI::success(sprintf('Successfully indexed %d documents into Typesense.', $total));
    }

    /**
     * Reset and recreate the Typesense collections.
     *
     * ## EXAMPLES
     *
     *     wp swift-search reset
     */
    public function reset($args, $assoc_args)
    {
        WP_CLI::confirm('Are you sure you want to delete and recreate all SwiftSearch collections in Typesense? This will delete the search index.');

        $indexer = new Indexer();
        WP_CLI::line('Deleting collections...');
        $indexer->delete_collection();
        
        WP_CLI::line('Creating collections...');
        $indexer->create_collection();
        WP_CLI::success('Reset completed successfully.');
    }

    /**
     * Check connection status and get document counts.
     *
     * ## EXAMPLES
     *
     *     wp swift-search status
     */
    public function status($args, $assoc_args)
    {
        $client = new Client();
        if ($client->test_connection()) {
            WP_CLI::success('Connected to Typesense server successfully.');
            
            $collections = $client->request('/collections', 'GET');
            if (is_array($collections)) {
                WP_CLI::line('Collections status:');
                foreach ($collections as $collection) {
                    $doc_count = isset($collection['num_documents']) ? (int) $collection['num_documents'] : 0;
                    WP_CLI::line(sprintf(' - %s: %d documents', $collection['name'], $doc_count));
                }
            } else {
                WP_CLI::line('No collections found.');
            }
        } else {
            WP_CLI::error('Failed to connect to Typesense server: ' . $client->get_last_error());
        }
    }
}
