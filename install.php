<?php
/*
 * Star Router Rent - Enhanced Auto Installer
 * Complete installation wizard with improved payment gateway setup
 */

// Prevent direct access after installation
if (file_exists('config.php') && !isset($_GET['force'])) {
    die('Installation already completed. Delete this file for security.');
}

$error = '';
$success = '';
$step = isset($_POST['step']) ? $_POST['step'] : 1;

// Add generateUUID function for install.php
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    try {
        // Database configuration
        $db_host = $_POST['db_host'];
        $db_name = $_POST['db_name'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        
        // Admin configuration
        $admin_username = $_POST['admin_username'];
        $admin_email = $_POST['admin_email'];
        $admin_password = $_POST['admin_password'];
        
        // Site configuration
        $site_name = $_POST['site_name'];
        $site_url = $_POST['site_url'];
        
        // Payment configuration
        $plisio_api_key = $_POST['plisio_api_key'];
        $min_deposit = $_POST['min_deposit'];
        $withdrawal_fee = $_POST['withdrawal_fee'];
        
        // Email configuration
        $smtp_host = $_POST['smtp_host'] ?? '';
        $smtp_port = $_POST['smtp_port'] ?? '587';
        $smtp_username = $_POST['smtp_username'] ?? '';
        $smtp_password = $_POST['smtp_password'] ?? '';
        
        // Test database connection
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create comprehensive database schema
        $sql = "
        -- Enhanced Users table
        CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(36) PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            first_name VARCHAR(50) NULL,
            last_name VARCHAR(50) NULL,
            country VARCHAR(3) NULL,
            password VARCHAR(255) NOT NULL,
            telegram_id BIGINT NULL,
            telegram_username VARCHAR(50) NULL,
            balance DECIMAL(15,2) DEFAULT 0.00,
            total_earnings DECIMAL(15,2) DEFAULT 0.00,
            total_invested DECIMAL(15,2) DEFAULT 0.00,
            total_withdrawn DECIMAL(15,2) DEFAULT 0.00,
            referral_earnings DECIMAL(15,2) DEFAULT 0.00,
            rental_earnings DECIMAL(15,2) DEFAULT 0.00,
            investment_earnings DECIMAL(15,2) DEFAULT 0.00,
            referral_code VARCHAR(20) UNIQUE NOT NULL,
            referred_by VARCHAR(20) NULL,
            status ENUM('active', 'suspended', 'pending', 'banned') DEFAULT 'active',
            telegram_verified BOOLEAN DEFAULT FALSE,
            kyc_status ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            UNIQUE KEY unique_username (username),
            UNIQUE KEY unique_email (email),
            UNIQUE KEY unique_referral_code (referral_code),
            INDEX idx_referral_code (referral_code),
            INDEX idx_email (email),
            INDEX idx_status (status)
        );

        -- Admin users table
        CREATE TABLE IF NOT EXISTS admin_users (
            id VARCHAR(36) PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'moderator', 'support') DEFAULT 'admin',
            status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Premium router devices
        CREATE TABLE IF NOT EXISTS devices (
            id VARCHAR(36) PRIMARY KEY,
            device_id VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            model VARCHAR(50) NOT NULL,
            location VARCHAR(100) NOT NULL,
            status ENUM('available', 'rented', 'maintenance', 'offline', 'reserved') DEFAULT 'available',
            daily_rate DECIMAL(10,2) NOT NULL,
            setup_fee DECIMAL(10,2) DEFAULT 0.00,
            max_speed_down INT NOT NULL,
            max_speed_up INT NOT NULL,
            uptime_percentage DECIMAL(5,2) DEFAULT 99.00,
            total_earnings DECIMAL(15,2) DEFAULT 0.00,
            total_rentals INT DEFAULT 0,
            images TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_location (location)
        );

        -- Investment plans
        CREATE TABLE IF NOT EXISTS investment_plans (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            duration_days INT NOT NULL,
            daily_rate DECIMAL(5,2) NOT NULL,
            min_amount DECIMAL(15,2) NOT NULL,
            max_amount DECIMAL(15,2) NOT NULL,
            description TEXT NULL,
            features JSON NULL,
            is_active BOOLEAN DEFAULT TRUE,
            is_featured BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- User investments
        CREATE TABLE IF NOT EXISTS investments (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            plan_id VARCHAR(36) NOT NULL,
            plan_name VARCHAR(100) NOT NULL,
            plan_duration INT NOT NULL,
            investment_amount DECIMAL(15,2) NOT NULL,
            daily_rate DECIMAL(5,2) NOT NULL,
            expected_daily_profit DECIMAL(15,2) NOT NULL,
            total_earned DECIMAL(15,2) DEFAULT 0.00,
            total_days_active INT DEFAULT 0,
            status ENUM('pending', 'active', 'completed', 'cancelled', 'suspended', 'matured') DEFAULT 'pending',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            actual_start_date DATE NULL,
            maturity_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_status (user_id, status),
            INDEX idx_status (status)
        );

        -- Device rentals
        CREATE TABLE IF NOT EXISTS rentals (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            device_id VARCHAR(36) NOT NULL,
            plan_type ENUM('basic', 'standard', 'premium', 'custom') NOT NULL,
            plan_name VARCHAR(100) NULL,
            rental_duration INT NOT NULL,
            daily_profit_rate DECIMAL(5,2) NOT NULL,
            total_cost DECIMAL(15,2) NOT NULL,
            setup_fee DECIMAL(15,2) DEFAULT 0.00,
            expected_daily_profit DECIMAL(15,2) NOT NULL,
            actual_total_profit DECIMAL(15,2) DEFAULT 0.00,
            total_days_active INT DEFAULT 0,
            status ENUM('pending', 'active', 'completed', 'cancelled', 'suspended', 'expired') DEFAULT 'pending',
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            actual_start_date DATE NULL,
            actual_end_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            INDEX idx_user_status (user_id, status),
            INDEX idx_device_status (device_id, status)
        );

        -- Enhanced payment transactions
        CREATE TABLE IF NOT EXISTS payments (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            transaction_id VARCHAR(100) NULL,
            amount DECIMAL(15,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'USD',
            crypto_currency VARCHAR(10) NULL,
            payment_method ENUM('crypto', 'binance', 'plisio', 'balance', 'manual', 'system', 'bank_transfer', 'paypal', 'stripe') NOT NULL DEFAULT 'crypto',
            status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded', 'expired') DEFAULT 'pending',
            type ENUM('rental', 'investment', 'withdrawal', 'referral_bonus', 'deposit', 'fee', 'refund') NOT NULL,
            description TEXT NULL,
            gateway_data JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_status (user_id, status),
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_type_status (type, status),
            INDEX idx_payment_method_status (payment_method, status)
        );

        -- Enhanced withdrawal requests
        CREATE TABLE IF NOT EXISTS withdrawal_requests (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            fee_amount DECIMAL(15,2) NOT NULL,
            net_amount DECIMAL(15,2) NOT NULL,
            withdrawal_method ENUM('crypto', 'binance', 'plisio', 'bank_transfer', 'paypal') NOT NULL DEFAULT 'crypto',
            withdrawal_address VARCHAR(255) NULL,
            status ENUM('pending', 'approved', 'processing', 'completed', 'rejected', 'cancelled') DEFAULT 'pending',
            admin_notes TEXT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_status (user_id, status),
            INDEX idx_status (status)
        );

        -- Multi-level referral system
        CREATE TABLE IF NOT EXISTS referrals (
            id VARCHAR(36) PRIMARY KEY,
            referrer_id VARCHAR(36) NOT NULL,
            referred_id VARCHAR(36) NOT NULL,
            level INT NOT NULL DEFAULT 1,
            commission_rate DECIMAL(5,2) NOT NULL,
            total_commission_earned DECIMAL(15,2) DEFAULT 0.00,
            total_referral_volume DECIMAL(15,2) DEFAULT 0.00,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY fk_referrer (referrer_id),
            KEY fk_referred (referred_id),
            INDEX idx_referrer (referrer_id),
            INDEX idx_referred (referred_id),
            INDEX idx_level (level)
        );

        -- Enhanced system configuration
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            setting_type ENUM('string', 'number', 'boolean', 'json', 'text') DEFAULT 'string',
            category VARCHAR(50) NOT NULL,
            description TEXT NULL,
            is_public BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Email templates
        CREATE TABLE IF NOT EXISTS email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_key VARCHAR(100) UNIQUE NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            variables JSON NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Notifications system
        CREATE TABLE IF NOT EXISTS notifications (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY fk_notification_user (user_id),
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created_at (created_at)
        );

        -- Website pages
        CREATE TABLE IF NOT EXISTS webpages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            meta_title VARCHAR(255) NULL,
            meta_description TEXT NULL,
            content LONGTEXT NULL,
            status ENUM('published', 'draft', 'private') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_status (status)
        );

        -- Enhanced activity logs
        CREATE TABLE IF NOT EXISTS activity_logs (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        );

        -- Currencies table for enhanced crypto support
        CREATE TABLE IF NOT EXISTS currencies (
            id VARCHAR(36) PRIMARY KEY,
            code VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            symbol VARCHAR(10) NOT NULL,
            decimals INT DEFAULT 8,
            min_amount DECIMAL(20,8) DEFAULT 0.00000001,
            network VARCHAR(50) NULL,
            icon VARCHAR(10) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            exchange_rate DECIMAL(20,8) DEFAULT 1.00000000,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_code (code),
            INDEX idx_active (is_active)
        );
        ";
        
        $pdo->exec($sql);
        
        // Create a function to generate UUIDs if not exists
        try {
            $pdo->exec("
                CREATE FUNCTION IF NOT EXISTS generate_uuid() 
                RETURNS CHAR(36) 
                READS SQL DATA 
                DETERMINISTIC 
                RETURN LOWER(CONCAT(
                    LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFF)), 8, '0'), '-',
                    LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
                    '4',
                    LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
                    HEX(FLOOR(RAND() * 4 + 8)),
                    LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
                    LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFFFFFF)), 12, '0')
                ))
            ");
        } catch (Exception $e) {
            // If function creation fails, we'll use PHP to generate UUIDs
            error_log('UUID function creation failed: ' . $e->getMessage());
        }
        
        // Insert admin user
        $admin_id = generateUUID();
        $admin_password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (id, username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'super_admin', 'active', NOW())");
        $stmt->execute([$admin_id, $admin_username, $admin_email, $admin_password_hash]);
        
        // Insert premium router devices
        $devices = [
            ['SRV001', 'Star Premium Router Alpha', 'StarTech Pro X1', 'New York, USA', 25.00, 1000, 100, 99.9],
            ['SRV002', 'Star Premium Router Beta', 'StarTech Pro X2', 'London, UK', 28.00, 1200, 120, 99.8],
            ['SRV003', 'Star Premium Router Gamma', 'StarTech Enterprise', 'Tokyo, Japan', 35.00, 1500, 150, 99.9],
            ['SRV004', 'Star Premium Router Delta', 'StarTech Ultra', 'Sydney, Australia', 30.00, 1300, 130, 99.7],
            ['SRV005', 'Star Premium Router Epsilon', 'StarTech Elite Pro', 'Frankfurt, Germany', 32.00, 1400, 140, 99.8],
            ['SRV006', 'Star Premium Router Zeta', 'StarTech Max', 'Singapore', 38.00, 1600, 160, 99.9],
            ['SRV007', 'Star Premium Router Eta', 'StarTech Supreme', 'Toronto, Canada', 26.00, 1100, 110, 99.6],
            ['SRV008', 'Star Premium Router Theta', 'StarTech Quantum', 'Amsterdam, Netherlands', 34.00, 1450, 145, 99.8]
        ];
        
        foreach ($devices as $device) {
            $device_id = generateUUID();
            $stmt = $pdo->prepare("INSERT INTO devices (id, device_id, name, model, location, daily_rate, max_speed_down, max_speed_up, uptime_percentage, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())");
            $stmt->execute(array_merge([$device_id], $device));
        }
        
        // Insert enhanced investment plans
        $plans = [
            ['Starter Plan', 30, 0.8, 100.00, 1000.00, 'Perfect for beginners - short term with good returns', '["0.8% Daily Return", "24% Monthly Return", "$100 Minimum", "24/7 Support", "Instant Activation"]', 1, 0, 1],
            ['Growth Plan', 90, 1.2, 500.00, 5000.00, 'Balanced growth plan for steady income', '["1.2% Daily Return", "108% Total Return", "$500 Minimum", "Priority Support", "Weekly Reports"]', 1, 1, 2],
            ['Premium Plan', 180, 1.5, 1000.00, 10000.00, 'Premium plan for serious investors', '["1.5% Daily Return", "270% Total Return", "$1,000 Minimum", "VIP Support", "Daily Reports"]', 1, 0, 3],
            ['Elite Plan', 365, 2.0, 5000.00, 50000.00, 'Elite plan for maximum returns', '["2.0% Daily Return", "730% Annual Return", "$5,000 Minimum", "Personal Manager", "Real-time Analytics"]', 1, 0, 4]
        ];
        
        foreach ($plans as $plan) {
            $plan_id = generateUUID();
            $stmt = $pdo->prepare("INSERT INTO investment_plans (id, name, duration_days, daily_rate, min_amount, max_amount, description, features, is_active, is_featured, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute(array_merge([$plan_id], $plan));
        }
        
        // Insert comprehensive system settings
        $settings = [
            ['site_name', $site_name, 'string', 'general', 'Site name', 1],
            ['site_url', $site_url, 'string', 'general', 'Site URL', 1],
            ['admin_email', $admin_email, 'string', 'general', 'Admin email', 0],
            ['site_description', 'Premium router rental platform with guaranteed daily profits', 'text', 'general', 'Site description', 1],
            ['site_keywords', 'router rental, passive income, daily profits, cryptocurrency, investment', 'text', 'general', 'Site keywords', 1],
            ['plisio_api_key', $plisio_api_key, 'string', 'payments', 'Plisio API key', 0],
            ['min_deposit', $min_deposit, 'number', 'payments', 'Minimum deposit amount', 1],
            ['max_deposit', '50000', 'number', 'payments', 'Maximum deposit amount', 1],
            ['min_withdrawal', '20', 'number', 'payments', 'Minimum withdrawal amount', 1],
            ['withdrawal_fee', $withdrawal_fee, 'number', 'payments', 'Withdrawal fee percentage', 1],
            ['referral_level_1_rate', '10.0', 'number', 'referrals', 'Level 1 referral commission rate', 1],
            ['referral_level_2_rate', '7.0', 'number', 'referrals', 'Level 2 referral commission rate', 1],
            ['referral_level_3_rate', '5.0', 'number', 'referrals', 'Level 3 referral commission rate', 1],
            ['referral_max_deposits', '2', 'number', 'referrals', 'Maximum deposits that earn referral commission', 1],
            ['auto_approve_withdrawal_limit', '500', 'number', 'payments', 'Auto approve withdrawals under this amount', 0],
            ['maintenance_mode', 'false', 'boolean', 'general', 'Maintenance mode', 0],
            ['registration_enabled', 'true', 'boolean', 'general', 'Allow new registrations', 1],
            ['kyc_required', 'false', 'boolean', 'security', 'Require KYC verification', 1],
            ['dashboard_welcome_message', 'Welcome back to your investment dashboard!', 'string', 'dashboard', 'Dashboard welcome message', 1],
            ['dashboard_show_stats', 'true', 'boolean', 'dashboard', 'Show statistics cards', 1],
            ['dashboard_show_recent_transactions', 'true', 'boolean', 'dashboard', 'Show recent transactions', 1],
            ['dashboard_show_active_investments', 'true', 'boolean', 'dashboard', 'Show active investments', 1],
            ['dashboard_announcement', '', 'text', 'dashboard', 'Dashboard announcement message', 1],
            ['dashboard_announcement_type', 'info', 'string', 'dashboard', 'Dashboard announcement type', 1],
            ['dashboard_show_announcement', 'false', 'boolean', 'dashboard', 'Show dashboard announcement', 1],
            ['smtp_host', $smtp_host, 'string', 'email', 'SMTP host', 0],
            ['smtp_port', $smtp_port, 'number', 'email', 'SMTP port', 0],
            ['smtp_username', $smtp_username, 'string', 'email', 'SMTP username', 0],
            ['smtp_password', $smtp_password, 'string', 'email', 'SMTP password', 0],
            ['email_notifications', 'true', 'boolean', 'email', 'Enable email notifications', 1]
        ];
        
        foreach ($settings as $setting) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute($setting);
        }
        
        // Insert default currencies
        $currencies = [
            ['USDT', 'Tether', 'USDT', 6, 1.0, 'TRC20', '₮'],
            ['USDT_BEP20', 'Tether (BEP20)', 'USDT', 18, 1.0, 'BEP20', '₮'],
            ['BTC', 'Bitcoin', 'BTC', 8, 0.0001, 'Bitcoin', '₿'],
            ['ETH', 'Ethereum', 'ETH', 18, 0.001, 'ERC20', 'Ξ'],
            ['BCH', 'Bitcoin Cash', 'BCH', 8, 0.001, 'Bitcoin Cash', '₿'],
            ['TRX', 'TRON', 'TRX', 6, 10.0, 'TRON', '⚡'],
            ['XMR', 'Monero', 'XMR', 12, 0.01, 'Monero', 'ɱ'],
            ['DASH', 'Dash', 'DASH', 8, 0.01, 'Dash', 'Đ'],
            ['ZEC', 'Zcash', 'ZEC', 8, 0.01, 'Zcash', 'ⓩ']
        ];
        
        foreach ($currencies as $currency) {
            $currency_id = generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO currencies (id, code, name, symbol, decimals, min_amount, network, icon, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    name = VALUES(name),
                    symbol = VALUES(symbol),
                    decimals = VALUES(decimals),
                    min_amount = VALUES(min_amount),
                    network = VALUES(network),
                    icon = VALUES(icon)
            ");
            $stmt->execute(array_merge([$currency_id], $currency));
        }
        
        // Create enhanced config.php
        $config_content = "<?php
/*
 * Star Router Rent Configuration
 * Enhanced version with improved payment gateway support
 * Generated: " . date('Y-m-d H:i:s') . "
 */

// Database configuration
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Site configuration
define('SITE_NAME', '$site_name');
define('SITE_URL', '$site_url');
define('ADMIN_EMAIL', '$admin_email');

// Payment configuration
define('PLISIO_API_KEY', ''); // Will be loaded from database settings
define('MIN_DEPOSIT', $min_deposit);
define('MAX_DEPOSIT', 50000);
define('MIN_WITHDRAWAL', 20);
define('WITHDRAWAL_FEE', $withdrawal_fee);

// Security configuration
define('JWT_SECRET', '" . bin2hex(random_bytes(32)) . "');
define('ENCRYPTION_KEY', '" . bin2hex(random_bytes(16)) . "');

// Payment gateway URLs
define('PLISIO_API_URL', 'https://plisio.net/api/v1/');

// Database connection with enhanced error handling
try {
    \$pdo = new PDO(
        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"
        ]
    );
} catch(PDOException \$e) {
    error_log('Database connection failed: ' . \$e->getMessage());
    die('Database connection failed. Please check your configuration.');
}

