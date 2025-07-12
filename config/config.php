<?php
/**
 * Configuration file for Travel Request System
 * Adjust these settings according to your environment
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mowaa_db');

// File Upload Configuration
define('UPLOAD_DIR', '../uploads/travel-requests/');
define('MAX_FILE_SIZE', 1024 * 1024); // 1MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'gif']);

// Image Compression Settings
define('IMAGE_QUALITY', 75); // JPEG quality (0-100)
define('MAX_IMAGE_WIDTH', 1920); // Maximum image width in pixels
define('MAX_IMAGE_HEIGHT', 1080); // Maximum image height in pixels

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@mowaa.com');
define('FROM_NAME', 'MOWAA Travel System');

// System Settings
define('TIMEZONE', 'UTC');
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('CURRENCY_SYMBOL', '$');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('UPLOAD_SCAN_ENABLED', true); // Enable virus scanning if available

// Application URLs
define('BASE_URL', 'http://localhost/mowaa/');
define('DASHBOARD_URL', BASE_URL . 'app/index.html');
define('TRAVEL_REQUEST_URL', BASE_URL . 'app/travel-request.php');

// Status Constants
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_CANCELLED', 'cancelled');

// Approval Workflow
define('AUTO_APPROVE_LIMIT', 500.00); // Auto-approve requests under this amount
define('REQUIRE_DOUBLE_APPROVAL', 5000.00); // Require double approval over this amount

// File Storage
define('STORE_FILES_LOCALLY', true);
define('CLOUD_STORAGE_ENABLED', false);
define('CLOUD_STORAGE_BUCKET', 'mowaa-travel-docs');

// Logging
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', '../logs/travel-request.log');

// Backup Settings
define('BACKUP_ENABLED', true);
define('BACKUP_RETENTION_DAYS', 90);
define('BACKUP_DIR', '../backups/');

/**
 * Helper function to get database connection
 */
function getDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

/**
 * Helper function to log activities
 */
function logActivity($message, $level = 'INFO', $context = []) {
    if (!LOG_ENABLED) return;
    
    $timestamp = date(DATE_FORMAT);
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] [$level] $message $contextStr" . PHP_EOL;
    
    error_log($logMessage, 3, LOG_FILE);
}

/**
 * Helper function to sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Helper function to generate unique request ID
 */
function generateRequestId() {
    return 'TR_' . date('Ymd') . '_' . strtoupper(uniqid());
}

/**
 * Helper function to format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Helper function to check if file type is allowed
 */
function isAllowedFileType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_FILE_TYPES);
}

/**
 * Helper function to get file size in human readable format
 */
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Create necessary directories
$directories = [
    dirname(UPLOAD_DIR),
    dirname(LOG_FILE),
    dirname(BACKUP_DIR)
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
