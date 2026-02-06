<?php

namespace SwiftSearch\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SettingsIntegrity
 *
 * Prevents database tampering by signing option values.
 */
class SettingsIntegrity
{
    const SALT_OPTION = 'swift_search_integrity_salt';

    /**
     * Get or Generate a persistent salt for this site.
     * We don't rely solely on wp_salt() because if salts change (security rotation),
     * we don't want to invalidate all settings immediately unless intended.
     * But actually, invalidating on salt rotation is a feature.
     * Let's stick to wp_salt() for simplicity and best practice.
     */
    private static function get_salt()
    {
        return wp_salt('auth');
    }

    /**
     * Calculate HMAC signature for a value.
     *
     * @param mixed $value
     * @return string
     */
    private static function calculate_hash($value)
    {
        $serialized = maybe_serialize($value);
        return hash_hmac('sha256', $serialized, self::get_salt());
    }

    /**
     * Sign an option value and store the signature.
     *
     * @param string $option_name
     * @param mixed $new_value
     */
    public static function sign_option($option_name, $arg2, $arg3 = null)
    {
        // specific options we care about
        if ($option_name !== 'swift_search_settings') {
            return;
        }

        // Handle 'updated_option' hook: ($option, $old_value, $new_value)
        if ($arg3 !== null) {
            $new_value = $arg3;
        } else {
            // Handle 'added_option' hook: ($option, $value)
            $new_value = $arg2;
        }

        $hash = self::calculate_hash($new_value);
        update_option($option_name . '_checksum', $hash, false); // false = autoload? maybe no need
    }

    /**
     * Verify the integrity of an option.
     *
     * @param string $option_name
     * @return bool True if valid, False if tampered.
     */
    public static function verify_option($option_name)
    {
        // Only verify if we have a checksum
        $stored_hash = get_option($option_name . '_checksum');
        if (false === $stored_hash) {
            // No checksum exists yet.
            // If settings exist but no checksum, it might be legacy or fresh install.
            // We should auto-sign it now to upgrade security.
            $current_value = get_option($option_name);
            if ($current_value !== false) {
                self::sign_option($option_name, $current_value);
            }
            return true;
        }

        $current_value = get_option($option_name);
        $calculated_hash = self::calculate_hash($current_value);

        return hash_equals($stored_hash, $calculated_hash);
    }
}