// Enhanced utility functions
function getSetting(\$key, \$default = null) {
    global \$pdo;
    static \$settings = [];
    
    if (empty(\$settings)) {
        try {
            \$stmt = \$pdo->prepare(\"SELECT setting_key, setting_value FROM system_settings\");
            \$stmt->execute();
            \$settings = \$stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception \$e) {
            error_log('Failed to load settings: ' . \$e->getMessage());
            return \$default;
        }
    }
    
    return \$settings[\$key] ?? \$default;
}

function logActivity(\$user_id, \$action, \$details, \$ip_address = null) {
    global \$pdo;
    try {
        \$activity_id = generateUUID();
        \$stmt = \$pdo->prepare(\"
            INSERT INTO activity_logs (id, user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        \");
        \$stmt->execute([\$activity_id, \$user_id, \$action, \$details, \$ip_address ?? \$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    } catch (Exception \$e) {
        error_log('Failed to log activity: ' . \$e->getMessage());
    }
}

function sanitizeInput(\$input) {
    return htmlspecialchars(trim(\$input), ENT_QUOTES, 'UTF-8');
}

function generateSecureToken(\$length = 32) {
    return bin2hex(random_bytes(\$length));
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

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Timezone
date_default_timezone_set('UTC');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>";
        
        file_put_contents('config.php', $config_content);
        
        // Create logs directory
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
        
        // Create backups directory
        if (!is_dir('backups')) {
            mkdir('backups', 0755, true);
        }
        
        $success = "Installation completed successfully!";
        $step = 3;
        
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Router Rent - Enhanced Installation Wizard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .container { 
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.15); 
            max-width: 800px; 
            width: 95%; 
            padding: 40px; 
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        .header { 
            text-align: center; 
            margin-bottom: 40px; 
        }
        .header h1 { 
            color: #333; 
            font-size: 32px; 
            margin-bottom: 10px; 
            font-weight: 700;
        }
        .header p { 
            color: #666; 
            font-size: 18px; 
        }
        .form-group { 
            margin-bottom: 25px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #333; 
            font-size: 14px;
        }
        .form-group input { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #e1e5e9; 
            border-radius: 10px; 
            font-size: 16px; 
            transition: all 0.3s ease; 
            background: #fafbfc;
        }
        .form-group input:focus { 
            outline: none; 
            border-color: #667eea; 
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 18px 35px; 
            border: none; 
            border-radius: 12px; 
            font-size: 18px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            width: 100%; 
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .alert { 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            font-weight: 500;
        }
        .alert-error { 
            background: linear-gradient(135deg, #ff6b6b, #ee5a52); 
            color: white; 
        }
        .alert-success { 
            background: linear-gradient(135deg, #51cf66, #40c057); 
            color: white; 
        }
        .step-indicator { 
            display: flex; 
            justify-content: center; 
            margin-bottom: 40px; 
        }
        .step { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 15px; 
            font-weight: bold; 
            font-size: 18px;
        }
        .step.active { 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .step.inactive { 
            background: #e1e5e9; 
            color: #999; 
        }
        .step.completed {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
        }
        .success-box { 
            text-align: center; 
            padding: 40px 20px; 
        }
        .credentials { 
            background: linear-gradient(135deg, #f8f9fa, #e9ecef); 
            padding: 25px; 
            border-radius: 15px; 
            margin: 25px 0; 
            text-align: left; 
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin: 35px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f3f4;
        }
        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
        .feature-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .feature-list h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .feature-list ul {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 5px 0;
            color: #666;
        }
        .feature-list li::before {
            content: '✅';
            margin-right: 10px;
        }
        
        .crypto-note {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .crypto-note h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .crypto-note p {
            color: #1976d2;
            margin: 0;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .container { padding: 25px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($step == 1): ?>
            <div class="header">
                <h1>🚀 Star Router Rent</h1>
                <p>Professional Router Rental & Investment Platform - Installation Wizard</p>
            </div>
            
            <div class="step-indicator">
                <div class="step active">1</div>
                <div class="step inactive">2</div>
                <div class="step inactive">3</div>
            </div>
            
            <div class="feature-list">
                <h3>🎯 Platform Features:</h3>
                <ul>
                    <li>Professional router rental investment platform</li>
                    <li>Plisio.net integration supporting 50+ cryptocurrencies (BTC, USDT, ETH, etc.)</li>
                    <li>Multi-level referral system with commission tracking</li>
                    <li>Advanced admin panel with comprehensive management tools</li>
                    <li>Real-time payment processing and webhook handling</li>
                    <li>Email notification system with customizable templates</li>
                    <li>Mobile-responsive design with dark/light theme</li>
                    <li>Investment plans with guaranteed daily returns</li>
                    <li>Secure user authentication and session management</li>
                    <li>Activity logging and audit trails</li>
                    <li>Professional email templates and notifications</li>
                    <li>Content management system for pages</li>
                </ul>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Installation Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="step" value="2">
                
                <div class="section-title">📊 Database Configuration</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <div class="help-text">Usually 'localhost' for most hosting providers</div>
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" placeholder="star_rent_db" required>
                        <div class="help-text">Create this database in your hosting control panel</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_user" placeholder="db_username" required>
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass" required>
                    </div>
                </div>
                
                <div class="section-title">👤 Administrator Account</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Username</label>
                        <input type="text" name="admin_username" value="admin" required>
                    </div>
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" value="admin@star-rent.vip" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Admin Password</label>
                    <input type="password" name="admin_password" value="StarRent2024!" required>
                    <div class="help-text">Change this after installation for security</div>
                </div>
                
                <div class="section-title">🌐 Website Configuration</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="Star Router Rent" required>
                    </div>
                    <div class="form-group">
                        <label>Site URL</label>
                        <input type="url" name="site_url" value="https://star-rent.vip" required>
                        <div class="help-text">Your website's full URL (with https://)</div>
                    </div>
                </div>
                
                <div class="section-title">💎 Enhanced Payment Gateway Configuration</div>
                <div class="form-group">
                    <label>Plisio.net API Key</label>
                    <input type="text" name="plisio_api_key" placeholder="Enter your Plisio API key">
                    <div class="help-text">Get from <a href="https://plisio.net/account/api" target="_blank">Plisio Dashboard</a> - Supports Bitcoin and other cryptocurrencies</div>
                </div>
                <div class="crypto-note">
                    <h4>🔔 Payment Method Note</h4>
                    <p>Users will see BTC as the primary option on the deposit page. They can change to other supported currencies (USDT, ETH, etc.) through Plisio's interface.</p>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Minimum Deposit ($)</label>
                        <input type="number" name="min_deposit" value="100" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Withdrawal Fee (%)</label>
                        <input type="number" name="withdrawal_fee" value="2.5" step="0.1" required>
                    </div>
                </div>

                <div class="section-title">📧 Email Configuration (Optional)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" placeholder="mail.yourdomain.com">
                    </div>
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="number" name="smtp_port" value="587">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" name="smtp_username" placeholder="noreply@yourdomain.com">
                    </div>
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="smtp_password">
                    </div>
                </div>
                
                <button type="submit" class="btn">🚀 Install Enhanced Star Router Rent</button>
            </form>
            
        <?php elseif ($step == 3): ?>
            <div class="success-box">
                <h1 style="color: #40c057; font-size: 36px; margin-bottom: 20px;">✅ Installation Complete!</h1>
                <h2>Enhanced Star Router Rent is now live with advanced payment gateways!</h2>
                
                <div class="credentials">
                    <h3>🔐 Admin Panel Access</h3>
                    <p><strong>URL:</strong> <?php echo htmlspecialchars($_POST['site_url']); ?>/admin/</p>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($_POST['admin_username']); ?></p>
                    <p><strong>Password:</strong> <?php echo htmlspecialchars($_POST['admin_password']); ?></p>
                </div>
                
                <div class="credentials">
                    <h3>🎯 What's Installed</h3>
                    <p>✅ Complete database schema with UUID support</p>
                    <p>✅ 8 premium router devices in major global locations</p>
                    <p>✅ 4 investment plans: Starter (30d, 0.8%), Growth (90d, 1.2%), Premium (180d, 1.5%), Elite (365d, 2.0%)</p>
                    <p>✅ Plisio.net cryptocurrency payment gateway (BTC, USDT, ETH, LTC, BCH, TRX, XMR, DASH, ZEC)</p>
                    <p>✅ Multi-level referral system (10%, 7%, 5% commission rates)</p>
                    <p>✅ Professional admin panel with comprehensive management tools</p>
                    <p>✅ Email notification system with customizable templates</p>
                    <p>✅ Content management system for website pages</p>
                    <p>✅ User dashboard with investment tracking</p>
                    <p>✅ Mobile-responsive design with dark/light theme support</p>
                    <p>✅ Security features: SSL, session management, activity logging</p>
                    <p>✅ Withdrawal system with cryptocurrency support</p>
                    <p>✅ Real-time webhook processing for payments</p>
                    <p>✅ Database backup and restore system</p>
                    <p>✅ Dashboard customization settings</p>
                    <p>✅ Enhanced referral commission tracking</p>
                </div>
                
                <div class="credentials">
                    <h3>🔧 Next Steps</h3>
                    <p>1. <strong>Security:</strong> Delete this install.php file immediately</p>
                    <p>2. <strong>Payment Setup:</strong> Configure Plisio API key in Admin → Payment Gateways</p>
                    <p>3. <strong>Email Setup:</strong> Configure SMTP settings in Admin → Settings</p>
                    <p>4. <strong>Content:</strong> Customize website pages in Admin → Pages</p>
                    <p>5. <strong>Templates:</strong> Customize email templates in Admin → Email Templates</p>
                    <p>6. <strong>Plans:</strong> Adjust investment plans and rates as needed</p>
                    <p>7. <strong>Testing:</strong> Test payment flows and email notifications</p>
                    <p>8. <strong>SSL:</strong> Ensure SSL certificate is properly configured</p>
                    <p>9. <strong>Backup:</strong> Create your first database backup in Admin → Backup & Restore</p>
                    <p>10. <strong>Dashboard:</strong> Customize dashboard settings in Admin → Dashboard Settings</p>
                </div>
                
                <a href="<?php echo htmlspecialchars($_POST['site_url']); ?>/admin/" class="btn" style="display: inline-block; text-decoration: none; margin: 15px 10px;">🎛️ Access Admin Panel</a>
                <a href="<?php echo htmlspecialchars($_POST['site_url']); ?>/" class="btn" style="display: inline-block; text-decoration: none; margin: 15px 10px; background: linear-gradient(135deg, #51cf66, #40c057);">🌐 View Website</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>