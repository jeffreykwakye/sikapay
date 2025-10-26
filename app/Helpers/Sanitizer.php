<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Helpers;

/**
 * Provides static methods for sanitizing user input data.
 * Used primarily in controllers before passing data to models.
 */
class Sanitizer
{
    /**
     * Cleans general text input (names, titles, short descriptions).
     * Strips HTML tags and trims whitespace.
     *
     * @param string|null $data The input string.
     * @return string The sanitized string.
     */
    public static function text(?string $data): string
    {
        if (empty($data)) {
            return '';
        }
        return trim(strip_tags($data));
    }

    /**
     * Cleans email input.
     *
     * @param string|null $data The input string.
     * @return string The sanitized email or empty string if invalid.
     */
    public static function email(?string $data): string
    {
        if (empty($data)) {
            return '';
        }
        // Sanitizes and validates the email format
        $sanitized = filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        return filter_var($sanitized, FILTER_VALIDATE_EMAIL) ? $sanitized : '';
    }

    /**
     * Cleans long text input where basic formatting (like newlines) is acceptable, 
     * but strictly removes HTML tags. Suitable for addresses and notes.
     *
     * @param string|null $data The input string.
     * @return string The sanitized string.
     */
    public static function textarea(?string $data): string
    {
        if (empty($data)) {
            return '';
        }
        return trim(strip_tags($data));
    }

    /**
     * Cleans integer input.
     *
     * @param mixed $data The input data.
     * @return int The sanitized integer.
     */
    public static function int($data): int
    {
        return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
    }
}