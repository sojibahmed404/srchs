<?php
/**
 * Application Configuration
 * Sirajganj Road Chowrasta High School Management System
 */

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Sirajganj Road Chowrasta High School');
define('APP_SHORT', 'SRCHS');
define('APP_VERSION', '1.0.0');

// Base URL - auto-detect
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Find the project root relative to web root
$basePath = $scriptDir;
// Navigate up to find the project root (where index.php lives)
$currentFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$relativePath = str_replace($docRoot, '', $currentFile);
$parts = explode('/', trim($relativePath, '/'));

// Find the project folder name from the path
$projectFolder = '';
foreach ($parts as $part) {
    if ($part === 'srchs' || $part === 'school-portal') {
        $projectFolder = $part;
        break;
    }
}

if ($projectFolder) {
    define('BASE_URL', $protocol . '://' . $host . '/' . $projectFolder);
} else {
    define('BASE_URL', $protocol . '://' . $host . rtrim($basePath, '/'));
}

// Path Configuration
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);

// Upload limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'SRCHS_SESSION');

// Pagination
define('RECORDS_PER_PAGE', 15);

// Timezone
date_default_timezone_set('Asia/Dhaka');
