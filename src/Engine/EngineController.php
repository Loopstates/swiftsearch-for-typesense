<?php

namespace SwiftSearch\Engine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EngineController
 * 
 * Init the indexing engine.
 */
class EngineController
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Initialize the Indexer (hooks into save_post)
        new Indexer();

        // Initialize Background Processor (Queue listener)
        new BackgroundIndexer();
    }
}
