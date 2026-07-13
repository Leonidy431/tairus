<?php
/**
 * CSRF Token Handler
 *
 * Provides secure CSRF token generation and validation.
 */

namespace App\Security;

class CsrfToken
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = '_csrf_token';

    public static function generate(): string
    {
        self::startSessionIfNeeded();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function getToken(): string
    {
        return self::generate();
    }

    public static function validate(string $token): bool
    {
        self::startSessionIfNeeded();

        if (empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function validateFromRequest(): bool
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if ($token === null) {
            return false;
        }

        return self::validate($token);
    }

    private static function startSessionIfNeeded(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'use_only_cookies' => true,
                'httponly' => true,
                'secure' => !self::isLocalhost(),
                'samesite' => 'Lax',
            ]);
        }
    }

    private static function isLocalhost(): bool
    {
        return in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
    }

    public static function regenerate(): string
    {
        self::startSessionIfNeeded();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        return $_SESSION[self::SESSION_KEY];
    }
}
