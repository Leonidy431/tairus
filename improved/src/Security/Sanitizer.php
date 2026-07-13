<?php
/**
 * Input Sanitizer
 *
 * Provides secure input sanitization and validation methods.
 */

namespace App\Security;

class Sanitizer
{
    /**
     * Sanitize string input
     */
    public static function string(mixed $input, bool $strip_tags = true): string
    {
        if (!is_string($input)) {
            $input = (string)$input;
        }

        if ($strip_tags) {
            $input = strip_tags($input);
        }

        return trim($input);
    }

    /**
     * Sanitize and validate email
     */
    public static function email(mixed $input): ?string
    {
        $email = self::string($input, true);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return strtolower($email);
    }

    /**
     * Sanitize integer input
     */
    public static function integer(mixed $input): ?int
    {
        $int = filter_var($input, FILTER_VALIDATE_INT);
        return $int !== false ? $int : null;
    }

    /**
     * Sanitize float input
     */
    public static function float(mixed $input): ?float
    {
        $float = filter_var($input, FILTER_VALIDATE_FLOAT);
        return $float !== false ? $float : null;
    }

    /**
     * Sanitize URL
     */
    public static function url(mixed $input): ?string
    {
        $url = self::string($input, true);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $url;
    }

    /**
     * Sanitize file name
     */
    public static function fileName(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $filename = preg_replace('/\.+/', '.', $filename);
        return trim($filename, '.');
    }

    /**
     * HTML escape output
     */
    public static function html(mixed $input): string
    {
        return htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * JavaScript escape output
     */
    public static function javascript(mixed $input): string
    {
        return json_encode((string)$input);
    }

    /**
     * SQL safe (for use with prepared statements)
     * Note: Always use prepared statements instead of this
     */
    public static function sqlLike(string $input): string
    {
        return addcslashes($input, '\\%_');
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    public static function date(string $input, string $format = 'Y-m-d'): ?string
    {
        $date = \DateTime::createFromFormat($format, $input);

        if ($date === false || $date->format($format) !== $input) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    /**
     * Validate phone number (basic)
     */
    public static function phone(string $input): ?string
    {
        $phone = preg_replace('/[^\d+\-\s()]/', '', $input);
        $phone = preg_replace('/\s+/', ' ', trim($phone));

        if (preg_match('/^[\d+\-\s()]{7,}$/', $phone)) {
            return $phone;
        }

        return null;
    }
}
