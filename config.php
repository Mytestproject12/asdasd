<?php
/*
 * Star Router Rent Configuration
 * Enhanced version with improved payment gateway support
 * Generated: 2025-07-18 20:56:03
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gainsmax_testbase');
define('DB_USER', 'gainsmax_testbase');
define('DB_PASS', 'Network@12');

// Site configuration
define('SITE_NAME', 'Star Router Rent');
define('SITE_URL', 'https://test.star-rent.vip');
define('ADMIN_EMAIL', 'admin@star-rent.vip');

// Payment configuration
define('PLISIO_API_KEY', ''); // Will be loaded from database settings
define('MIN_DEPOSIT', 100);
define('MAX_DEPOSIT', 50000);
define('MIN_WITHDRAWAL', 20);
define('WITHDRAWAL_FEE', 2.5);

// Security configuration
define('JWT_SECRET', bin2hex(random_bytes(32)));
define('ENCRYPTION_KEY', bin2hex(random_bytes(16)));

// Payment gateway URLs
define('PLISIO_API_URL', 'https://plisio.net/api/v1/');

// Database connection with enhanced error handling
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check your configuration.');
}

// Enhanced utility functions
function getSetting($key, $default = null) {
    global $pdo;
    static $settings = [];
    
    if (empty($settings)) {
        try {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            error_log('Failed to load settings: ' . $e->getMessage());
            return $default;
        }
    }
    
    return $settings[$key] ?? $default;
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function logActivity($user_id, $action, $details, $ip_address = null) {
    global $pdo;
    try {
        $activity_id = generateUUID();
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (id, user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$activity_id, $user_id, $action, $details, $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch (Exception $e) {
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Initialize referral manager when needed
function getReferralManager() {
    global $pdo;
    require_once __DIR__ . '/includes/ReferralManager.php';
    return new ReferralManager($pdo);
}

// Validate UUID format
function isValidUUID($uuid) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}

// Enhanced error logging
function logError($message, $context = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    error_log(json_encode($log_entry));
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Changed to 0 for production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');


// Timezone
date_default_timezone_set('UTC');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>