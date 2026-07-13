<?php
/**
 * Application Bootstrap
 *
 * Initializes the application, loads configuration, and sets up core services.
 */

// Define base path early
define('BASE_PATH', dirname(__FILE__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('LOGS_PATH', STORAGE_PATH . '/logs');

// Load helpers FIRST
require_once BASE_PATH . '/src/Helpers/env.php';

// Now we can use env() function
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG', false) ? '1' : '0');
ini_set('log_errors', '1');

// Set timezone
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

// Create necessary directories
$dirs = [LOGS_PATH, STORAGE_PATH . '/uploads'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Autoloader
spl_autoload_register(function ($class) {
    // App namespace
    if (strpos($class, 'App\\') === 0) {
        $path = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});
require_once BASE_PATH . '/src/Helpers/permissions.php';

// Load environment variables
loadEnv(BASE_PATH . '/.env');
loadEnv(BASE_PATH . '/.env.local');

// Load configuration
$config = require BASE_PATH . '/config/config.php';

// Initialize services
use App\Database\Database;
use App\Security\Sanitizer;
use App\Security\CsrfToken;

$database = new Database($config['database']);

// Register global database connection
$GLOBALS['db'] = $database;

// Setup session with security headers
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '0.0.0.0']);

    // Configure session cookie settings
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start([
        'use_only_cookies' => true,
    ]);
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSRF token middleware (optional, can be enabled per-route)
function requireCsrfToken(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!CsrfToken::validateFromRequest()) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
}

return [
    'db' => $database,
    'config' => $config,
];
