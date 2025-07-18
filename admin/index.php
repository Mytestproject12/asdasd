<?php
require_once '../includes/session_config.php';
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get admin data
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Get system settings
$site_name = getSetting('site_name', 'Star Router Rent');

// Get dashboard statistics
$stats = [];

// Users statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$stats['total_users'] = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE status = 'active'");
$stats['active_users'] = $stmt->fetch()['active_users'];

// Investment statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_investments, SUM(investment_amount) as total_invested FROM investments");
$investment_stats = $stmt->fetch();
$stats['total_investments'] = $investment_stats['total_investments'];
$stats['total_invested'] = $investment_stats['total_invested'] ?? 0;

// Payment statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_payments, SUM(amount) as total_amount FROM payments WHERE status = 'completed'");
$payment_stats = $stmt->fetch();
$stats['total_payments'] = $payment_stats['total_payments'];
$stats['total_payment_amount'] = $payment_stats['total_amount'] ?? 0;

// Withdrawal statistics
$stmt = $pdo->query("SELECT COUNT(*) as pending_withdrawals FROM withdrawal_requests WHERE status = 'pending'");
$stats['pending_withdrawals'] = $stmt->fetch()['pending_withdrawals'];

// Recent activities
$stmt = $pdo->prepare("
    SELECT al.*, u.username 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Recent users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Recent payments
$stmt = $pdo->prepare("
    SELECT p.*, u.username 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($site_name); ?></title>
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
        
        .sidebar-header p {
            opacity: 0.8;
            font-size: 0.9rem;
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
        
        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
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
        
        .theme-toggle:hover {
            background: var(--border-color);
            transform: translateY(-2px);
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .content-area {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-color);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.users { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .stat-icon.investments { background: linear-gradient(135deg, #51cf66, #40c057); color: white; }
        .stat-icon.payments { background: linear-gradient(135deg, #ffd43b, #fab005); color: white; }
        .stat-icon.withdrawals { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-color);
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .card-body {
            padding: 1.5rem 2rem;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1rem;
        }
        
        .activity-icon.login { background: #e3f2fd; color: #1976d2; }
        .activity-icon.deposit { background: #e8f5e8; color: #2e7d32; }
        .activity-icon.withdrawal { background: #fff3e0; color: #f57c00; }
        .activity-icon.investment { background: #f3e5f5; color: #7b1fa2; }
        
        .activity-content h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .activity-content p {
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 1rem;
        }
        
        .user-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .user-info p {
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
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
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid {
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
                <p>Welcome, <?php echo htmlspecialchars($admin['username']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <div class="nav-item">
                        <a href="index.php" class="nav-link active">
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
                    <div class="nav-item">
                        <a href="email-templates.php" class="nav-link">
                            <i class="fas fa-envelope"></i>
                            Email Templates
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
                        <a href="payment-gateways.php" class="nav-link">
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
                <h1 class="page-title">Dashboard Overview</h1>
                <div class="top-bar-actions">
                    <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
                    <div class="admin-info">
                        <div class="admin-avatar">
                            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($admin['username']); ?></div>
                            <div style="font-size: 0.8rem; opacity: 0.7;"><?php echo ucfirst($admin['role']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon investments">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value">$<?php echo number_format($stats['total_invested'], 2); ?></div>
                        <div class="stat-label">Total Invested</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon payments">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stat-value">$<?php echo number_format($stats['total_payment_amount'], 2); ?></div>
                        <div class="stat-label">Total Payments</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon withdrawals">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['pending_withdrawals']); ?></div>
                        <div class="stat-label">Pending Withdrawals</div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                            <a href="activity-logs.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                View All
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_activities)): ?>
                                <p style="text-align: center; color: #666; padding: 2rem;">No recent activities</p>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo $activity['action']; ?>">
                                            <i class="fas fa-<?php 
                                                echo match($activity['action']) {
                                                    'user_login' => 'sign-in-alt',
                                                    'deposit_completed' => 'plus-circle',
                                                    'withdrawal_requested' => 'minus-circle',
                                                    'investment_created' => 'chart-line',
                                                    default => 'info-circle'
                                                };
                                            ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h4><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></h4>
                                            <p><?php echo htmlspecialchars($activity['details']); ?></p>
                                            <p><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <div class="card" style="margin-bottom: 1rem;">
                            <div class="card-header">
                                <h3 class="card-title">Recent Users</h3>
                                <a href="users.php" class="btn btn-primary">
                                    <i class="fas fa-eye"></i>
                                    View All
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_users)): ?>
                                    <p style="text-align: center; color: #666; padding: 1rem;">No users yet</p>
                                <?php else: ?>
                                    <?php foreach ($recent_users as $user): ?>
                                        <div class="user-item">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <div class="user-info">
                                                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                                <p><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Payments</h3>
                                <a href="payments.php" class="btn btn-primary">
                                    <i class="fas fa-eye"></i>
                                    View All
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_payments)): ?>
                                    <p style="text-align: center; color: #666; padding: 1rem;">No payments yet</p>
                                <?php else: ?>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon <?php echo $payment['type']; ?>">
                                                <i class="fas fa-<?php 
                                                    echo match($payment['type']) {
                                                        'deposit' => 'plus-circle',
                                                        'withdrawal' => 'minus-circle',
                                                        'investment' => 'chart-line',
                                                        default => 'credit-card'
                                                    };
                                                ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h4><?php echo htmlspecialchars($payment['username']); ?></h4>
                                                <p>$<?php echo number_format($payment['amount'], 2); ?> - <?php echo ucfirst($payment['type']); ?></p>
                                                <p><?php echo date('M j, Y H:i', strtotime($payment['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
            
            // Update theme toggle icon
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = newTheme === 'dark' ? '☀️' : '🌓';
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Update theme toggle icon
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
        
        // Close sidebar when clicking on nav links on mobile
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    mobileOverlay.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>