<?php 
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers; // Use a dedicated Helpers namespace

/**
 * Provides static helper methods for secure data display in views.
 * This class is aliased or passed to the templates for easy use.
 */
class ViewHelper
{
    /**
     * Safely escapes HTML special characters for output (XSS prevention).
     * Alias: h()
     *
     * @param mixed $data The string or variable to escape.
     * @return string The escaped string.
     */
    public static function h($data): string
    {
        if (is_null($data)) {
            return '';
        }
        // Ensure non-string data is cast to string before escaping (e.g., numbers)
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Ensures a variable is cast to an integer for use in safe contexts like URLs.
     * Alias: id()
     *
     * @param mixed $data The data (e.g., a database ID).
     * @return int The integer value.
     */
    public static function id($data): int
    {
        return (int)$data;
    }

    /**
     * Escapes data and converts newlines to HTML <br /> tags (e.g., for addresses).
     * Alias: nl2br_h()
     *
     * @param string|null $data The string to process.
     * @return string The safe, formatted string.
     */
    public static function nl2br_h(?string $data): string
    {
        return nl2br(self::h($data));
    }
}