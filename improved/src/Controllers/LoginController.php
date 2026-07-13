<?php
/**
 * Login Controller
 *
 * Handles user authentication with rate limiting, session management,
 * and remember-me functionality.
 */

namespace App\Controllers;

use App\Database\Database;
use App\Repository\UserRepository;
use App\Security\CsrfToken;
use App\Security\Sanitizer;

class LoginController
{
    private Database $db;
    private UserRepository $userRepository;

    // Configuration
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const RATE_LIMIT_WINDOW = 15; // minutes
    private const SESSION_TIMEOUT = 3600; // 1 hour in seconds
    private const REMEMBER_ME_VALIDITY = 7; // days

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userRepository = new UserRepository($db);
    }

    /**
     * Handle login form display and submission
     */
    public function login(): array
    {
        $response = [
            'success' => false,
            'error' => null,
            'csrf_token' => CsrfToken::generate(),
        ];

        // Handle POST request (form submission)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $response = $this->handleLoginSubmission();
        }

        return $response;
    }

    /**
     * Process login submission
     */
    private function handleLoginSubmission(): array
    {
        $response = [
            'success' => false,
            'error' => null,
        ];

        // Validate CSRF token
        if (!CsrfToken::validateFromRequest()) {
            $response['error'] = 'Invalid security token. Please try again.';
            return $response;
        }

        // Get client IP address
        $ipAddress = $this->getClientIp();

        // Check rate limiting
        $failedAttempts = $this->userRepository->getFailedLoginAttempts(
            $ipAddress,
            self::RATE_LIMIT_WINDOW
        );

        if ($failedAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            $response['error'] = 'Too many login attempts. Please try again in 15 minutes.';
            return $response;
        }

        // Get and sanitize input
        $identifier = Sanitizer::string($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);

        // Validate input
        if (empty($identifier) || empty($password)) {
            $response['error'] = 'Please enter both username/email and password.';
            return $response;
        }

        // Find user
        $user = $this->userRepository->findByUsernameOrEmail($identifier);

        if (!$user || !$user['is_active']) {
            // Record failed attempt
            $this->userRepository->recordLoginAttempt(
                $user['id'] ?? null,
                $ipAddress,
                $identifier,
                false
            );

            $response['error'] = 'Invalid username/email or password.';
            return $response;
        }

        // Verify password
        if (!$this->userRepository->verifyPassword($password, $user['password_hash'])) {
            // Record failed attempt
            $this->userRepository->recordLoginAttempt(
                $user['id'],
                $ipAddress,
                $identifier,
                false
            );

            $response['error'] = 'Invalid username/email or password.';
            return $response;
        }

        // Successful login - record attempt
        $this->userRepository->recordLoginAttempt(
            $user['id'],
            $ipAddress,
            $identifier,
            true
        );

        // Update last login time
        $this->userRepository->updateLastLogin($user['id']);

        // Initialize session
        $this->initializeSession($user['id']);

        // Handle remember me
        if ($rememberMe) {
            $token = $this->userRepository->createRememberToken(
                $user['id'],
                self::REMEMBER_ME_VALIDITY
            );
            $this->setRememberMeCookie($token, self::REMEMBER_ME_VALIDITY);
        }

        $response['success'] = true;
        $response['user_id'] = $user['id'];
        $response['redirect'] = '/admin/dashboard';

        return $response;
    }

    /**
     * Initialize secure session
     */
    private function initializeSession(int $userId): void
    {
        // Set secure session options before starting session
        if (!isset($_SESSION)) {
            $this->configureSessionSecurity();
            session_start();
        }

        // Store user info in session
        $_SESSION['user_id'] = $userId;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $this->getClientIp();

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    /**
     * Configure session security settings
     */
    private function configureSessionSecurity(): void
    {
        // HTTPOnly - prevent JavaScript access
        ini_set('session.cookie_httponly', '1');

        // Secure - only HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }

        // SameSite - prevent CSRF
        ini_set('session.cookie_samesite', 'Lax');

        // Session timeout
        ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);
    }

    /**
     * Set remember me cookie
     */
    private function setRememberMeCookie(string $token, int $daysValid): void
    {
        $expiryTime = time() + ($daysValid * 86400);

        setcookie(
            'remember_me',
            $token,
            [
                'expires' => $expiryTime,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Handle logout
     */
    public function logout(): array
    {
        $response = [
            'success' => true,
            'message' => 'You have been logged out successfully.',
        ];

        // Clear remember me cookie if exists
        if (isset($_COOKIE['remember_me'])) {
            $this->userRepository->revokeRememberToken($_COOKIE['remember_me']);
            setcookie('remember_me', '', -1, '/');
            unset($_COOKIE['remember_me']);
        }

        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        return $response;
    }

    /**
     * Verify remember me token and restore session
     */
    public function verifyRememberMe(): ?int
    {
        if (!isset($_COOKIE['remember_me'])) {
            return null;
        }

        $token = $_COOKIE['remember_me'];
        $tokenData = $this->userRepository->verifyRememberToken($token);

        if (!$tokenData) {
            setcookie('remember_me', '', -1, '/');
            return null;
        }

        // Get user data
        $user = $this->userRepository->find($tokenData['user_id']);

        if (!$user || !$user['is_active']) {
            setcookie('remember_me', '', -1, '/');
            return null;
        }

        // Restore session
        $this->initializeSession($user['id']);

        return $user['id'];
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user ID
     */
    public static function getCurrentUserId(): ?int
    {
        if (self::isLoggedIn() && isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        return null;
    }

    /**
     * Verify session validity (IP and timeout)
     */
    public static function verifySession(): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION['login_time'] > self::SESSION_TIMEOUT) {
            return false;
        }

        // Verify IP hasn't changed
        if (isset($_SESSION['ip_address'])) {
            $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
            $currentIp = '0.0.0.0';

            foreach ($ipKeys as $key) {
                if (!empty($_SERVER[$key])) {
                    $ips = explode(',', $_SERVER[$key]);
                    $currentIp = trim($ips[0]);
                    break;
                }
            }

            if ($_SESSION['ip_address'] !== $currentIp) {
                return false;
            }
        }

        return true;
    }
}
