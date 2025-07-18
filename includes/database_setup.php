<?php
/**
 * Database Setup and Migration Helper
 * Handles database initialization and schema updates
 */

require_once __DIR__ . '/../config.php';

class DatabaseSetup {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Initialize database tables if they don't exist
     */
    public function initializeTables() {
        try {
            // Create enhanced users table with proper UUID handling
            $this->pdo->exec("
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
                )
            ");
            
            // Create other essential tables
            $this->createAdminUsersTable();
            $this->createDevicesTable();
            $this->createInvestmentPlansTable();
            $this->createInvestmentsTable();
            $this->createRentalsTable();
            $this->createPaymentsTable();
            $this->createWithdrawalRequestsTable();
            $this->createReferralsTable();
            $this->createSystemSettingsTable();
            $this->createEmailTemplatesTable();
            $this->createNotificationsTable();
            $this->createWebpagesTable();
            $this->createActivityLogsTable();
            $this->createCurrenciesTable();
            
            return true;
        } catch (Exception $e) {
            error_log('Database initialization failed: ' . $e->getMessage());
            return false;
        }
    }
    
    private function createAdminUsersTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id VARCHAR(36) PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('super_admin', 'admin', 'moderator', 'support') DEFAULT 'admin',
                status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    private function createDevicesTable() {
        $this->pdo->exec("
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
            )
        ");
    }
    
    private function createInvestmentPlansTable() {
        $this->pdo->exec("
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
            )
        ");
    }
    
    private function createInvestmentsTable() {
        $this->pdo->exec("
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
                INDEX idx_user_status (user_id, status),
                INDEX idx_status (status)
            )
        ");
    }
    
    private function createRentalsTable() {
        $this->pdo->exec("
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
                INDEX idx_user_status (user_id, status),
                INDEX idx_device_status (device_id, status)
            )
        ");
    }
    
    private function createPaymentsTable() {
        $this->pdo->exec("
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
                INDEX idx_user_status (user_id, status),
                INDEX idx_transaction_id (transaction_id),
                INDEX idx_type_status (type, status),
                INDEX idx_payment_method_status (payment_method, status)
            )
        ");
    }
    
    private function createWithdrawalRequestsTable() {
        $this->pdo->exec("
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
                INDEX idx_user_status (user_id, status),
                INDEX idx_status (status)
            )
        ");
    }
    
    private function createReferralsTable() {
        $this->pdo->exec("
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
                INDEX idx_referrer (referrer_id),
                INDEX idx_referred (referred_id),
                INDEX idx_level (level)
            )
        ");
    }
    
    private function createSystemSettingsTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                setting_type ENUM('string', 'number', 'boolean', 'json', 'text') DEFAULT 'string',
                category VARCHAR(50) NOT NULL DEFAULT 'general',
                description TEXT NULL,
                is_public BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Also create password_resets table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user (user_id),
                INDEX idx_token (token)
            )
        ");
    }
    
    private function createEmailTemplatesTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS email_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_key VARCHAR(100) UNIQUE NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                variables JSON NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    }
    
    private function createNotificationsTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_read (user_id, is_read),
                INDEX idx_created_at (created_at)
            )
        ");
    }
    
    private function createWebpagesTable() {
        $this->pdo->exec("
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
            )
        ");
    }
    
    private function createActivityLogsTable() {
        $this->pdo->exec("
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
            )
        ");
    }
    
    private function createCurrenciesTable() {
        $this->pdo->exec("
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
            )
        ");
    }
    
    /**
     * Insert default data
     */
    public function insertDefaultData() {
        try {
            $this->insertDefaultInvestmentPlans();
            $this->insertDefaultDevices();
            $this->insertDefaultSettings();
            return true;
        } catch (Exception $e) {
            error_log('Failed to insert default data: ' . $e->getMessage());
            return false;
        }
    }
    
    private function insertDefaultInvestmentPlans() {
        $plans = [
            ['Starter Plan', 30, 0.8, 100.00, 1000.00, 'Perfect for beginners - short term with good returns', '["0.8% Daily Return", "24% Monthly Return", "$100 Minimum", "24/7 Support", "Instant Activation"]', 1, 0, 1],
            ['Growth Plan', 90, 1.2, 500.00, 5000.00, 'Balanced growth plan for steady income', '["1.2% Daily Return", "108% Total Return", "$500 Minimum", "Priority Support", "Weekly Reports"]', 1, 1, 2],
            ['Premium Plan', 180, 1.5, 1000.00, 10000.00, 'Premium plan for serious investors', '["1.5% Daily Return", "270% Total Return", "$1,000 Minimum", "VIP Support", "Daily Reports"]', 1, 0, 3],
            ['Elite Plan', 365, 2.0, 5000.00, 50000.00, 'Elite plan for maximum returns', '["2.0% Daily Return", "730% Annual Return", "$5,000 Minimum", "Personal Manager", "Real-time Analytics"]', 1, 0, 4]
        ];
        
        foreach ($plans as $plan) {
            $plan_id = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO investment_plans (id, name, duration_days, daily_rate, min_amount, max_amount, description, features, is_active, is_featured, sort_order, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute(array_merge([$plan_id], $plan));
        }
    }
    
    private function insertDefaultDevices() {
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
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO devices (id, device_id, name, model, location, daily_rate, max_speed_down, max_speed_up, uptime_percentage, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())
            ");
            $stmt->execute(array_merge([$device_id], $device));
        }
    }
    
    private function insertDefaultSettings() {
        $settings = [
            ['site_name', 'Star Router Rent', 'string', 'general', 'Site name', 1],
            ['site_url', 'https://star-rent.vip', 'string', 'general', 'Site URL', 1],
            ['admin_email', 'admin@star-rent.vip', 'string', 'general', 'Admin email', 0],
            ['site_description', 'Premium router rental platform with guaranteed daily profits', 'text', 'general', 'Site description', 1],
            ['min_deposit', '100', 'number', 'payments', 'Minimum deposit amount', 1],
            ['max_deposit', '50000', 'number', 'payments', 'Maximum deposit amount', 1],
            ['min_withdrawal', '20', 'number', 'payments', 'Minimum withdrawal amount', 1],
            ['withdrawal_fee', '2.5', 'number', 'payments', 'Withdrawal fee percentage', 1],
            ['referral_level_1_rate', '10.0', 'number', 'referrals', 'Level 1 referral commission rate', 1],
            ['referral_level_2_rate', '7.0', 'number', 'referrals', 'Level 2 referral commission rate', 1],
            ['referral_level_3_rate', '5.0', 'number', 'referrals', 'Level 3 referral commission rate', 1],
            ['referral_max_deposits', '2', 'number', 'referrals', 'Maximum deposits that earn referral commission', 1],
            ['maintenance_mode', 'false', 'boolean', 'general', 'Maintenance mode', 0],
            ['registration_enabled', 'true', 'boolean', 'general', 'Allow new registrations', 1],
            ['email_notifications', 'true', 'boolean', 'email', 'Enable email notifications', 1]
        ];
        
        foreach ($settings as $setting) {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute($setting);
        }
    }
}
?>