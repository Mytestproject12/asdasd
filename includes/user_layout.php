<?php
/**
 * User Layout Component
 * Provides consistent sidebar and layout for all user pages
 */

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$site_name = getSetting('site_name', 'Star Router Rent');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
            transform: translateX(0);
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 1rem 2rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            border-right: 4px solid white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .mobile-menu-btn {
            display: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1002;
        }
        
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mobile-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Tablet and mobile responsive design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 280px;
            }
            
            .main-content {
                margin-left: 280px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
                width: 250px;
            }
            
            .sidebar.active {
                transform: translateX(0);
                z-index: 1001;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .header {
                padding: 1rem 1.5rem;
                margin-left: 0;
            }
            
            .header h1 {
                font-size: 1.5rem;
                text-align: center;
                margin-left: 2rem;
            }
            
            .user-info {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
            }
            
            .main-content {
                padding: 0.5rem;
            }
            
            .header {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .header h1 {
                font-size: 1.25rem;
                margin-left: 1.5rem;
            }
        }
        
        /* Auto-hide functionality for desktop */
        @media (min-width: 769px) {
            .sidebar.auto-hide {
                transform: translateX(-200px);
            }
            
            .sidebar.auto-hide:hover {
                transform: translateX(0);
            }
            
            .main-content.sidebar-hidden {
                margin-left: 50px;
            }
            
            .sidebar.auto-hide .sidebar-nav a {
                padding: 1rem 1rem;
                text-align: center;
            }
            
            .sidebar.auto-hide .sidebar-nav a span {
                display: none;
            }
            
            .sidebar.auto-hide .sidebar-header {
                text-align: center;
                padding: 1rem;
            }
            
            .sidebar.auto-hide .sidebar-header h2 {
                font-size: 1rem;
                writing-mode: vertical-rl;
                text-orientation: mixed;
            }
        }
        
        /* Smooth scrolling for content */
        .main-content {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar for main content */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Sidebar toggle button for desktop auto-hide */
        .sidebar-toggle {
            position: fixed;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            z-index: 1003;
            display: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar-toggle:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-toggle.show {
            display: block;
        }
        
        /* Responsive content adjustments */
        .content-area {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Ensure tables are responsive */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-responsive table {
            min-width: 600px;
        }
        
        /* Card responsiveness */
        .card {
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .card {
                margin-bottom: 0.75rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        /* Grid responsiveness */
        .stats-grid,
        .plans-grid,
        .devices-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        
        @media (max-width: 768px) {
            .stats-grid,
            .plans-grid,
            .devices-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
        
        /* Form responsiveness */
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
        }
        
        @media (max-width: 768px) {
            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }
        
        /* Button responsiveness */
        .btn {
            width: 100%;
            box-sizing: border-box;
        }
        
        @media (min-width: 769px) {
            .btn-group .btn {
                width: auto;
            }
        }
        
        /* Notification positioning */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
        }
        
        /* Sidebar navigation improvements */
        .sidebar-nav a {
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .sidebar-nav a:hover::before {
            left: 100%;
        }
        
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            .sidebar,
            .main-content,
            .mobile-overlay,
            .sidebar-toggle {
                transition: none;
            }
        }
        
        /* Focus states */
        .sidebar-nav a:focus,
        .mobile-menu-btn:focus,
        .sidebar-toggle:focus {
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .sidebar {
                border-right: 2px solid #fff;
            }
            
            .sidebar-nav a {
                border: 1px solid transparent;
            }
            
            .sidebar-nav a:hover,
            .sidebar-nav a.active {
                border-color: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>💫 <?php echo htmlspecialchars($site_name); ?></h2>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
            <li><a href="investments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'investments.php' ? 'active' : ''; ?>">💰 Investments</a></li>
            <li><a href="devices.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'devices.php' ? 'active' : ''; ?>">🖥️ Devices</a></li>
            <li><a href="deposit.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'deposit.php' ? 'active' : ''; ?>">💳 Deposit</a></li>
            <li><a href="withdraw.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'withdraw.php' ? 'active' : ''; ?>">💰 Withdraw</a></li>
            <li><a href="transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">💳 Transactions</a></li>
            <li><a href="referrals.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : ''; ?>">👥 Referrals</a></li>
            <li><a href="notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">🔔 Notifications</a></li>
            <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">👤 Profile</a></li>
            <li><a href="support.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : ''; ?>">🎧 Support</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
            <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Welcome back, ' . htmlspecialchars($user['username']) . '!'; ?></h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div style="font-size: 0.9rem; color: #666;">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <div class="content-area">