<?php
namespace SwiftSearch\Engine;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class UserIndexer
 *
 * Handles building documents for WP_User objects (Authors).
 */
class UserIndexer
{
    private $config;

    public function __construct($config_loader = null)
    {
        if ($config_loader) {
            $this->config = $config_loader->get_config();
        } else {
            $this->config = get_option('swift_search_settings', array());
        }
    }

    /**
     * Build a user document.
     *
     * @param int|object $user User ID or object.
     * @return array|false Document or false if invalid.
     */
    public function build($user)
    {
        if (is_numeric($user)) {
            $user = get_userdata($user);
        }

        if (!$user) {
            return false;
        }

        // Strict Check: Feature Flag
        $index_users = isset($this->config['indexed_users']) ? (bool) $this->config['indexed_users'] : false;
        if (!$index_users) {
            return false;
        }

        // Heuristic: Only index users who can create content (Authors, Editors, Admins)
        // This prevents indexing thousands of Subscribers/Customers
        // Vendors (Dokan/WCFM) usually have 'edit_products' or similar.
        if (!$user->has_cap('edit_posts') && !$user->has_cap('edit_products')) {
            return false;
        }

        $document = array(
            'id' => (string) $user->ID,
            'user_id' => (int) $user->ID,
            'display_name' => $user->display_name,
            'user_login' => $user->user_login,
            'url' => get_author_posts_url($user->ID),
            'avatar_url' => get_avatar_url($user->ID),
        );

        return $document;
    }
}
