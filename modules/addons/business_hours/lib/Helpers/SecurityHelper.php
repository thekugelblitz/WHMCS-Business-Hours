<?php
/**
 * Security Helper
 *
 * @package    BusinessHours\Helpers
 */

namespace BusinessHours\Helpers;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class SecurityHelper
{
    /**
     * Sanitize a string input
     *
     * @param string $input
     * @return string
     */
    public static function sanitizeString($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize an integer input
     *
     * @param mixed $input
     * @param int $default
     * @return int
     */
    public static function sanitizeInt($input, $default = 0)
    {
        $val = filter_var($input, FILTER_VALIDATE_INT);
        return $val !== false ? $val : $default;
    }

    /**
     * Sanitize a time string (H:i or H:i:s)
     *
     * @param string $input
     * @return string|null
     */
    public static function sanitizeTime($input)
    {
        $input = trim($input);
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $input)) {
            // Normalize to H:i:s
            $parts = explode(':', $input);
            $h = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $m = $parts[1];
            $s = isset($parts[2]) ? $parts[2] : '00';
            return "{$h}:{$m}:{$s}";
        }
        return null;
    }

    /**
     * Sanitize a date string (Y-m-d)
     *
     * @param string $input
     * @return string|null
     */
    public static function sanitizeDate($input)
    {
        $input = trim($input);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            $parts = explode('-', $input);
            if (checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
                return $input;
            }
        }
        return null;
    }

    /**
     * Sanitize a hex color string
     *
     * @param string $input
     * @return string|null
     */
    public static function sanitizeColor($input)
    {
        $input = trim($input);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $input)) {
            return $input;
        }
        return null;
    }

    /**
     * Sanitize a slug
     *
     * @param string $input
     * @return string
     */
    public static function sanitizeSlug($input)
    {
        $slug = strtolower(trim($input));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Escape output for HTML
     *
     * @param string $output
     * @return string
     */
    public static function escape($output)
    {
        return htmlspecialchars((string) $output, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape output for use in a JavaScript string
     *
     * @param string $output
     * @return string
     */
    public static function escapeJs($output)
    {
        return addslashes((string) $output);
    }

    /**
     * Validate that a value is in an allowed list
     *
     * @param mixed $value
     * @param array $allowed
     * @param mixed $default
     * @return mixed
     */
    public static function validateEnum($value, array $allowed, $default = null)
    {
        return in_array($value, $allowed) ? $value : $default;
    }

    /**
     * Get POST parameter with sanitization
     *
     * @param string $key
     * @param mixed $default
     * @return string
     */
    public static function getPost($key, $default = '')
    {
        return isset($_POST[$key]) ? self::sanitizeString($_POST[$key]) : $default;
    }

    /**
     * Get GET parameter with sanitization
     *
     * @param string $key
     * @param mixed $default
     * @return string
     */
    public static function getQuery($key, $default = '')
    {
        return isset($_GET[$key]) ? self::sanitizeString($_GET[$key]) : $default;
    }

    /**
     * Verify CSRF token (WHMCS admin)
     *
     * @return bool
     */
    public static function verifyAdminToken()
    {
        if (function_exists('verify_token')) {
            return verify_token('link');
        }
        return true; // Graceful fallback
    }

    /**
     * Check if the current admin has access to this module
     *
     * @return bool
     */
    public static function hasAdminAccess()
    {
        // WHMCS handles module-level access through its addon permissions
        // This can be extended with fine-grained permissions
        return defined('WHMCS') && isset($_SESSION['adminid']);
    }
}
