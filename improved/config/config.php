<?php
/**
 * Application Configuration
 *
 * This file contains all configuration settings for the painting sales website.
 * Environment-specific values should be loaded from .env files.
 */

return [
    // Database Configuration
    'database' => [
        'driver'   => env('DB_DRIVER', 'mysql'),
        'host'     => env('DB_HOST', 'localhost'),
        'port'     => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'painting_sales'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset'  => env('DB_CHARSET', 'utf8mb4'),
    ],

    // Email Configuration
    'email' => [
        'admin_email' => env('ADMIN_EMAIL', 'admin@art.local'),
        'from_address' => env('EMAIL_FROM', 'noreply@art.local'),
        'from_name' => env('EMAIL_FROM_NAME', 'Art Gallery'),
        'driver' => env('MAIL_DRIVER', 'smtp'),
        'smtp_host' => env('SMTP_HOST', 'localhost'),
        'smtp_port' => env('SMTP_PORT', 587),
        'smtp_username' => env('SMTP_USERNAME', ''),
        'smtp_password' => env('SMTP_PASSWORD', ''),
        'smtp_encryption' => env('SMTP_ENCRYPTION', 'tls'),
    ],

    // Application Settings
    'app' => [
        'debug' => env('APP_DEBUG', false),
        'url' => env('APP_URL', 'http://art.local'),
        'name' => env('APP_NAME', 'Art Gallery'),
        'timezone' => env('APP_TIMEZONE', 'Europe/Moscow'),
    ],

    // Pagination
    'pagination' => [
        'sales_per_page' => 5,
        'pictures_per_page' => 3,
        'news_per_page' => 10,
    ],

    // File Upload Settings
    'upload' => [
        'max_file_size' => 11_333_000, // bytes
        'allowed_types' => ['jpg', 'jpeg', 'gif', 'png', 'doc', 'docx', 'rtf', 'xls', 'xlsx'],
        'storage_path' => env('UPLOAD_PATH', 'uploads/'),
    ],

    // Security Settings
    'security' => [
        'csrf_enabled' => true,
        'csrf_token_name' => '_token',
        'password_hash_algorithm' => PASSWORD_BCRYPT,
        'password_hash_options' => ['cost' => 12],
    ],

    // Logging
    'logging' => [
        'driver' => env('LOG_DRIVER', 'file'),
        'level' => env('LOG_LEVEL', 'info'),
        'path' => env('LOG_PATH', 'logs/'),
    ],
];
