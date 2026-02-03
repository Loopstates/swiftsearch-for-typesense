<?php

namespace SwiftSearch\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 * 
 * Simple PSR-4 Autoloader for the plugin.
 */
class Autoloader
{

    /**
     * Register the autoloader.
     */
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload callback.
     *
     * @param string $class Class name.
     */
    public static function autoload($class)
    {
        // Project-specific namespace prefix
        $prefix = 'SwiftSearch\\';

        // Base directory for the namespace prefix
        $base_dir = SWIFT_SEARCH_PATH . 'src/';

        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }

        // Get the relative class name
        $relative_class = substr($class, $len);

        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}
