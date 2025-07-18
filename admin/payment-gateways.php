<?php
require_once '../includes/session_config.php';
require_once '../config.php';
require_once '../includes/PaymentManager.php';

// Check if admin is logged in
requireAdminLogin();

$site_name = getSetting('site_name', 'Star Router Rent');
$success = '';
$error = '';

// Handle gateway settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [
            'plisio_api_key' => $_POST['plisio_api_key']
        ];
        
        $pdo->beginTransaction();
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) 
                VALUES (?, ?, 'string', 'payments', ?, 0) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value, ucfirst(str_replace('_', ' ', $key))]);
        }
        
        $pdo->commit();
        $success = 'Payment gateway settings updated successfully!';
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Test gateway connections
$gateway_status = [];

// Test Plisio
$plisio_api_key = getSetting('plisio_api_key');
if ($plisio_api_key) {
    try {
        $paymentManager = new PaymentManager($pdo);
        $test_results = $paymentManager->testAllGateways();
        $gateway_status['plisio'] = $test_results['plisio'] ?? ['success' => false, 'message' => 'Test failed'];
    } catch (Exception $e) {
        $gateway_status['plisio'] = ['success' => false, 'message' => $e->getMessage()];
    }
} else {
    $gateway_status['plisio'] = ['success' => false, 'message' => 'API key not configured'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateways - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --bg-color: #ffffff;
            --text-color: #333333;
            --border-color: #e9ecef;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --sidebar-width: 280px;
        }
        
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
            --shadow-color: rgba(255, 255, 255, 0.1);
            --light-color: #2d2d2d;
            --dark-color: #0d1117;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }
        
        .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 1rem;
            font-size: 1.1rem;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
        }
        
        .top-bar {
            background: var(--bg-color);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px var(--shadow-color);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .theme-toggle {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        
        .content-area {
            padding: 2rem;
        }
        
        .gateways-grid {
            display: grid;
            gap: 2rem;
        }
        
        .card {
            background: var(--bg-color);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.connected {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.disconnected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .gateway-info {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .gateway-logo {
            text-align: center;
        }
        
        .gateway-logo img {
            max-width: 150px;
            height: auto;
        }
        
        .gateway-logo .logo-placeholder {
            width: 150px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto;
        }
        
        .gateway-details h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .gateway-features {
            list-style: none;
            padding: 0;
        }
        
        .gateway-features li {
            padding: 0.25rem 0;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .gateway-features li::before {
            content: '✓';
            color: var(--success-color);
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: var(--text-color);
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .test-result {
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .test-result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .test-result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .mobile-menu-btn {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
        }
        
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .mobile-overlay.active {
                display: block;
            }
            
            .top-bar {
                padding: 1rem;
                margin-left: 4rem;
            }
            
            .content-area {
                padding: 1rem;
            }
            
            .gateway-info {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="mobile-overlay" id="mobileOverlay"></div>
        
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-star"></i> Admin Panel</h2>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <div class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">User Management</div>
                    <div class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            Users
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="referrals.php" class="nav-link">
                            <i class="fas fa-share-alt"></i>
                            Referrals
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Financial</div>
                    <div class="nav-item">
                        <a href="investments.php" class="nav-link">
                            <i class="fas fa-chart-line"></i>
                            Investments
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="payments.php" class="nav-link">
                            <i class="fas fa-credit-card"></i>
                            Payments
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="withdrawals.php" class="nav-link">
                            <i class="fas fa-money-bill-wave"></i>
                            Withdrawals
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Platform</div>
                    <div class="nav-item">
                        <a href="devices.php" class="nav-link">
                            <i class="fas fa-server"></i>
                            Devices
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="plans.php" class="nav-link">
                            <i class="fas fa-layer-group"></i>
                            Investment Plans
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Content</div>
                    <div class="nav-item">
                        <a href="pages.php" class="nav-link">
                            <i class="fas fa-file-alt"></i>
                            Pages
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="notifications.php" class="nav-link">
                            <i class="fas fa-bell"></i>
                            Notifications
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <div class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="payment-gateways.php" class="nav-link active">
                            <i class="fas fa-credit-card"></i>
                            Payment Gateways
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="activity-logs.php" class="nav-link">
                            <i class="fas fa-history"></i>
                            Activity Logs
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title">Payment Gateways</h1>
                <div class="top-bar-actions">
                    <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
                </div>
            </div>
            
            <div class="content-area">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="gateways-grid">
                        <!-- Plisio Gateway -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-bitcoin"></i>
                                    Plisio.net
                                </h3>
                                <span class="status-badge <?php echo $gateway_status['plisio']['success'] ? 'connected' : 'disconnected'; ?>">
                                    <?php echo $gateway_status['plisio']['success'] ? 'Connected' : 'Disconnected'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="gateway-info">
                                    <div class="gateway-logo">
                                        <div class="logo-placeholder">PLISIO</div>
                                    </div>
                                    <div class="gateway-details">
                                        <h4>Cryptocurrency Payment Gateway</h4>
                                        <ul class="gateway-features">
                                            <li>Bitcoin (BTC) payments</li>
                                            <li>Ethereum (ETH) payments</li>
                                            <li>USDT (TRC20 & ERC20) payments</li>
                                            <li>50+ supported cryptocurrencies</li>
                                            <li>Real-time payment notifications</li>
                                            <li>Low transaction fees</li>
                                            <li>Instant confirmations</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="plisio_api_key">Plisio API Key</label>
                                    <input type="text" id="plisio_api_key" name="plisio_api_key" 
                                           value="<?php echo htmlspecialchars($plisio_api_key); ?>" 
                                           placeholder="Enter your Plisio API key">
                                    <div class="help-text">
                                        Get your API key from <a href="https://plisio.net/account/api" target="_blank">Plisio Dashboard</a>
                                    </div>
                                </div>
                                
                                <?php if (isset($gateway_status['plisio'])): ?>
                                    <div class="test-result <?php echo $gateway_status['plisio']['success'] ? 'success' : 'error'; ?>">
                                        <i class="fas fa-<?php echo $gateway_status['plisio']['success'] ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                        <?php echo htmlspecialchars($gateway_status['plisio']['message']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Gateway Settings
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 3rem;">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i>
                                Setup Instructions
                            </h3>
                        </div>
                        <div class="card-body">
                            <h4 style="margin-bottom: 1rem;">Plisio.net Setup:</h4>
                            <ol style="padding-left: 1.5rem; line-height: 1.8;">
                                <li>Create an account at <a href="https://plisio.net" target="_blank">Plisio.net</a></li>
                                <li>Verify your account and complete KYC if required</li>
                                <li>Go to <a href="https://plisio.net/account/api" target="_blank">API Settings</a></li>
                                <li>Generate a new API key</li>
                                <li>Copy the API key and paste it in the field above</li>
                                <li>Set your webhook URL to: <code><?php echo getSetting('site_url'); ?>/api/webhook/plisio.php</code></li>
                                <li>Save the settings and test the connection</li>
                            </ol>
                            
                            <div style="margin-top: 2rem; padding: 1rem; background: #e3f2fd; border-radius: 8px;">
                                <h5 style="margin-bottom: 0.5rem;">Important Notes:</h5>
                                <ul style="padding-left: 1.5rem; line-height: 1.6;">
                                    <li>Users will see BTC as the primary option but can change to other currencies</li>
                                    <li>Plisio supports 50+ cryptocurrencies including USDT, ETH, LTC, and more</li>
                                    <li>All payments are processed securely through Plisio's infrastructure</li>
                                    <li>Webhook notifications ensure real-time payment status updates</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = newTheme === 'dark' ? '☀️' : '🌓';
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = savedTheme === 'dark' ? '☀️' : '🌓';
        });
        
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        });
        
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            mobileOverlay.classList.remove('active');
        });
    </script>
</body>
</html>