<?php
/**
 * Test Bootstrap
 *
 * Sets up the test environment for all tests.
 */

define('BASE_PATH', dirname(dirname(__FILE__)));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('LOGS_PATH', STORAGE_PATH . '/logs');

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $path = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    } elseif (strpos($class, 'Tests\\') === 0) {
        $path = BASE_PATH . '/tests/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});

// Load helpers
require_once BASE_PATH . '/src/Helpers/env.php';

// Create test directories
$dirs = [LOGS_PATH, STORAGE_PATH . '/uploads', STORAGE_PATH . '/test-uploads'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
